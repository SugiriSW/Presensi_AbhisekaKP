from flask import Flask, jsonify, request
from flask_cors import CORS
import mysql.connector
from mysql.connector import Error
from datetime import date, datetime, time as dt_time
import os
from dotenv import load_dotenv

app = Flask(__name__)
CORS(app)
load_dotenv()

# ========== KONFIGURASI ==========
db_config = {
    'host': os.getenv('DB_HOST'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME')
}

# Variabel global untuk UID terakhir
last_tapped_uid = {"uid": None}

# Aturan jam kerja
WAKTU_MASUK_MULAI = dt_time(7, 0, 0)    # Jam 07:00
WAKTU_MASUK_AKHIR = dt_time(9, 0, 0)     # Jam 09:00
WAKTU_PULANG_MULAI = dt_time(17, 0, 0)   # Jam 17:00

# ========== ENDPOINT UTAMA ==========
@app.route('/get_uid', methods=['GET'])
def get_uid():
    """Untuk frontend mengambil UID terakhir"""
    return jsonify({
        "status": "success" if last_tapped_uid['uid'] else "empty",
        "uID": last_tapped_uid['uid'] or None
    })

@app.route('/handle-tap', methods=['POST'])
def handle_tap():
    """Endpoint untuk Arduino mengirim UID"""
    global last_tapped_uid
    
    if not request.is_json:
        return jsonify({"status": "error", "message": "Request harus JSON"}), 400
    
    uid = request.json.get('uid')
    if not uid:
        return jsonify({"status": "error", "message": "UID tidak ada"}), 400
    
    # Simpan UID terakhir untuk frontend
    last_tapped_uid['uid'] = uid
    
    try:
        cnx = mysql.connector.connect(**db_config)
        cursor = cnx.cursor(dictionary=True)
        
        # 1. Cek apakah karyawan ada dan aktif
        cursor.execute("SELECT nama FROM karyawan WHERE uid = %s AND status_aktif = TRUE", (uid,))
        karyawan = cursor.fetchone()
        
        if not karyawan:
            return jsonify({
                "status": "error",
                "message": "Kartu tidak terdaftar atau karyawan non-aktif"
            }), 404
        
        nama_karyawan = karyawan['nama']
        today = date.today()
        now_time = datetime.now().time()
        
        # 2. Cek riwayat presensi hari ini
        cursor.execute(
            "SELECT id, jam_pulang FROM presensi WHERE uid = %s AND tanggal = %s", 
            (uid, today)
        )
        presensi = cursor.fetchone()
        
        # 3. Proses presensi
        if not presensi:  # Belum presensi masuk
            if now_time < WAKTU_MASUK_MULAI:
                return jsonify({
                    "status": "error",
                    "message": "Belum waktunya presensi masuk (sebelum 07:00)"
                }), 400
                
            keterangan = 'TEPAT WAKTU' if now_time <= WAKTU_MASUK_AKHIR else 'TERLAMBAT'
            
            cursor.execute(
                "INSERT INTO presensi (uid, tanggal, jam_masuk, keterangan) VALUES (%s, %s, %s, %s)",
                (uid, today, now_time, keterangan)
            )
            cnx.commit()
            
            return jsonify({
                "status": "success",
                "message": f"{nama_karyawan} - Masuk ({keterangan})"
            })
            
        elif not presensi['jam_pulang']:  # Sudah masuk, belum pulang
            if now_time >= WAKTU_PULANG_MULAI:
                cursor.execute(
                    "UPDATE presensi SET jam_pulang = %s WHERE id = %s",
                    (now_time, presensi['id'])
                )
                cnx.commit()
                return jsonify({
                    "status": "success",
                    "message": f"{nama_karyawan} - Pulang"
                })
            else:
                return jsonify({
                    "status": "info",
                    "message": "Anda sudah presensi masuk hari ini"
                })
                
        else:  # Sudah presensi pulang
            return jsonify({
                "status": "info",
                "message": "Anda sudah selesai presensi hari ini"
            })
            
    except Exception as e:
        return jsonify({
            "status": "error",
            "message": f"Server error: {str(e)}"
        }), 500
        
    finally:
        if 'cnx' in locals() and cnx.is_connected():
            cursor.close()
            cnx.close()

if __name__ == '__main__':
    app.run(host='0.0.0.0', port=5001, debug=True)