# pertemuan-11
# P11 – Pemenuhan atau Penyerahan Objek Transaksi dengan MVC
## A. Dokumentasi Persiapan Baseline P10
### A.1. Workspace dan Artefak P10
Artefak P10 telah disalin ke workspace `dpwl-nim\` sebagai dasar pengembangan P11. Pengembangan
dilakukan secara kumulatif dengan mempertahankan struktur dan fitur aplikasi sebelumnya.
### A.2. Struktur Aplikasi dan Basis Data
Struktur aplikasi homemade MVC dan basis data MySQL telah diperiksa. Tabel yang digunakan dalam
pengembangan P11 meliputi `t_sewa`, `t_pilih`, `t_mobil`, `t_bayar`, dan `t_penyewa`.
A.3. Pemeriksaan Fitur P10
Fitur transaksi dan pembayaran P10 telah diperiksa melalui aplikasi. Hasil pemeriksaan:
- semua fitur pada P10 berhasil diuji.
- Tidak ada kendala yang ditemukan.
- Tidak ada perbaikan yang dilakukan.
B. Analisis Persyaratan Serah Terima Kendaraan
P11 mengembangkan proses serah terima kendaraan setelah transaksi disetujui dan pembayaran DP
diverifikasi valid. Sebelum penyerahan dikonfirmasi, admin harus memastikan data penyewa, dokumen
KTP dan SIM A, serta detail kendaraan tersedia.
Proses serah terima menggunakan tabel `t_sewa`, `t_bayar`, `t_penyewa`, `t_pilih`, dan `t_mobil`.
Sistem mencatat waktu aktual penyerahan pada `t_sewa.wkt_ambil` dan mengubah `t_sewa.status_sewa`
menjadi `aktif`. Status kendaraan tetap `disewa` dan tidak dibuat pembayaran baru.
Implementasi direncanakan menggunakan pemisahan tanggung jawab MVC: Model mengelola pengambilan dan
penyimpanan data, Controller menangani alur permintaan dan validasi proses, sedangkan View
menyajikan informasi serta formulir serah terima.
Analisis ini menjadi dasar implementasi pada bagian C sampai dengan F.git