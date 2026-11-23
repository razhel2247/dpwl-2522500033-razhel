<?php
// UTS DPWL: Model Manajemen Penyewa (CRUD tabel t_penyewa).
// Aturan: Model HANYA berisi SQL (MySQLi + prepared statement). Validasi ada di Controller.
class Mpenyewa_model
{
  private mysqli $db;

  public function __construct()
  {
    global $db; // koneksi dibuat di application/config/database.php
    $this->db = $db;
  }

  // Menyiapkan prepared statement; gagal => lempar exception agar ditangkap Controller.
  private function siapkan(string $sql): mysqli_stmt
  {
    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
      throw new RuntimeException('Query penyewa gagal disiapkan.');
    }
    return $stmt;
  }

  // READ (daftar): kolom sandi sengaja TIDAK diambil agar hash tidak sampai ke View.
  public function getAll(): array
  {
    $stmt = $this->siapkan(
      "SELECT user_plg, nik, nm_lngkp, no_hp, almt_lngkp, ft_ktp, ft_sim_a
       FROM t_penyewa
       ORDER BY nm_lngkp ASC, user_plg ASC"
    );
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $rows;
  }

  // READ (satu baris): dipakai form ubah, cek duplikat username, dan proses hapus.
  public function findByUserPlg(string $userPlg): ?array
  {
    $stmt = $this->siapkan(
      "SELECT user_plg, nik, nm_lngkp, no_hp, almt_lngkp, ft_ktp, ft_sim_a
       FROM t_penyewa
       WHERE user_plg = ?
       LIMIT 1"
    );
    $stmt->bind_param('s', $userPlg);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $row ?: null;
  }

  // Cek NIK sudah dipakai penyewa lain. $kecualiUserPlg = akun yang sedang diubah.
  public function nikSudahDipakai(string $nik, string $kecualiUserPlg = ''): bool
  {
    $stmt = $this->siapkan(
      "SELECT 1 FROM t_penyewa WHERE nik = ? AND user_plg <> ? LIMIT 1"
    );
    $stmt->bind_param('ss', $nik, $kecualiUserPlg);
    $stmt->execute();
    $ada = $stmt->get_result()->fetch_row() !== null;
    $stmt->close();
    return $ada;
  }

  // Jumlah riwayat sewa milik penyewa (t_sewa.user_plg), dipakai sebelum DELETE.
  public function jumlahSewa(string $userPlg): int
  {
    $stmt = $this->siapkan(
      "SELECT COUNT(*) AS total FROM t_sewa WHERE user_plg = ?"
    );
    $stmt->bind_param('s', $userPlg);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($row['total'] ?? 0);
  }

  // CREATE: $data['sandi'] sudah berupa HASH (password_hash) dari Controller.
  public function create(array $data): bool
  {
    $stmt = $this->siapkan(
      "INSERT INTO t_penyewa
         (user_plg, sandi, nik, nm_lngkp, no_hp, almt_lngkp, ft_ktp, ft_sim_a)
       VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param(
      'ssssssss',
      $data['user_plg'],
      $data['sandi'],
      $data['nik'],
      $data['nm_lngkp'],
      $data['no_hp'],
      $data['almt_lngkp'],
      $data['ft_ktp'],
      $data['ft_sim_a']
    );
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // UPDATE: $passwordHash null => kolom sandi tidak disentuh (hash lama dipertahankan).
  public function update(string $userPlg, array $data, ?string $passwordHash = null): bool
  {
    if ($passwordHash !== null) {
      $stmt = $this->siapkan(
        "UPDATE t_penyewa
         SET sandi = ?, nik = ?, nm_lngkp = ?, no_hp = ?, almt_lngkp = ?,
             ft_ktp = ?, ft_sim_a = ?
         WHERE user_plg = ?"
      );
      $stmt->bind_param(
        'ssssssss',
        $passwordHash,
        $data['nik'],
        $data['nm_lngkp'],
        $data['no_hp'],
        $data['almt_lngkp'],
        $data['ft_ktp'],
        $data['ft_sim_a'],
        $userPlg
      );
    } else {
      $stmt = $this->siapkan(
        "UPDATE t_penyewa
         SET nik = ?, nm_lngkp = ?, no_hp = ?, almt_lngkp = ?,
             ft_ktp = ?, ft_sim_a = ?
         WHERE user_plg = ?"
      );
      $stmt->bind_param(
        'sssssss',
        $data['nik'],
        $data['nm_lngkp'],
        $data['no_hp'],
        $data['almt_lngkp'],
        $data['ft_ktp'],
        $data['ft_sim_a'],
        $userPlg
      );
    }
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }

  // DELETE
  public function delete(string $userPlg): bool
  {
    $stmt = $this->siapkan("DELETE FROM t_penyewa WHERE user_plg = ?");
    $stmt->bind_param('s', $userPlg);
    $ok = $stmt->execute();
    $stmt->close();
    return $ok;
  }
}