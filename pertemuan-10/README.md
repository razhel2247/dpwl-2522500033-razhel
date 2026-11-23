# pertemuan-10
1. Memverifikasi Perubahan Struktur Basis Data perintah penambahan nilai lunas pada ENUM kolom status_sewa
2. Mengimplementasikan Model Pelunasan pada application\models\Pembayaran_model.php
* menambahkan method getDaftarPelunasan() 
* menambahkan method getDetailPelunasan(string $kodeSewa)
* menambahkan method internal buatKodeBayarPelunasan(string $tanggalKode)
* Validasi pelunasan dilakukan di dalam method simpanPelunasan() sebelum data pembayaran disimpan.
Tambahkan method simpanPelunasan(string $kodeSewa, string $metodeBayar, ?string $namaFileBukti =
null)
* melakukan pemeriksaan awal terhadap Model Pelunasan