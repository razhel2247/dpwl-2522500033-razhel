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

 