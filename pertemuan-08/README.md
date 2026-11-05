# pertemuan-08
1. Menu Navigasi
Saya menambahkan menu Manajemen Penyewa pada sidebar admin dengan menambah satu item <li> di application/views/templates/sidebar.php, tepat di bawah menu Manajemen Kendaraan. Tautannya mengarah ke site_url('admin/penyewa') dan memakai ikon fa-id-card. Item menu lain tidak saya ubah Agar UR
2. Formulir dan Tampilan Data
application\views\admin\mpenyewa_view.php
Formulir. saya Memakai enctype="multipart/form-data" agar dapat mengunggah foto. Isinya terbagi tiga: Akun (username, password, konfirmasi password), Identitas (NIK, nama, nomor HP, alamat), dan Dokumen (foto KTP dan SIM A).
Menampilkan seluruh data pada t_penyewa: nama lengkap beserta username, NIK, nomor HP, alamat (dipotong 60 karakter, teks lengkap muncul saat kursor diarahkan), serta status foto KTP dan SIM A Kolom sandi sengaja tidak ditampilkan Tabel memakai kelas js-data-table sehingga mendapat pencarian, pengurutan, dan paginasi dari DataTables. Setiap baris punya tombol Ubah (tautan) dan Hapus (formulir POST dengan konfirmasi).
3. Implementasi CRUD Berbasis MVC
application\models\Mpenyewa_model.php
Saya memisahkan tanggung jawab ke tiga berkas:
Model (application/models/Mpenyewa_model.php) hanya berisi query SQL. Seluruhnya memakai MySQLi dengan prepared statement (prepare, bind_param, execute). Method-nya: getAll(), findByUserPlg(), nikSudahDipakai(), jumlahSewa(), create(), update(), dan delete().
View (mpenyewa_view.php) hanya menampilkan data, dan semua keluarannya melewati htmlspecialchars().
Controller (application/controllers/Mpenyewa_controller.php) memeriksa login, memvalidasi input, mengelola foto, memanggil Model, lalu memilih View.
4. Validasi, Pengamanan, dan Pengelolaan Foto
di controller
Semua input dirapikan dengan trim(). Jika ada yang tidak valid, formulir tampil kembali dengan pesan kesalahan dan isian sebelumnya, kecuali password
Penanganan kegagalan: jika foto kedua gagal diunggah, foto pertama yang sudah tersimpan dihapus lagi. Jika penyimpanan ke database gagal, foto baru dibuang. Saat foto diganti, foto lama baru dihapus setelah database berhasil diperbarui. Saat penyewa dihapus, kedua fotonya ikut dihapus. Fungsi penghapus hanya menerima nama file berpola buatan sistem, sehingga tidak bisa dipakai menghapus file lain
5. Pengujian
fungsi semua berjalan dengan baik.
 

