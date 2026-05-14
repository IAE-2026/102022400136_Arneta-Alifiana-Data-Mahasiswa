# AI Prompting Log
Service A - Data Mahasiswa Service
BBK2HAB3 - Integrasi Aplikasi Enterprise

## Tool yang Digunakan
- Claude AI (claude.ai)

## Log Prompting

### 1. Perencanaan Project
**Prompt:** Membuat implementation plan untuk Service A Data Mahasiswa menggunakan Laravel, MySQL, dan Docker

**Hasil:** Mendapatkan full implementation plan termasuk arsitektur, schema database, endpoint API, dan docker setup

---

### 2. Membuat Migration Database
**Prompt:** Membuat migration untuk tabel mahasiswas dengan field nim, nama, email, prodi, angkatan, status

**Hasil:** File migration berhasil dibuat dengan struktur tabel yang sesuai

---

### 3. Membuat API Key Middleware
**Prompt:** Membuat middleware untuk autentikasi menggunakan API Key di request header X-API-KEY

**Hasil:** ApiKeyMiddleware berhasil dibuat dan didaftarkan di bootstrap/app.php

---

### 4. Membuat REST API Controller
**Prompt:** Membuat MahasiswaController dengan 3 endpoint: GET semua mahasiswa, GET by NIM, POST tambah mahasiswa beserta Swagger documentation

**Hasil:** Controller berhasil dibuat dengan response format JSON yang konsisten

---

### 5. Setup Docker
**Prompt:** Membuat Dockerfile dan docker-compose.yml untuk menjalankan Laravel + MySQL

**Hasil:** Docker berhasil berjalan di port 8001

---

### 6. Setup Swagger
**Prompt:** Mengkonfigurasi L5-Swagger agar bisa generate dokumentasi API

**Hasil:** Swagger UI berhasil diakses di /api/documentation

---

### 7. Setup GraphQL
**Prompt:** Menginstall Lighthouse dan membuat schema GraphQL untuk query data mahasiswa

**Hasil:** GraphQL Playground berhasil diakses dan query data mahasiswa berhasil dijalankan