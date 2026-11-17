# Flow project — UML Misi Koding

| Kendali dokumen | Nilai |
| --- | --- |
| ID dokumen | MK-FLOW-001 |
| Versi | 1.0 |
| Tanggal | 17 November 2026 |
| Status | Draft dokumentasi teknis untuk ditinjau |
| Cakupan | Misi Koding — autentikasi, materi, kuis, boss, progres, sertifikat, dan administrasi konten |
| Sumber kebenaran | Kode pada repository ini |

Dokumentasi ini menggambarkan implementasi repository pada tanggal di atas. Setiap file `.xml` adalah diagram **diagrams.net / draw.io XML** yang dapat dibuka atau diedit melalui diagrams.net (`File → Import From → Device`). XML ini menyimpan diagram presentasi UML; bukan ekspor XMI untuk pertukaran model antar-tool UML.

Logo maskot pada setiap diagram di-embed dari `../Logo.png` yang tersedia di folder `asset project`, sehingga tetap tampil ketika file XML dipindahkan atau dibuka mandiri.

## Daftar 14 diagram UML

1. `01-use-case.xml` — aktor siswa/admin dan layanan utama.
2. `02-class.xml` — entitas domain serta service.
3. `03-sequence-quiz.xml` — urutan interaksi menjawab kuis.
4. `04-activity-kuis.xml` — alur aktivitas kuis, retry, reveal, dan bintang.
5. `05-state-attempt.xml` — status attempt belajar/boss dan replay.
6. `06-component.xml` — komponen UI, CodeIgniter, database, dan AI.
7. `07-deployment.xml` — node runtime dan koneksi.
8. `08-package.xml` — pengelompokan lapisan kode.
9. `09-object.xml` — contoh snapshot object satu attempt boss.
10. `10-communication.xml` — komunikasi permintaan petunjuk AI.
11. `11-timing-hearts.xml` — pemulihan hearts tanpa cron.
12. `12-interaction-overview.xml` — overview login sampai alur belajar.
13. `13-composite-structure.xml` — struktur internal halaman boss dan runner.
14. `14-profile.xml` — stereotipe entity, service, boundary, dan external.

## SOP dokumentasi

- Gunakan nama dan tanggung jawab yang benar-benar ada pada `app/Controllers`, `app/Services`, `app/Models`, `app/Config/Routes.php`, serta `public/assets/js`.
- Panah menunjukkan arah kontrol atau data; label menunjukkan pemicu, kondisi, atau tujuan.
- Istilah `QuizService`, `BossService`, dan `ProgressService` dipertahankan sesuai kode.
- Tampilkan kondisi keputusan dan jalur gagal/ulang ketika berdampak pada progres pengguna.
- Catat perilaku yang dibatasi pada browser sesuai implementasi (test case boss dievaluasi di browser).
- Saat kode berubah, tinjau diagram terdampak, naikkan versi dokumen, dan perbarui tanggal.
- Jangan menandai dokumen sebagai disahkan sebelum peninjauan pemilik proyek.

## Batas interpretasi

Diagram merekam perilaku yang sudah diterapkan, bukan rancangan fitur yang belum tersedia. Status konten dan batasan teknis mengikuti `README.md` di root proyek. Belum ada SOP internal terpisah yang diberikan, sehingga aturan di atas menjadi konvensi dokumentasi untuk paket ini.
