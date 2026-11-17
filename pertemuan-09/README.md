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