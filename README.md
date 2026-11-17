# Misi Koding

Web belajar coding berbasis misi ala Duolingo untuk pemula dari nol. Project akhir mata kuliah Framework PHP (CodeIgniter 4).

User menempuh 50 level berurutan di 5 bab: HTML, CSS, JavaScript, SQL, dan PHP. Setiap level dibuka dengan kartu materi singkat lalu kuis acak. Level 5 dan 10 di setiap bab adalah **boss**: tantangan menulis kode di sandbox browser, lengkap dengan AI tutor saat gagal.

## Fitur

| Fitur | Keterangan |
| --- | --- |
| Akun | Daftar/masuk (Shield), mode tamu, upgrade tamu ke akun biasa di baris user yang sama |
| Kuis | 10 soal acak dari bank soal per level, pilihan ganda dan susun kata, clue setelah salah, dinilai di server |
| Boss | Editor CodeMirror + sandbox: iframe (HTML/CSS/JS), sql.js (SQL), php-wasm (PHP), test case, 2 variasi per boss |
| AI tutor | Lewat 9Router lokal (OpenAI-compatible), petunjuk manual/otomatis, kode jawaban dibatasi, fallback kalau AI mati |
| Gamifikasi | Hearts + isi ulang tanpa cron, bintang terbaik per level, syarat buka bab, leaderboard, portofolio |
| Sertifikat | PDF (Dompdf) ber-kode unik + halaman verifikasi publik `/verifikasi/KODE` |
| Admin | Semua level terbuka untuk akun admin; panel dapat menambah, mengedit, mencari, menduplikasi, dan menghapus soal kuis |
| Pendukung | Peta belajar, perpustakaan materi, tutorial pertama kali, halaman bantuan, tampilan HP |

## Prasyarat

- PHP 8.2+ dengan ekstensi `intl`, `mbstring`, `mysqli`, `curl`, `gd` (untuk Dompdf)
- Composer
- MySQL 8 atau MariaDB 10.6+

Library browser (CodeMirror, sql.js, php-wasm) sudah ada di `public/vendor/`, jadi tidak perlu internet saat demo.

## Setup

```bash
git clone <repo> belajar-coding && cd belajar-coding
composer install
cp .env.example .env          # lalu isi bagian database
```

Buat database:

```sql
CREATE DATABASE belajar_coding CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
```

Buat tabel dan isi data:

```bash
php spark migrate --all              # tabel Shield + 13 tabel aplikasi
php spark db:seed DatabaseSeeder     # 5 bab, 50 level, semua konten soal & tantangan
php spark db:seed DemoSeeder         # (opsional) akun demo + isi leaderboard
php spark db:seed TestAccountsSeeder # (opsional) akun uji user dan admin
php spark serve                      # http://localhost:8080
```

### Akun demo (dari `DemoSeeder`)

| Email | Password | Kondisi |
| --- | --- | --- |
| `demo@misikoding.test` | `demo12345` | Tamat 50 level, 150 bintang, punya sertifikat dan portofolio |
| `siswa@misikoding.test` | `siswa12345` | Baru menyelesaikan level 1-4, berikutnya mini boss |

Akun demo bisa mengulang level mana pun tanpa memotong hearts, jadi cocok untuk menunjukkan sandbox SQL dan PHP.

### Akun pengujian

Buat akun berikut di lingkungan development dengan `php spark db:seed TestAccountsSeeder`:

| Peran | Email | Kata sandi |
| --- | --- | --- |
| User | `user.test@misikoding.test` | `UserTest!2026` |
| Admin | `admin.test@misikoding.test` | `AdminTest!2026` |

Akun admin dapat membuka panel `/admin`. Seeder aman dijalankan ulang: akun yang sudah ada tidak dihapus atau diubah password-nya, dan akun admin akan dipastikan berada di grup `admin`. Seeder menolak dijalankan saat `CI_ENVIRONMENT=production`. Gunakan kredensial ini hanya untuk pengujian lokal.

### Saat presentasi

Isi ulang heart default 5 menit. Supaya cepat terlihat, tulis di `.env`:

```
game.heartRefillSeconds = 60
```

## AI tutor (9Router)

AI hanya dipanggil dari server (controller `Api\Boss::hint` → `AiTutorService`), tidak pernah dari JavaScript. Atur di `.env`:

```
game.aiBaseUrl = 'http://127.0.0.1:20128/v1'
game.aiApiKey  = ''
game.aiModel   = 'nama-model-di-9router'
```

Cek endpoint dengan:

```bash
curl http://127.0.0.1:20128/v1/chat/completions \
  -H "Content-Type: application/json" \
  -d '{"model":"nama-model","messages":[{"role":"user","content":"halo"}]}'
```

Kalau 9Router mati atau salah alamat, halaman boss tetap jalan dan menampilkan "Petunjuk sedang tidak tersedia".

## Struktur penting

```
app/
  Config/Game.php              semua angka aturan main (bisa ditimpa lewat .env, prefix game.)
  Controllers/                 Home, Account, Map, Level, Library, Leaderboard, Portfolio,
                               Certificate, Help, Auth/RegisterController, Api/Quiz, Api/Boss
  Services/                    ProgressService (bintang, hearts), CurriculumService (peta & kunci level),
                               QuizService, BossService, RankService, AiTutorService
  Database/Content/*.json      konten per bab (kartu materi, soal, tantangan)
  Database/Seeds/              ChapterLevelSeeder, ContentSeeder, DemoSeeder, TestAccountsSeeder
  Filters/AiThrottle.php       batas panggilan AI per user per menit
  Views/                       layouts, partials, dan halaman per fitur
  Commands/GuestCleanup.php    php spark guest:cleanup
public/
  assets/css/app.css           design system (token warna di :root, mode gelap otomatis)
  assets/img/                  icons.svg (sprite ikon), logo.svg, kodi.svg (maskot)
  assets/js/                   app.js, quiz.js, boss.js, runners.js, sql-worker.js, php-worker.mjs
  vendor/                      codemirror, sqljs, php-wasm (disimpan lokal, bukan CDN)
tests/                         unit (aturan main) dan database (alur kuis, buka level, cleanup tamu)
```

## Menambah atau mengubah konten

Konten ada di `app/Database/Content/<urutan>-<bab>.json`. Setiap level berisi `cards`, `questions`, dan (untuk boss) `challenges`. Teks panjang boleh ditulis sebagai array baris.

```json
{ "type": "mcq", "prompt": "Tag untuk paragraf?", "options": ["<p>", "<para>"], "answer": 0,
  "clue": "Huruf pertama kata paragraf.", "explanation": "Paragraf ditulis dengan <p>." }

{ "type": "arrange", "prompt": "Susun paragraf Halo", "answer": ["<p>", "Halo", "</p>"],
  "distractors": ["<p/>"], "clue": "...", "explanation": "..." }
```

Format test case boss:

| Runner | Contoh |
| --- | --- |
| iframe | `{ "desc": "Ada <h1>", "check": "!!document.querySelector('h1')" }` (ekspresi JS, `__logs` berisi isi console) |
| sql.js | `{ "desc": "...", "type": "result", "columns": ["nama"], "expect": [["Andi"]], "ordered": true }` atau `"type": "query"` untuk mengecek tabel setelah query user |
| php-wasm | `{ "type": "output", "match": "exact", "expect": "..." }` atau `{ "type": "php", "check": "$total === 10" }` |

Setelah mengubah JSON, jalankan `php spark db:seed ContentSeeder`. Konten level yang ada di JSON diganti total, dan sesi kuis aktif di level itu ditutup.

Status konten saat ini: Bab HTML dan CSS lengkap 10 level. Bab JavaScript, SQL, dan PHP lengkap level 1-5 (termasuk mini boss), level 6-10 masih berlabel "Konten segera hadir".

## Pengujian

```bash
vendor/bin/phpunit
```

Test memakai SQLite in-memory (grup `tests` di `app/Config/Database.php`), jadi tidak menyentuh database utama.

## Keamanan

- Kunci jawaban (`question_options.is_correct`, `correct_position`) dan `challenges.solution_code` tidak pernah dikirim ke browser. Query untuk browser memilih kolom eksplisit.
- Kuis dinilai di server. Progres per soal disimpan di server, jadi refresh tidak mengacak ulang soal atau mengembalikan hearts.
- Filter CSRF global aktif. Request `fetch()` mengirim token lewat header `X-CSRF-TOKEN`.
- Semua output data lewat `esc()`. Di JavaScript, data dimasukkan dengan `textContent` (tanpa `innerHTML`).
- Kode user HTML/CSS/JS berjalan di iframe `sandbox="allow-scripts"` tanpa `allow-same-origin`. SQL dan PHP berjalan di Web Worker yang dimatikan otomatis kalau terlalu lama (infinite loop).
- Endpoint AI dibatasi throttler. Petunjuk AI manual tidak boleh berisi kode, dan blok kode panjang di penjelasan otomatis disembunyikan di server.

## Keterbatasan yang disadari

- Test case boss dijalankan di browser, sehingga secara teori bisa dimanipulasi. Solusi masa depan: code runner terisolasi di server.
- php-wasm berukuran sekitar 12 MB dan diunduh sekali saat pertama membuka boss PHP.
- Login Google belum dibuat (tabel `user_social_accounts` sudah tersedia).
- Lulus boss di submit ke-4 (setelah 3 kali gagal) sementara diberi 2 bintang (`ProgressService::bossStars`).

## Untuk tim UI/UX

- Warna, radius, dan font diatur lewat token CSS di bagian atas `public/assets/css/app.css`. Mode gelap mengikuti setelan sistem.
- Ikon ada di `public/assets/img/icons.svg` (sprite). Pakai di view dengan `<?= icon('heart') ?>`.
- Nama aplikasi diatur di `app/Config/Game.php` (`$appName`).
- Belum memakai framework CSS, jadi bisa diganti ke Bootstrap atau Tailwind sesuai keputusan tim.
