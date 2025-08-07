#include <SPI.h>
#include <MFRC522.h>
#include <TFT_eSPI.h>
#include <WiFi.h>
#include <HTTPClient.h>
#include <ArduinoJson.h>
#include "config.h"

// === KONFIGURASI PIN LENGKAP ===
// LCD ST7789 
TFT_eSPI tft = TFT_eSPI();

// RFID MFRC522
#define RST_PIN    0
#define SS_PIN     5
MFRC522 rfid(SS_PIN, RST_PIN);

// Relay
#define RELAY1_PIN 25
#define RELAY2_PIN 26

// LED RGB 
#define LED_R_PIN 15
#define LED_G_PIN 2
#define LED_B_PIN 4

// --- Deklarasi Fungsi Pembantu ---
void displayMessage(String M1, String M2, int color);
void setLedColor(int red, int green, int blue);
String getUIDString();
void kirimUIDKeServer(String uid);
void displayMainMenu();

void setup() {
  Serial.begin(115200);

  // Inisialisasi semua pin OUTPUT
  pinMode(RELAY1_PIN, OUTPUT);
  pinMode(RELAY2_PIN, OUTPUT);
  pinMode(LED_R_PIN, OUTPUT);
  pinMode(LED_G_PIN, OUTPUT);
  pinMode(LED_B_PIN, OUTPUT);
  
  // Matikan semua relay dan LED di awal
  digitalWrite(RELAY1_PIN, LOW);
  digitalWrite(RELAY2_PIN, LOW);
  setLedColor(HIGH, HIGH, HIGH); // HIGH = OFF untuk common cathode

  //Inisialisasi LCD
  tft.init();
  tft.setRotation(1);

  // Proses koneksi WiFi dengan feedback LCD & LED
  displayMessage("Menghubungkan", "ke WiFi...", TFT_WHITE);
  setLedColor(HIGH, HIGH, LOW); // LED Biru = Menghubungkan
  WiFi.begin(ssid, password);
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  
  displayMessage("WiFi Terhubung!", WiFi.localIP().toString(), TFT_GREEN);
  setLedColor(HIGH, LOW, HIGH); // LED Hijau = Terhubung
  delay(2000);
  displayMainMenu();

  // Inisialisasi RFID
  SPI.begin();
  rfid.PCD_Init();
  
}

void loop() {
  if (rfid.PICC_IsNewCardPresent() && rfid.PICC_ReadCardSerial()) {
    String uid = getUIDString();
    Serial.println("Kartu Ditempel: " + uid);

    displayMessage("Memproses...", uid, TFT_YELLOW);
    setLedColor(LOW, LOW, HIGH); // LED Kuning = Sedang memproses
    
    kirimUIDKeServer(uid);

    delay(3000); 
    displayMainMenu();

    rfid.PICC_HaltA();
    rfid.PCD_StopCrypto1();
  }
}

// --- FUNGSI-FUNGSI PEMBANTU ---

void kirimUIDKeServer(String uid) {
  if (WiFi.status() != WL_CONNECTED) {
    displayMessage("Error", "WiFi Tidak Tersambung", TFT_RED);
    setLedColor(LOW, HIGH, HIGH); // LED Merah = Error
    return;
  }

  String fullUrl = String(serverUrl) + "/handle-tap"; 
  WiFiClient client;
  HTTPClient http;
  http.begin(client, fullUrl);
  http.addHeader("Content-Type", "application/json");

  JsonDocument doc;
  doc["uid"] = uid;
  String jsonData;
  serializeJson(doc, jsonData);
  
  int httpCode = http.POST(jsonData);
  
  if (httpCode > 0) {
    String payload = http.getString();
    Serial.println("Server Response: " + payload);
    JsonDocument responseDoc;
    deserializeJson(responseDoc, payload);
    String message = responseDoc["message"] | "Gagal Parsing";
    String status = responseDoc["status"] | "error";
    
    if (status == "success") {
      displayMessage("BERHASIL", message, TFT_GREEN);
      setLedColor(HIGH, LOW, HIGH); // LED Hijau
      // Aktifkan kedua relay sebagai feedback sukses
      digitalWrite(RELAY1_PIN, HIGH);
      digitalWrite(RELAY2_PIN, HIGH);
      delay(1000);
      digitalWrite(RELAY1_PIN, LOW);
      digitalWrite(RELAY2_PIN, LOW);
    } else {
      displayMessage("INFO", message, TFT_ORANGE);
      setLedColor(LOW, HIGH, HIGH); // LED Merah
    }
  } else {
    displayMessage("Error", "Koneksi Gagal", TFT_RED);
    setLedColor(LOW, HIGH, HIGH); // LED Merah
  }
  http.end();
}

// Fungsi untuk mengatur warna LED (LOW = ON)
void setLedColor(int red, int green, int blue) {
  digitalWrite(LED_R_PIN, red);
  digitalWrite(LED_G_PIN, green);
  digitalWrite(LED_B_PIN, blue);
}

// Fungsi untuk menampilkan pesan di LCD
void displayMessage(String M1, String M2, int color) {
  tft.fillScreen(TFT_NAVY);
  tft.setTextColor(color, TFT_BLACK);
  tft.setTextDatum(MC_DATUM);
  tft.drawString(M1, 120, 100, 4); 
  tft.drawString(M2, 120, 140, 2); 
}

// Fungsi untuk menampilkan menu utama di LCD
void displayMainMenu(){
  tft.fillScreen(TFT_NAVY);
  tft.setTextColor(TFT_WHITE, TFT_NAVY);
  tft.setTextDatum(MC_DATUM);
  setLedColor(HIGH, HIGH, HIGH); // Matikan LED saat di menu utama
  tft.drawString("Tempelkan Kartu", 120, 120, 4);
}

// Fungsi untuk membaca UID dari kartu
String getUIDString() {
    String uid = "";
    for (byte i = 0; i < rfid.uid.size; i++) {
        uid += (rfid.uid.uidByte[i] < 0x10 ? "0" : "");
        uid += String(rfid.uid.uidByte[i], HEX);
    }
    uid.toUpperCase();
    return uid;
}