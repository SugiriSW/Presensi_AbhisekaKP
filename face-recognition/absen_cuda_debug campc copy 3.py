import cv2
import numpy as np
import os
import pickle
import json
import csv
import time
from datetime import datetime
from keras_facenet import FaceNet
import tensorflow as tf
from sklearn.metrics.pairwise import cosine_similarity

# ========== GPU SETUP ==========
gpus = tf.config.experimental.list_physical_devices('GPU')
if gpus:
    for gpu in gpus:
        tf.config.experimental.set_memory_growth(gpu, True)

# ========== KONFIGURASI ==========
ESP32_CAM_URL = "http://192.168.1.29:81/stream"
DATA_DIR = "data"
UID_MAP_FILE = "uid_to_name.json"
ABSEN_FILE = "absensi.csv"
SIMILARITY_THRESHOLD = 0.6
COOLDOWN_SECONDS = 1.0

# ========== LOAD UID → NAMA ==========
if not os.path.exists(UID_MAP_FILE):
    print("[❌] File uid_to_name.json tidak ditemukan.")
    exit()

with open(UID_MAP_FILE, "r") as f:
    uid_to_name = json.load(f)

uid = input("Masukkan UID (dari RFID): ")
if uid not in uid_to_name:
    print(f"[❌] UID {uid} tidak ditemukan.")
    exit()

nama = uid_to_name[uid]
file_path = os.path.join(DATA_DIR, f"{uid}.pkl")
if not os.path.exists(file_path):
    print(f"[❌] Embedding UID {uid} tidak ditemukan.")
    exit()

# ========== LOAD EMBEDDING ==========
with open(file_path, 'rb') as f:
    saved_embeddings = pickle.load(f)
if len(saved_embeddings.shape) == 1:
    saved_embeddings = [saved_embeddings]

# ========== INISIALISASI ==========
embedder = FaceNet()
facedetect = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
cap = cv2.VideoCapture(ESP32_CAM_URL)

if not cap.isOpened():
    print("[❌] Gagal membuka stream dari ESP32-CAM")
    exit()

print(f"[INFO] Verifikasi presensi untuk: {nama} (UID: {uid})")

recognized = False
last_embed_time = 0

while True:
    ret, frame = cap.read()
    if not ret:
        print("[⚠️] Gagal membaca frame...")
        continue

    # Resize untuk kecepatan
    frame = cv2.resize(frame, (320, 240))
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    faces = facedetect.detectMultiScale(gray, scaleFactor=1.2, minNeighbors=4)

    label = "Mendeteksi wajah..."
    color = (0, 0, 0)

    for (x, y, w, h) in faces:
        if w < 80 or h < 80:
            continue  # Abaikan wajah kecil

        face = frame[y:y+h, x:x+w]
        face_resized = cv2.resize(face, (160, 160))
        face_rgb = cv2.cvtColor(face_resized, cv2.COLOR_BGR2RGB)

        if time.time() - last_embed_time >= COOLDOWN_SECONDS:
            embedding = embedder.embeddings([face_rgb])[0]
            sims = cosine_similarity([embedding], saved_embeddings)
            max_sim = np.max(sims)
            last_embed_time = time.time()

            if max_sim > SIMILARITY_THRESHOLD:
                label = f"[✅] {nama} Absen!"
                color = (0, 255, 0)
                recognized = True

                # Simpan presensi
                now = datetime.now().strftime("%Y-%m-%d %H:%M:%S")
                with open(ABSEN_FILE, "a", newline='') as csvfile:
                    writer = csv.writer(csvfile)
                    writer.writerow([uid, nama, now])
                print(f"[📌] {nama} absen pada {now}")
                break
            else:
                label = "[❌] Wajah tidak cocok!"
                color = (0, 0, 255)

        cv2.rectangle(frame, (x, y), (x+w, y+h), color, 2)
        cv2.putText(frame, label, (x, y-10), cv2.FONT_HERSHEY_SIMPLEX, 0.6, color, 2)

    cv2.putText(frame, f"UID: {uid} | Nama: {nama}", (5, 230), cv2.FONT_HERSHEY_SIMPLEX, 0.5, (200, 200, 200), 1)
    cv2.imshow("Presensi Wajah", frame)

    if recognized or cv2.waitKey(1) & 0xFF == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()