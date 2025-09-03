from flask import Flask, jsonify, render_template, request
import mysql.connector
from mysql.connector import Error
from flask_cors import CORS
import threading
from dotenv import load_dotenv
import time
from datetime import date, datetime, time as dt_time
import os
from werkzeug.security import generate_password_hash # Untuk hashing password
from werkzeug.utils import secure_filename # Untuk mengamankan nama file

# cara get uid format json
app = Flask(__name__)

CORS(app)

load_dotenv() # Memuat variabel dari file .env

# --- Variabel Global untuk Pairing Mode ---
# Tahap Development.
pairing_info = {
    "is_active": False,
    "user_id_to_pair": None,
    "timestamp": 0
}

#Konfigurasi database
db_config = {
    'host': os.getenv('DB_HOST'),
    'user': os.getenv('DB_USER'),
    'password': os.getenv('DB_PASSWORD'),
    'database': os.getenv('DB_NAME')
}

#Konfigurasi Upload folder foto
UPLOAD_FOLDER = os.getenv('UPLOAD_FOLDER_FOTO')
app.config['UPLOAD_FOLDER'] = UPLOAD_FOLDER
ALLOWED_EXTENSIONS = {'png', 'jpg', 'jpeg'}

# --- Variabel Global untuk Cek UID ---
last_tapped_uid = {"uid": None}

# --- ATURAN JAM KERJA ---
WAKTU_MASUK_MULAI = dt_time(7, 0, 0)
WAKTU_MASUK_AKHIR = dt_time(9, 0, 0)
WAKTU_PULANG_MULAI = dt_time(17, 0, 0)
WAKTU_PULANG_AKHIR = dt_time(22, 0, 0)

# === Endpoint untuk Frontend Web ===


@app.route('/')
def dashboard():
    return render_template('index.html')

# === API untuk Cek UID ===
@app.route('/api/last-uid', methods=['GET'])
def get_last_uid():
    return jsonify(last_tapped_uid)

# ENDPOINT IOT 
@app.route('/handle-tap', methods=['POST'])
def handle_tap():
    uid = request.json.get('uid')
    if not uid: return jsonify({"status": "error", "message": "UID tidak ada"}), 400
    
    last_tapped_uid['uid'] = uid
    cnx = None
    try:
        cnx = mysql.connector.connect(**db_config)
        cursor = cnx.cursor(dictionary=True)

        # --- LOGIKA PAIRING ---
        if pairing_info["is_active"]:
            user_id = pairing_info["user_id_to_pair"]
            cursor.execute("SELECT nama_lengkap FROM pengguna WHERE uid = %s", (uid,))
            if cursor.fetchone():
                return jsonify({"status": "error", "message": "Kartu sudah dimiliki orang lain"})
            
            cursor.execute("UPDATE pengguna SET uid = %s WHERE id_user = %s", (uid, user_id))
            cnx.commit()
            pairing_info.update({"is_active": False, "user_id_to_pair": None})
            return jsonify({"status": "success", "message": "Registrasi Berhasil!"})
        
        # --- LOGIKA PRESENSI ---
        else:
            cursor.execute("SELECT * FROM pengguna WHERE uid = %s AND status = 'aktif'", (uid,))
            pengguna = cursor.fetchone()
            if not pengguna:
                return jsonify({"status": "error", "message": "Kartu Tidak Terdaftar"})

            id_pengguna = pengguna['id_user']
            nama_pengguna = pengguna['nama_lengkap']
            today = date.today()
            now_time = datetime.now().time()
            
            cursor.execute("SELECT id_presensi, jam_pulang FROM presensi WHERE uid = %s AND tanggal_presensi = %s", (uid, today))
            presensi_hari_ini = cursor.fetchone()

            if presensi_hari_ini is None:
                if now_time < WAKTU_MASUK_MULAI:
                    return jsonify({"status": "error", "message": "Belum Waktunya Presensi"})
                
                keterangan_masuk = 'Hadir' if now_time <= WAKTU_MASUK_AKHIR else 'Terlambat'
                
                # PERBAIKAN: Menambahkan id_user saat INSERT
                cursor.execute(
                    "INSERT INTO presensi (id_user, uid, tanggal_presensi, jam_masuk, status_kehadiran) VALUES (%s, %s, %s, %s, %s)",
                    (id_pengguna, uid, today, now_time, keterangan_masuk)
                )
                cnx.commit()
                return jsonify({"status": "success", "message": f"Masuk: {nama_pengguna} ({keterangan_masuk})"})
            
            elif presensi_hari_ini['jam_pulang'] is None:
                if now_time >= WAKTU_PULANG_MULAI:
                    presensi_id = presensi_hari_ini['id_presensi']
                    cursor.execute("UPDATE presensi SET jam_pulang = %s WHERE id_presensi = %s", (now_time, presensi_id))
                    cnx.commit()
                    return jsonify({"status": "success", "message": f"Pulang: {nama_pengguna}"})
                else:
                    return jsonify({"status": "done", "message": "Anda Sudah Presensi Masuk"})
            
            else:
                return jsonify({"status": "done", "message": "Anda Sudah Konfirmasi Pulang"})

    except Error as e:
        return jsonify({"status": "error", "message": f"{e.errno} ({e.sqlstate}): {e.msg}"}), 500
    finally:
        if cnx and cnx.is_connected():
            cursor.close()
            cnx.close()  


if __name__ == '__main__':
    app.run(debug=True, host='0.0.0.0', port=5001)
