<?php
// Nama Berkas - application\config\routes.php
$route = [];
// Route P2/P3 - dipertahankan, penyesuaian terhadap penulisan nama Controller.
$route['default_controller'] = 'Home';
$route['info/(:any)'] = 'Home/info/$1';
$route['login'] = 'Auth/login';
$route['auth/login'] = 'Auth/login';
$route['auth/logout'] = 'Auth/logout';
$route['admin'] = 'Admin/index';
// Route P4: semua proses CRUD admin dipindahkan di URL admin/madmin.
$route['admin/madmin'] = 'Madmin_controller/index';
$route['admin/madmin/tambah'] = 'Madmin_controller/tambah';
$route['admin/madmin/ubah/(:any)'] = 'Madmin_controller/ubah/$1';
$route['admin/madmin/hapus/(:any)'] = 'Madmin_controller/hapus/$1';
// Route P5
// dari href(menu), diproses ke Mobil_controller dengan mengerjakan method index() yang memanggiladmin\mobil_view
$route['admin/mobil'] = 'Mobil_controller/index';
$route['admin/mobil/tambah'] = 'Mobil_controller/tambah';
$route['admin/mobil/ubah/(:any)'] = 'Mobil_controller/ubah/$1';
$route['admin/mobil/hapus/(:any)'] = 'Mobil_controller/hapus/$1';
// Route P6
$route['katalog'] = 'Katalog_controller/index';
$route['katalog/detail/(:any)'] = 'Katalog_controller/detail/$1';
$route['auth/register'] = 'Auth/register';
$route['auth/login-penyewa'] = 'Auth/loginPenyewa';
$route['auth/logout-penyewa'] = 'Auth/logoutPenyewa';
$route['penyewa/profil'] = 'Penyewa_controller/profil';
$route['default_controller'] = 'Katalog_controller';
//Route P7 - Transaksi dan Keranjang
$route['transaksi/tambah'] = 'Transaksi_controller/tambah';
$route['transaksi/keranjang'] = 'Transaksi_controller/keranjang';
$route['transaksi/perbarui-periode'] = 'Transaksi_controller/perbaruiPeriode';
$route['transaksi/hapus'] = 'Transaksi_controller/hapus';
$route['transaksi/hapus/(:any)'] = 'Transaksi_controller/hapus/$1';
$route['transaksi/checkout'] = 'Transaksi_controller/checkout';

// UTS - Manajemen Penyewa (Admin)
$route['admin/penyewa'] = 'Mpenyewa_controller/index';
$route['admin/penyewa/tambah'] = 'Mpenyewa_controller/tambah';
$route['admin/penyewa/ubah/(:any)'] = 'Mpenyewa_controller/ubah/$1';
$route['admin/penyewa/hapus/(:any)'] = 'Mpenyewa_controller/hapus/$1';

// Route P9 - Pembayaran DP penyewa dan verifikasi admin
// kode_sewa berformat INV/yyyymmdd/nn (mengandung "/"), sehingga dikirim lewat query string,
// mengikuti pola transaksi/keranjang?kode_sewa=...
$route['pembayaran'] = 'Pembayaran_controller/index';
$route['pembayaran/detail'] = 'Pembayaran_controller/detail';
$route['pembayaran/unggah'] = 'Pembayaran_controller/unggah';
$route['admin/pembayaran'] = 'Verifikasi_controller/index';
$route['admin/pembayaran/detail'] = 'Verifikasi_controller/detail';
$route['admin/pembayaran/verifikasi'] = 'Verifikasi_controller/verifikasi';

$route['admin/pembayaran/pelunasan'] = 'Pembayaran_controller/pelunasanIndex';
$route['admin/pembayaran/pelunasan/proses/(:any)'] = 'Pembayaran_controller/prosesPelunasan/$1';
$route['admin/pembayaran/dokumen/(:any)'] = 'Pembayaran_controller/dokumenTransaksi/$1';
$route['admin/pembayaran/pelunasan/(:any)'] = 'Pembayaran_controller/pelunasanDetail/$1';