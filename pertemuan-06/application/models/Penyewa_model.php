<?php
class Penyewa_model
{
  private mysqli $db;
  public function __construct()
  {
    global $db;
    $this->db = $db;
  }
  public function findByUserPlg(string $userPlg): ?array
  {
    $sql = "SELECT user_plg
FROM t_penyewa
WHERE user_plg = ?
LIMIT 1";
    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
      throw new RuntimeException(
        'Pemeriksaan username penyewa gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $userPlg);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Pemeriksaan username penyewa gagal dijalankan.'
      );
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
  }
  public function create(array $data): bool
  {
    $sql = "INSERT INTO t_penyewa (
user_plg,
sandi,
nik,
nm_lngkp,
no_hp,
almt_lngkp,
ft_ktp,
ft_sim_a
) VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)";
    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
      throw new RuntimeException(
        'Query registrasi penyewa gagal disiapkan.'
      );
    }
    $stmt->bind_param(
      'ssssss',
      $data['user_plg'],
      $data['sandi'],
      $data['nik'],
      $data['nm_lngkp'],
      $data['no_hp'],
      $data['almt_lngkp']
    );
    $success = $stmt->execute();
    $stmt->close();
    return $success;
  }
  // P6: untuk login penyewa
  public function findForLogin(string $userPlg): ?array
  {
    $sql = "SELECT user_plg, sandi, nm_lngkp
FROM t_penyewa
WHERE user_plg = ?
LIMIT 1";
    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
      throw new RuntimeException(
        'Query pencarian akun penyewa gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $userPlg);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Pencarian akun penyewa gagal dijalankan.'
      );
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
  }
  // P6: mengambil profil penyewa.
  public function getProfil(string $userPlg): ?array
  {
    $sql = "SELECT
user_plg,
nik,
nm_lngkp,
no_hp,
almt_lngkp,
ft_ktp,
ft_sim_a
FROM t_penyewa
WHERE user_plg = ?
LIMIT 1";
    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
      throw new RuntimeException(
        'Query profil penyewa gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $userPlg);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Pengambilan profil penyewa gagal dijalankan.'
      );
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row ?: null;
  }
  // P6: verifikasi password
  public function verifikasiPassword(
    string $userPlg,
    string $passwordSaatIni
  ): bool {
    $sql = "SELECT sandi
FROM t_penyewa
WHERE user_plg = ?
LIMIT 1";
    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
      throw new RuntimeException(
        'Query verifikasi password gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $userPlg);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Verifikasi password gagal dijalankan.'
      );
    }
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $stmt->close();
    return $row !== null
      && password_verify($passwordSaatIni, $row['sandi']);
  }
  // P6: memperbarui data profil dan referensi foto.
  public function updateProfil(
    string $userPlg,
    string $nik,
    string $nama,
    string $noHp,
    string $alamat,
    ?string $ftKtp,
    ?string $ftSimA,
    ?string $passwordHash = null
  ): bool {
    if ($passwordHash !== null) {
      $sql = "UPDATE t_penyewa
SET nik = ?,
nm_lngkp = ?,
no_hp = ?,
almt_lngkp = ?,
ft_ktp = ?,
ft_sim_a = ?,
sandi = ?
WHERE user_plg = ?";
      $stmt = $this->db->prepare($sql);
      if (!$stmt) {
        throw new RuntimeException(
          'Query update profil dan password gagal disiapkan.'
        );
      }
      $stmt->bind_param(
        'ssssssss',
        $nik,
        $nama,
        $noHp,
        $alamat,
        $ftKtp,
        $ftSimA,
        $passwordHash,
        $userPlg
      );
    } else {
      $sql = "UPDATE t_penyewa
SET nik = ?,
nm_lngkp = ?,
no_hp = ?,
almt_lngkp = ?,
ft_ktp = ?,
ft_sim_a = ?
WHERE user_plg = ?";
      $stmt = $this->db->prepare($sql);
      if (!$stmt) {
        throw new RuntimeException(
          'Query update profil gagal disiapkan.'
        );
      }
      $stmt->bind_param(
        'sssssss',
        $nik,
        $nama,
        $noHp,
        $alamat,
        $ftKtp,
        $ftSimA,
        $userPlg
      );
    }
    $success = $stmt->execute();
    $stmt->close();
    return $success;
  }
}