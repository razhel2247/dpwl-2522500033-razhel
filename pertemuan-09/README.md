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