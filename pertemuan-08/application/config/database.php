<?php

$db = new mysqli(
  'localhost',
  'root',
  '',
  'db_dpwl_nama123',
  3306
);

if ($db->connect_errno) {
  http_response_code(500);
  exit('Koneksi basis data gagal.');
}

$db->set_charset('utf8mb4');