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
CORS(app, origins=[
         "http://localhost", 
         "http://localhost:80", 
         "http://localhost:8080", 
         "http://127.0.0.1",
         "http://localhost/Presensi_AbhisekaKP/frontend/kelola_karyawan.php",
         "http://localhost/Presensi_AbhisekaKP/frontend",
         "http://192.168.222.217",
         "http://192.168.222.217/handle-tap"],
     methods=["GET", "POST", "PUT", "DELETE", "OPTIONS"],
     allow_headers=["Content-Type", "Authorization", "X-Requested-With"],
     supports_credentials=True
)


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
# las_tapped_uid -> simpan ke var json -> baru upload ke db
#  ada yg di upload ke db, ada yang masuk ke presensi

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

# @app.before_request
# def handle_preflight():
#     if request.method == "OPTIONS":
#         response = make_response()
#         response.headers.add("Access-Control-Allow-Origin", "*")
#         response.headers.add('Access-Control-Allow-Headers', "*")
#         response.headers.add('Access-Control-Allow-Methods', "*")
#         return response


# API untuk CRUD pengguna 
@app.route('/api/pengguna', methods=['POST','OPTIONS'])
def add_pengguna():
    try:
        id_user = request.form.get('id_user', '').strip()
        username = request.form.get('username', '').strip()
        password = request.form.get('password', '').strip()
        nama_lengkap = request.form.get('nama_lengkap', '').strip()
        email = request.form.get('email', '').strip()
        peran = request.form.get('peran', '').strip()
        uid = request.form.get('uid', '').strip()

        if not all([id_user, username, password, nama_lengkap, email, peran]):
            return jsonify({"status": "error", "message": "Semua field (kecuali UID) wajib diisi!"}), 400

        cnx = mysql.connector.connect(**db_config)
        cursor = cnx.cursor()

        if uid:
            cursor.execute("SELECT id_user FROM pengguna WHERE id_user = %s OR username = %s OR uid = %s", (id_user, username, uid))
        else:
            cursor.execute("SELECT id_user FROM pengguna WHERE id_user = %s OR username = %s", (id_user, username))
        
        if cursor.fetchone():
            return jsonify({"status": "error", "message": "ID User, Username, atau UID sudah digunakan!"}), 409

        hashed_password = generate_password_hash(password)
        foto_path = None
        if 'foto' in request.files:
            file = request.files['foto']
            if file and file.filename != '' and allowed_file(file.filename):
                filename = secure_filename(file.filename)
                unique_filename = str(int(time.time())) + '_' + filename
                if not os.path.exists(app.config['UPLOAD_FOLDER']):
                    os.makedirs(app.config['UPLOAD_FOLDER'])
                file.save(os.path.join(app.config['UPLOAD_FOLDER'], unique_filename))
                foto_path = os.path.join(app.config['UPLOAD_FOLDER'], unique_filename)

        uid_to_insert = uid if uid else None

        cursor.execute(
            "INSERT INTO pengguna (id_user, username, password, nama_lengkap, email, peran, uid, foto_path) VALUES (%s, %s, %s, %s, %s, %s, %s, %s)",
            (id_user, username, hashed_password, nama_lengkap, email, peran, uid_to_insert, foto_path)
        )
        cnx.commit()
        
        return jsonify({"status": "success", "message": "pengguna berhasil ditambahkan!"}), 201

    except Error as e:
        return jsonify({"status": "error", "message": str(e)}), 500
    finally:
        if 'cnx' in locals() and cnx.is_connected():
            cursor.close()
            cnx.close()

@app.route('/api/pengguna', methods=['GET', 'OPTIONS'])
def get_pengguna():
    try:
        cnx = mysql.connector.connect(**db_config)
        cursor = cnx.cursor(dictionary=True)
        cursor.execute("SELECT id_user, nama_lengkap, uid, status FROM pengguna ORDER BY nama_lengkap ASC")
        pengguna = cursor.fetchall()
        for row in pengguna:
            if isinstance(row['status'], bytes):
                row['status'] = row['status'].decode('utf-8')
        return jsonify(pengguna)
    except Error as e:
        return jsonify({"error": str(e)}), 500
    finally:
        if 'cnx' in locals() and cnx.is_connected():
            cursor.close()
            cnx.close()

# UPDATE: Mengubah data pengguna (Diperbarui)
@app.route('/api/pengguna/<string:id>', methods=['PUT', 'OPTIONS'])
def update_pengguna(id):
    data = request.json
    nama_lengkap = data.get('nama_lengkap', '').strip()
    status = data.get('status')
    uid = data.get('uid', '').strip()

    if not nama_lengkap or status_aktif is None:
        return jsonify({"status": "error", "message": "Nama dan Status tidak boleh kosong"}), 400
    
    uid_to_update = uid if uid else None

    try:
        cnx = mysql.connector.connect(**db_config)
        cursor = cnx.cursor()
        if uid_to_update:
            cursor.execute("SELECT id_user FROM pengguna WHERE uid = %s AND id_user != %s", (uid_to_update, id))
            if cursor.fetchone():
                return jsonify({"status": "error", "message": "UID tersebut sudah digunakan oleh pengguna lain."}), 409

        cursor.execute("UPDATE pengguna SET nama_lengkap = %s, status = %s, uid = %s WHERE id = %s", (nama_lengkap, status, uid_to_update, id))
        cnx.commit()
        return jsonify({"status": "success", "message": "Data pengguna berhasil diperbarui"})
    except Error as e:
        return jsonify({"status": "error", "message": str(e)}), 500
    finally:
        if 'cnx' in locals() and cnx.is_connected():
            cursor.close()
            cnx.close()

# DELETE: Menghapus pengguna (Diperbarui)
@app.route('/api/pengguna/<string:id>', methods=['DELETE', 'OPTIONS'])
def delete_pengguna(id):
    try:
        cnx = mysql.connector.connect(**db_config)
        cursor = cnx.cursor()
        cursor.execute("DELETE FROM pengguna WHERE id_user = %s", (id,))
        cnx.commit()
        return jsonify({"status": "success", "message": "pengguna berhasil dihapus"})
    except Error as e:
        if e.errno == 1451:
            return jsonify({"status": "error", "message": "Tidak bisa menghapus, pengguna sudah memiliki data presensi."}), 409
        return jsonify({"status": "error", "message": str(e)}), 500
    finally:
        if 'cnx' in locals() and cnx.is_connected():
            cursor.close()
            cnx.close()


# ENDPOINT IOT 
@app.route('/handle-tap', methods=['POST','OPTIONS'])
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

    # # LOGIKA PAIRING
    # if pairing_info["is_active"]:
    #     user_id = pairing_info["user_id_to_pair"]
    #     cursor.execute("SELECT nama_lengkap FROM pengguna WHERE uid = %s", (uid,))
    #     if cursor.fetchone(): return jsonify({"status": "error", "message": "Kartu sudah dimiliki orang lain"})
        
    #     cursor.execute("UPDATE pengguna SET uid = %s WHERE id_user = %s", (uid, user_id))
    #     cnx.commit()
    #     pairing_info.update({"is_active": False, "user_id_to_pair": None})
    #     return jsonify({"status": "success", "message": "Registrasi Berhasil!"})
    # try:
    #     cnx = mysql.connector.connect(**db_config)
    #     cursor = cnx.cursor(dictionary=True)
        
    #     cursor.execute("SELECT * FROM pengguna WHERE uid = %s AND status = 'aktif'", (uid,))
    #     pengguna = cursor.fetchone()
    #     if not pengguna:
    #         return jsonify({"status": "error", "message": "Kartu Tidak Terdaftar Atau Kartu Sudah Terdaftar"})

    #     # MENGAMBIL DATA WAKTU
    #     nama_pengguna = pengguna['nama_lengkap']
    #     hari_ini = date.today()
    #     waktu_hari_ini = datetime.now().time()

    #     # MENGECEK DATA PRESENSI HARI INI
    #     cursor.execute("SELECT id_user, jam_masuk, jam_pulang FROM presensi WHERE uid = %s AND tanggal_presensi = %s", (uid, hari_ini))
    #     presensi_hari_ini = cursor.fetchone()

    #     if presensi_hari_ini is None:
    #         # KONDISI MELAKUKAN PRESENSI KEHADIRAN
    #         if waktu_hari_ini < WAKTU_MASUK_MULAI:
    #             return jsonify({"status": "error", "message": "Belum Waktunya Presensi"})
    #         keterangan_masuk = 'Hadir' if waktu_hari_ini <= WAKTU_MASUK_AKHIR else 'Terlambat'
    #         cursor.execute("INSERT INTO presensi (uid, tanggal_presensi, jam_masuk, status_kehadiran) VALUES (%s, %s, %s, %s)",(uid, hari_ini, waktu_hari_ini, keterangan_masuk))
    #         cnx.commit()
    #         return jsonify({"status": "success", "message": f"Masuk: {nama_pengguna} ({keterangan_masuk})"})
        
    #     # KONDISI MELAKUKAN PRESENSI PULANG ATAU DUPLIKAT KEHADIRAN
    #     elif presensi_hari_ini['jam_pulang'] is None:
    #         if waktu_hari_ini >= WAKTU_PULANG_MULAI and waktu_hari_ini <= WAKTU_PULANG_AKHIR:
    #             presensi_id = presensi_hari_ini['id_presensi']
    #             # UPDATE JAM PULANG
    #             cursor.execute("UPDATE presensi SET jam_pulang = %s WHERE id_presensi = %s", (waktu_hari_ini, presensi_id))
    #             cnx.commit()
    #             return jsonify({"status": "success", "message": f"Pulang: {nama_pengguna}"})
    #         else:
    #             # SUDAH MELAKUKAN PRESENSI KEHADIRAN TAPI BELUM WAKTUNYA PULANG
    #             return jsonify({"status": "done", "message": "Anda Sudah Presensi Kehadiran"})
    #     else:
    #         # SUDAH KONFIRMASI PULANG SEBELUMNYA
    #         return jsonify({"status": "done", "message": "Anda Sudah Konfirmasi Pulang"})
    # except Error as e:
    #     return jsonify({"status": "error", "message": str(e)}), 500
    # finally:
    #     if 'cnx' in locals() and cnx.is_connected():
    #         cursor.close()
    #         cnx.close(_
