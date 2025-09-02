import cv2
import numpy as np
import os
import pickle
import json
from keras_facenet import FaceNet
import tensorflow as tf
import time

# ========== SETUP GPU ==========
gpus = tf.config.experimental.list_physical_devices('GPU')
if gpus:
    for gpu in gpus:
        tf.config.experimental.set_memory_growth(gpu, True)

# ========== KONFIGURASI ==========
ESP32_CAM_URL = "http://192.168.1.29:81/stream"
DATA_DIR = "data"
os.makedirs(DATA_DIR, exist_ok=True)
UID_MAP_FILE = "uid_to_name.json"

# ========== LOAD MAPPING UID–NAMA ==========
if os.path.exists(UID_MAP_FILE):
    with open(UID_MAP_FILE, "r") as f:
        uid_to_name = json.load(f)
else:
    uid_to_name = {}

# ========== INPUT USER ==========
uid = input("Masukkan UID (unik): ")
if uid in uid_to_name:
    print(f"[⚠️] UID {uid} sudah terdaftar sebagai: {uid_to_name[uid]}")
    exit()

nama = input("Masukkan Nama Lengkap: ")
uid_to_name[uid] = nama
with open(UID_MAP_FILE, "w") as f:
    json.dump(uid_to_name, f)
print(f"[✅] UID {uid} → {nama} disimpan.")

# ========== INISIALISASI ==========
embedder = FaceNet()
facedetect = cv2.CascadeClassifier(cv2.data.haarcascades + 'haarcascade_frontalface_default.xml')
cap = cv2.VideoCapture(ESP32_CAM_URL)
if not cap.isOpened():
    print("[❌] Tidak bisa membuka kamera ESP32-CAM")
    exit()

print("[INFO] Tunggu... sistem akan otomatis merekam wajah sebanyak 5 kali...")

saved_embeddings = []
count = 0
last_capture_time = time.time()

while count < 5:
    ret, frame = cap.read()
    if not ret:
        print("[⚠️] Gagal membaca frame.")
        continue

    frame = cv2.resize(frame, (640, 480))
    gray = cv2.cvtColor(frame, cv2.COLOR_BGR2GRAY)
    faces = facedetect.detectMultiScale(gray, 1.3, 5)

    for (x, y, w, h) in faces:
        # Hindari capture terlalu cepat berturut-turut
        if time.time() - last_capture_time < 1.5:
            break

        face = frame[y:y+h, x:x+w]
        face_resized = cv2.resize(face, (160, 160))
        face_rgb = cv2.cvtColor(face_resized, cv2.COLOR_BGR2RGB)

        embedding = embedder.embeddings([face_rgb])[0]
        saved_embeddings.append(embedding)
        count += 1
        last_capture_time = time.time()
        print(f"[✅] Wajah ke-{count} terekam.")

        # Gambar bounding box dan status
        cv2.rectangle(frame, (x, y), (x+w, y+h), (0, 255, 0), 2)
        cv2.putText(frame, f"Terekam {count}/5", (x, y-10), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 0), 2)
        break  # Satu wajah per frame cukup

    cv2.putText(frame, f"{nama} - UID: {uid}", (10, 30), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (255, 255, 255), 2)
    cv2.putText(frame, f"Wajah terekam: {count}/5", (10, 60), cv2.FONT_HERSHEY_SIMPLEX, 0.8, (0, 255, 255), 2)

    cv2.imshow("Perekaman Otomatis - ESP32-CAM", frame)
    if cv2.waitKey(1) & 0xFF == ord('q'):
        break

cap.release()
cv2.destroyAllWindows()

# ========== SIMPAN EMBEDDING ==========
if saved_embeddings:
    file_path = os.path.join(DATA_DIR, f"{uid}.pkl")
    with open(file_path, 'wb') as f:
        pickle.dump(np.array(saved_embeddings), f)
    print(f"[📦] Embedding wajah disimpan: {file_path}")
else:
    print("[❌] Tidak ada embedding yang disimpan.")
