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