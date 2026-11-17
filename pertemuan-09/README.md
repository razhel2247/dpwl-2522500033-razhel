# pertemuan-09
1. Deskripsi
Pada P7, checkout sudah membuat record pembayaran awal (DP) sebesar 30% dari total transaksi di tabel `t_bayar` dengan status `pending`. Namun, pengunggahan bukti transfer dan verifikasi admin belum ada.

P9 melanjutkan aplikasi yang sama (bukan aplikasi baru) dengan dua fitur:

1. Penyewa: melihat rincian DP, lalu mengunggah bukti transfer.
2. Admin: melihat daftar pembayaran, memeriksa bukti transfer, lalu menerima atau menolak pembayaran.

Fitur P7 (login, katalog, profil, keranjang, checkout) tetap dipertahankan.

 2. Fitur yang Ditambahkan

 2.1 Sisi Penyewa

- Setelah checkout berhasil, penyewa langsung diarahkan ke halaman pembayaran transaksi tersebut (pola PRG).
- Menu Pembayaran pada navbar penyewa menampilkan daftar pembayaran DP milik penyewa yang login.
- Halaman detail menampilkan rincian transaksi, kode pembayaran, nominal DP (30%, diambil dari `t_bayar`), metode pembayaran, dan status pembayaran.
- Formulir unggah bukti transfer dengan validasi jenis, MIME type, isi gambar, dan ukuran berkas.
- Bukti disimpan di uploads/bayar, lalu ft_byr dan tgl_wkt_byr diperbarui. Status tetap pending.
- Umpan balik (_flash message_) berhasil atau gagal ditampilkan kepada penyewa.

 2.2 Sisi Admin

- Menu baru **Pembayaran** pada sidebar admin.
- Daftar pembayaran yang menunggu verifikasi (yang sudah ada bukti diurutkan lebih dulu) dan riwayat yang sudah diverifikasi.
- Halaman detail menampilkan informasi penyewa, kode transaksi, nominal, waktu unggah, dan bukti transfer yang dapat dibuka.
- Tombol **Terima** dan **Tolak**.
- Perubahan status pembayaran dan transaksi dilakukan dalam satu transaksi database.

 2.3 Ketentuan Perubahan Status

| Keputusan admin     | `t_bayar.sttus_byr` | `t_sewa.status_sewa` | `t_bayar.tgl_wkt_ver`  |
| ------------------- | ------------------- | -------------------- | ---------------------- |
| Pembayaran diterima | `valid`             | `disetujui`          | diisi waktu verifikasi |
| Pembayaran ditolak  | `invalid`           | `ditolak`            | diisi waktu verifikasi |

Contoh record DP hasil checkout (P7):

| Kolom         | Nilai                            |
| ------------- | -------------------------------- |
| `kode_bayar`  | `PAY/20261028/01`                |
| `tgl_wkt_byr` | waktu pencatatan pembayaran      |
| `ft_byr`      | `NULL` (sampai bukti diunggah)   |
| `mtode_byr`   | `transfer`                       |
| `jns_byr`     | `dp`                             |
| `nominal`     | `855000`                         |
| `sttus_byr`   | `pending`                        |
| `tgl_wkt_ver` | `NULL` (sampai admin verifikasi) |
| `kode_sewa`   | `INV/20261028/01`                |

---

3. Aturan Bisnis yang Diterapkan

- Penyewa dan admin wajib login. Pengguna yang belum login diarahkan ke halaman autentikasi.
- Penyewa hanya dapat mengakses pembayaran milik transaksinya sendiri. Query selalu memfilter `user_plg`, dan respons "tidak ada" serta "milik orang lain" disamakan (403) agar tidak membocorkan data.
- Admin tidak dapat memakai proses unggah penyewa. Penyewa tidak dapat membuka menu verifikasi admin (403).
- Nominal tidak pernah dibaca dari formulir. Nilainya selalu berasal dari database.
- Bukti transfer wajib ada sebelum admin dapat memverifikasi.
- Pembayaran yang sudah diverifikasi (valid/invalid) tidak dapat diproses atau diunggah ulang.
- Penyewa boleh mengunggah ulang bukti selama status masih pending. Berkas lama dihapus hanya setelah database berhasil diperbarui.
- Proses pembayaran memperbarui record DP yang sudah dibuat saat checkout, bukan membuat record DP baru.

---

4. Route Baru

Ditambahkan pada `application/config/routes.php`. `kode_sewa` berformat `INV/yyyymmdd/nn` (mengandung `/`), sehingga dikirim lewat _query string_, mengikuti pola `transaksi/keranjang?kode_sewa=...`.

| Method | URL                                      | Controller::method                  | Akses   |
| ------ | ---------------------------------------- | ----------------------------------- | ------- |
| GET    | `/pembayaran`                            | `Pembayaran_controller::index`      | Penyewa |
| GET    | `/pembayaran/detail?kode_sewa=...`       | `Pembayaran_controller::detail`     | Penyewa |
| POST   | `/pembayaran/unggah`                     | `Pembayaran_controller::unggah`     | Penyewa |
| GET    | `/admin/pembayaran`                      | `Verifikasi_controller::index`      | Admin   |
| GET    | `/admin/pembayaran/detail?kode_sewa=...` | `Verifikasi_controller::detail`     | Admin   |
| POST   | `/admin/pembayaran/verifikasi`           | `Verifikasi_controller::verifikasi` | Admin   |

---

 5. Pembagian MVC

| Lapisan    | Berkas                                                                                         | Tanggung jawab                                                                                                                                                                                                      |
| ---------- | ---------------------------------------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| Model      | `application/models/Pembayaran_model.php`                                                      | Akses dan perubahan data `t_bayar` serta relasinya dengan `t_sewa`, `t_pilih`, `t_mobil`, `t_penyewa`. Semua query memakai _prepared statement_. Verifikasi memakai transaksi database dan `SELECT ... FOR UPDATE`. |
| View       | `application/views/pembayaran/daftar.php`, `application/views/pembayaran/detail.php`           | Daftar pembayaran penyewa, rincian DP, dan formulir unggah.                                                                                                                                                         |
| View       | `application/views/admin/verifikasi_view.php`, `application/views/admin/verifikasi_detail.php` | Daftar verifikasi, riwayat, detail, dan bukti transfer.                                                                                                                                                             |
| Controller | `application/controllers/Pembayaran_controller.php`                                            | Autentikasi penyewa, kepemilikan, validasi berkas, aturan bisnis, PRG.                                                                                                                                              |
| Controller | `application/controllers/Verifikasi_controller.php`                                            | Autentikasi admin, validasi keputusan, PRG.                                                                                                                                                                         |

### Berkas yang Berubah / Ditambahkan di P9

| Berkas                                              | Status      | Keterangan                                         |
| --------------------------------------------------- | ----------- | -------------------------------------------------- |
| `application/controllers/Pembayaran_controller.php` | Baru        | Pembayaran dan unggah bukti oleh penyewa           |
| `application/controllers/Verifikasi_controller.php` | Baru        | Verifikasi pembayaran oleh admin                   |
| `application/models/Pembayaran_model.php`           | Baru        | Model pembayaran + `PembayaranException`           |
| `application/views/pembayaran/daftar.php`           | Baru        | Daftar pembayaran penyewa                          |
| `application/views/pembayaran/detail.php`           | Baru        | Detail DP dan formulir unggah                      |
| `application/views/admin/verifikasi_view.php`       | Baru        | Daftar verifikasi admin                            |
| `application/views/admin/verifikasi_detail.php`     | Baru        | Detail verifikasi admin                            |
| `application/config/routes.php`                     | Diubah      | Route P9                                           |
| `application/controllers/Transaksi_controller.php`  | Diubah      | Setelah checkout, redirect ke `pembayaran/detail`  |
| `application/views/templates/sidebar.php`           | Diubah      | Menu **Pembayaran** admin                          |
| `application/views/katalog/navbar.php`              | Diubah      | Menu **Pembayaran** penyewa                        |
| `application/views/transaksi/konfirmasi.php`        | Diubah      | Petunjuk unggah bukti DP                           |
| `uploads/bayar/`                                    | Folder baru | Dibuat otomatis saat unggah pertama bila belum ada |

---

 6. Keamanan Unggah Berkas

Validasi dilakukan berlapis pada `Pembayaran_controller::simpanBerkas()`:

1. Pemeriksaan kode error unggahan (`UPLOAD_ERR_*`) dan deteksi `post_max_size` terlampaui.
2. `is_uploaded_file()` untuk memastikan berkas benar-benar hasil unggahan.
3. **Ukuran** maksimal **2 MB**.
4. **Ekstensi** hanya `jpg`, `jpeg`, `png`, `webp`.
5. **MIME type** nyata via `finfo` (`image/jpeg`, `image/png`, `image/webp`).
6. Isi berkas diverifikasi sebagai gambar dengan `getimagesize()`.
7. **Nama berkas dibuat server**: `ft_byr_YYYYMMDDHHMMSS_<16 hex acak>.<ext>`. Nama asli pengguna tidak dipakai, sehingga tidak bentrok dan tidak dapat dimanipulasi.
8. Penghapusan berkas hanya untuk nama yang cocok pola buatan server (anti _path traversal_).

Keamanan lain: _prepared statement_ untuk seluruh query berparameter, pembatasan method HTTP (405), `htmlspecialchars` pada output view, dan Post-Redirect-Get pada semua pengiriman formulir.

---

 7. Cara Menjalankan

 Prasyarat

- Laragon (Apache + MySQL) dan PHP 8.4 aktif.
- Ekstensi PHP `mysqli` dan `fileinfo` aktif.
- `mod_rewrite` Apache aktif.

 Langkah

1. Salin folder aplikasi ke `C:\laragon\www\dpwl-2522500033\`.
2. Buat atau impor database, lalu sesuaikan `application/config/database.php`:
   ```php
   $db = new mysqli('localhost', 'root', '', 'NAMA_DATABASE_ANDA', 3306);
   ```
   > Pada berkas bawaan nama database masih `db_dpwl_nama123`. Ganti sesuai database Anda.
3. Pastikan `base_url` pada `application/config/config.php` sesuai:
   ```php
   $config['base_url'] = 'http://localhost/dpwl-2522500033/';
   ```
   dan `RewriteBase` pada `.htaccess` adalah `/dpwl-2522500033/`.
4. Pastikan folder `uploads/bayar/` ada dan dapat ditulis (dibuat otomatis jika belum ada).
5. Buka `http://localhost/dpwl-2522500033/`.

Prasyarat Database

Kolom berikut harus dapat menyimpan nilai baru P9. Jika berupa `ENUM`, pastikan definisinya memuat nilainya:

| Kolom                | Nilai yang diperlukan                           |
| -------------------- | ----------------------------------------------- |
| `t_bayar.sttus_byr`  | `pending`, `valid`, `invalid`                   |
| `t_sewa.status_sewa` | `keranjang`, `diajukan`, `disetujui`, `ditolak` |

Model memiliki pengaman: jika nilai hasil verifikasi tidak tersimpan sesuai (misalnya ENUM belum memuat nilai baru), transaksi database dibatalkan (_rollback_).

 Alur Uji Singkat

1. Login sebagai penyewa → pilih kendaraan → keranjang → **checkout**.
2. Sistem membuka halaman pembayaran DP → unggah bukti transfer.
3. Login sebagai admin → menu **Pembayaran** → buka detail → **Terima** atau **Tolak**.
4. Periksa `t_bayar` dan `t_sewa` pada database.

---

 8. Hasil Pengujian Fungsional

> Isi kolom **Hasil** (Berhasil / Gagal) dan **Catatan** setelah Anda menjalankan setiap skenario pada aplikasi Anda. Periksa juga konsistensi tabel `t_sewa` dan `t_bayar` setelah setiap proses.

| No  | Skenario Pengujian                                             | Hasil yang Diharapkan                                | Hasil | Catatan |
| --- | -------------------------------------------------------------- | ---------------------------------------------------- | ----- | ------- |
| 1   | Penyewa membuka halaman pembayaran setelah checkout            | Informasi transaksi dan DP ditampilkan               |       |         |
| 2   | Penyewa membuka pembayaran milik akun lain                     | Akses ditolak                                        |       |         |
| 3   | Penyewa mengunggah bukti transfer yang valid                   | Berkas tersimpan dan record pembayaran diperbarui    |       |         |
| 4   | Penyewa mengunggah berkas dengan jenis tidak diizinkan         | Sistem menolak berkas                                |       |         |
| 5   | Penyewa mengunggah berkas melebihi batas ukuran                | Sistem menolak berkas                                |       |         |
| 6   | Penyewa mengirim formulir tanpa memilih berkas                 | Sistem menampilkan pesan kesalahan                   |       |         |
| 7   | Penyewa melihat pembayaran setelah mengunggah bukti            | Status tetap `pending`                               |       |         |
| 8   | Admin membuka daftar verifikasi                                | Pembayaran `pending` ditampilkan                     |       |         |
| 9   | Admin melihat bukti transfer                                   | Bukti pembayaran dapat diperiksa                     |       |         |
| 10  | Admin menerima pembayaran                                      | Status pembayaran `valid` dan transaksi `disetujui`  |       |         |
| 11  | Admin menolak pembayaran                                       | Status pembayaran `invalid` dan transaksi `ditolak`  |       |         |
| 12  | Admin mencoba memverifikasi pembayaran tanpa bukti             | Sistem menolak proses                                |       |         |
| 13  | Admin mencoba memverifikasi pembayaran yang sudah diproses     | Sistem menolak verifikasi berulang                   |       |         |
| 14  | Penyewa mencoba mengakses menu verifikasi admin                | Akses ditolak                                        |       |         |
| 15  | Pengguna yang belum login mencoba mengakses halaman pembayaran | Sistem mengarahkan ke autentikasi atau menolak akses |       |         |

### Perbaikan yang Dilakukan Selama Pengujian

| Temuan           | Perbaikan        |
| ---------------- | ---------------- |
| _(isi bila ada)_ | _(isi bila ada)_ |

---

 9. Tangkapan Layar

Simpan gambar pada folder `pertemuan-09/screenshots/` lalu sesuaikan tautan di bawah.

| Halaman                                                   | Tangkapan Layar                                             |
| --------------------------------------------------------- | ----------------------------------------------------------- |
| Halaman pembayaran penyewa (rincian DP + formulir unggah) | `![pembayaran-penyewa](screenshots/pembayaran-penyewa.png)` |
| Daftar verifikasi admin                                   | `![verifikasi-daftar](screenshots/verifikasi-daftar.png)`   |
| Detail verifikasi admin (bukti transfer)                  | `![verifikasi-detail](screenshots/verifikasi-detail.png)`   |
| Hasil pembayaran **diterima**                             | `![hasil-diterima](screenshots/hasil-diterima.png)`         |
| Hasil pembayaran **ditolak**                              | `![hasil-ditolak](screenshots/hasil-ditolak.png)`           |

---

 10. Penggunaan AI

AI digunakan sebagai alat bantu untuk menganalisis source code P7, merancang Model–View–Controller pembayaran, memeriksa keamanan dan konsistensi proses, serta menyusun skenario pengujian dan README ini. Seluruh kode dibaca, dipahami, diperiksa, dan diuji sendiri pada aplikasi sebelum diterapkan.

---

11. Riwayat Git

Pengembangan dicatat bertahap, contoh pesan commit:

```
Membuat halaman pembayaran penyewa P9
Menambahkan fitur upload bukti pembayaran P9
Membuat verifikasi pembayaran admin P9
Menguji dan memperbaiki proses pembayaran P9
```

---

 12. Kriteria Penyelesaian

- [ ] Fitur P7 tetap berfungsi dengan baik
- [ ] Penyewa dapat melihat informasi pembayaran DP
- [ ] Penyewa dapat mengunggah bukti pembayaran
- [ ] Bukti pembayaran tersimpan pada `uploads/bayar/`
- [ ] Data pembayaran diperbarui dengan benar
- [ ] Admin dapat melihat dan memeriksa bukti pembayaran
- [ ] Admin dapat menerima atau menolak pembayaran
- [ ] Status pembayaran dan transaksi diperbarui secara konsisten
- [ ] Pengendalian akses admin dan penyewa berjalan sesuai hak akses
- [ ] Model, View, dan Controller terintegrasi secara terstruktur
- [ ] Skenario pengujian valid, tidak valid, dan kasus batas telah dilakukan
- [ ] README.md dan histori Git/GitHub telah diperbarui
