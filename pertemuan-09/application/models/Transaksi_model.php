<?php
class Transaksi_model
{
  private mysqli $db;

  public function __construct()
  {
    global $db;
    $this->db = $db;
  }

  private function ambilKunci(string $namaKunci, int $timeout = 10): void
  {
    $stmt = $this->db->prepare('SELECT GET_LOCK(?, ?) AS lock_status');
    if (!$stmt) {
      throw new RuntimeException('Kunci proses gagal disiapkan.');
    }
    $stmt->bind_param('si', $namaKunci, $timeout);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException('Kunci proses gagal dijalankan.');
    }
    $hasil = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ((int) ($hasil['lock_status'] ?? 0) !== 1) {
      throw new RuntimeException('Kunci proses tidak diperoleh. Silakan coba lagi.');
    }
  }

  private function lepasKunci(string $namaKunci): void
  {
    $stmt = $this->db->prepare('SELECT RELEASE_LOCK(?)');
    if (!$stmt) {
      throw new RuntimeException('Pelepasan kunci proses gagal disiapkan.');
    }
    $stmt->bind_param('s', $namaKunci);
    $berhasil = $stmt->execute();
    $stmt->close();
    if (!$berhasil) {
      throw new RuntimeException('Pelepasan kunci proses gagal dijalankan.');
    }
  }

  private function kunciDanBuatKodeSewa(): array
  {
    $tanggal = (new DateTimeImmutable('now', new DateTimeZone('Asia/Jakarta')))->format('Ymd');
    $prefix = 'INV/' . $tanggal . '/';
    $namaKunci = 'dpwl_inv_' . $tanggal;

    $this->ambilKunci($namaKunci);

    try {
      $stmt = $this->db->prepare(
        "SELECT kode_sewa 
                 FROM t_sewa 
                 WHERE kode_sewa LIKE ? 
                 ORDER BY kode_sewa DESC 
                 LIMIT 1"
      );

      if (!$stmt) {
        throw new RuntimeException('Pencarian nomor transaksi gagal disiapkan.');
      }

      $pola = $prefix . '%';
      $stmt->bind_param('s', $pola);

      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Pencarian nomor transaksi gagal dijalankan.');
      }

      $baris = $stmt->get_result()->fetch_assoc();
      $stmt->close();

      $nomorTerakhir = $baris === null
        ? 0
        : (int) substr($baris['kode_sewa'], -2);

      $nomorBerikutnya = $nomorTerakhir + 1;

      if ($nomorBerikutnya > 99) {
        throw new RuntimeException('Nomor transaksi harian telah mencapai batas 99.');
      }

      $kodeSewaBaru = $prefix . str_pad((string) $nomorBerikutnya, 2, '0', STR_PAD_LEFT);

      return [
        'kode_sewa' => $kodeSewaBaru,
        'nama_kunci' => $namaKunci
      ];
    } catch (Throwable $e) {
      $this->lepasKunci($namaKunci);
      throw $e;
    }
  }

  public function getJumlahMobilKeranjang(string $userPlg): int
  {
    $userPlg = trim($userPlg);
    if ($userPlg === '') {
      return 0;
    }
    $stmt = $this->db->prepare(
      "SELECT COUNT(*) AS jumlah
             FROM t_pilih p
             INNER JOIN t_sewa s ON s.kode_sewa = p.kode_sewa
             WHERE s.user_plg = ?
             AND s.status_sewa = 'keranjang'"
    );
    if (!$stmt) {
      throw new RuntimeException('Penghitungan kendaraan dalam keranjang gagal disiapkan.');
    }
    $stmt->bind_param('s', $userPlg);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException('Penghitungan kendaraan dalam keranjang gagal dijalankan.');
    }
    $hasil = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return (int) ($hasil['jumlah'] ?? 0);
  }
  private function namaKunciKeranjang(string $userPlg): string
  {
    return 'dpwl_cart_' . substr(hash('sha256', $userPlg), 0, 48);
  }
  private function kunciKendaraan(string $noPlat): array
  {
    $stmt = $this->db->prepare(
      "SELECT no_plat, hrg_hari, status_unit
FROM t_mobil
WHERE no_plat = ?
LIMIT 1
FOR UPDATE"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Query penguncian kendaraan gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $noPlat);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Penguncian kendaraan gagal dijalankan.'
      );
    }
    $kendaraan = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($kendaraan === null) {
      throw new RuntimeException('Kendaraan tidak ditemukan.');
    }
    return $kendaraan;
  }
  private function hitungHariSewa(
    string $tglMulai,
    string $tglSelesai
  ): int {
    $mulai = DateTimeImmutable::createFromFormat('!Y-m-d', $tglMulai);
    $errorMulai = DateTimeImmutable::getLastErrors();
    if (
      $mulai === false ||
      (
        $errorMulai !== false &&
        (
          $errorMulai['warning_count'] > 0 ||
          $errorMulai['error_count'] > 0
        )
      ) ||
      $mulai->format('Y-m-d') !== $tglMulai
    ) {
      throw new InvalidArgumentException(
        'Tanggal mulai tidak valid.'
      );
    }
    $selesai = DateTimeImmutable::createFromFormat(
      '!Y-m-d',
      $tglSelesai
    );
    $errorSelesai = DateTimeImmutable::getLastErrors();
    if (
      $selesai === false ||
      (
        $errorSelesai !== false &&
        (
          $errorSelesai['warning_count'] > 0 ||
          $errorSelesai['error_count'] > 0
        )
      ) ||
      $selesai->format('Y-m-d') !== $tglSelesai
    ) {
      throw new InvalidArgumentException(
        'Tanggal selesai tidak valid.'
      );
    }
    if ($selesai <= $mulai) {
      throw new InvalidArgumentException(
        'Tanggal selesai harus setelah tanggal mulai.'
      );
    }
    $hari = (int) $mulai->diff($selesai)->days;
    if ($hari < 1 || $hari > 127) {
      throw new InvalidArgumentException(
        'Lama sewa harus antara 1 dan 127 hari.'
      );
    }
    return $hari;
  }
  private function normalisasiWaktu(string $waktu): string
  {
    foreach (['H:i:s', 'H:i'] as $format) {
      $hasil = DateTimeImmutable::createFromFormat(
        '!' . $format,
        $waktu
      );
      $errors = DateTimeImmutable::getLastErrors();
      if (
        $hasil !== false &&
        (
          $errors === false ||
          (
            $errors['warning_count'] === 0 &&
            $errors['error_count'] === 0
          )
        ) &&
        $hasil->format($format) === $waktu
      ) {
        return $hasil->format('H:i:s');
      }
    }
    throw new InvalidArgumentException(
      'Waktu pengambilan tidak valid.'
    );
  }
  public function tambahKeKeranjang(
    string $userPlg,
    string $noPlat
  ): string {
    $userPlg = trim($userPlg);
    $noPlat = trim($noPlat);
    if ($userPlg === '' || $noPlat === '') {
      throw new InvalidArgumentException(
        'Identitas penyewa dan kendaraan wajib diisi.'
      );
    }
    $this->db->begin_transaction();
    $namaKunci = null;
    $namaKunciKeranjang = null;
    $kunciKeranjangDidapat = false;
    try {
      $kendaraan = $this->kunciKendaraan($noPlat);
      if ($kendaraan['status_unit'] !== 'tersedia') {
        throw new RuntimeException(
          'Kendaraan tidak berstatus tersedia.'
        );
      }
      $namaKunciKeranjang = $this->namaKunciKeranjang($userPlg);
      $this->ambilKunci($namaKunciKeranjang);
      $kunciKeranjangDidapat = true;
      $stmt = $this->db->prepare(
        "SELECT kode_sewa, tgl_mulai, tgl_selesai, wkt_ambil
FROM t_sewa
WHERE user_plg = ?
AND status_sewa = 'keranjang'
ORDER BY kode_sewa ASC
LIMIT 1
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pencarian keranjang aktif gagal disiapkan.'
        );
      }
      $stmt->bind_param('s', $userPlg);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pencarian keranjang aktif gagal dijalankan.'
        );
      }
      $keranjang = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ($keranjang !== null) {
        $kodeSewa = (string) $keranjang['kode_sewa'];
        $tglMulai = (string) $keranjang['tgl_mulai'];
        $tglSelesai = (string) $keranjang['tgl_selesai'];
        $wktAmbil = $this->normalisasiWaktu(
          (string) $keranjang['wkt_ambil']
        );
      } else {
        $sekarang = new DateTimeImmutable('now');
        $tglMulai = $sekarang->format('Y-m-d');
        $tglSelesai = $sekarang->modify('+1 day')->format('Y-m-d');
        $wktAmbil = $sekarang->format('H:i:s');
        $dataKode = $this->kunciDanBuatKodeSewa();
        $kodeSewa = $dataKode['kode_sewa'];
        $namaKunci = $dataKode['nama_kunci'];
        $stmt = $this->db->prepare(
          "INSERT INTO t_sewa (
kode_sewa,
tgl_mulai,
tgl_selesai,
wkt_ambil,
status_sewa,
user_plg
) VALUES (?, ?, ?, ?, 'keranjang', ?)"
        );
        if (!$stmt) {
          throw new RuntimeException(
            'Pembuatan transaksi gagal disiapkan.'
          );
        }
        $stmt->bind_param(
          'sssss',
          $kodeSewa,
          $tglMulai,
          $tglSelesai,
          $wktAmbil,
          $userPlg
        );
        if (!$stmt->execute()) {
          $stmt->close();
          throw new RuntimeException(
            'Transaksi keranjang gagal dibuat.'
          );
        }
        $stmt->close();
      }
      // Cegah kendaraan yang sama dimasukkan dua kali ke keranjang aktif.
      $stmt = $this->db->prepare(
        "SELECT 1
FROM t_pilih
WHERE kode_sewa = ?
AND no_plat = ?
LIMIT 1
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pemeriksaan kendaraan dalam keranjang gagal disiapkan.'
        );
      }
      $stmt->bind_param('ss', $kodeSewa, $noPlat);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pemeriksaan kendaraan dalam keranjang gagal dijalankan.'
        );
      }
      $kendaraanSudahDipilih =
        $stmt->get_result()->fetch_assoc() !== null;
      $stmt->close();
      if ($kendaraanSudahDipilih) {
        throw new RuntimeException(
          'Kendaraan sudah ada dalam keranjang ini.'
        );
      }
      $hari = $this->hitungHariSewa($tglMulai, $tglSelesai);
      $stmt = $this->db->prepare(
        "INSERT INTO t_pilih (
kode_sewa,
no_plat,
hrg_sewa,
n_hari
) VALUES (?, ?, ?, ?)"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Detail transaksi gagal disiapkan.'
        );
      }
      $harga = (int) $kendaraan['hrg_hari'];
      $stmt->bind_param(
        'ssii',
        $kodeSewa,
        $noPlat,
        $harga,
        $hari
      );
      if (!$stmt->execute()) {
        $errno = $stmt->errno;
        $stmt->close();
        if ($errno === 1062) {
          throw new RuntimeException(
            'Kendaraan sudah ada dalam keranjang ini.'
          );
        }
        throw new RuntimeException(
          'Kendaraan gagal ditambahkan ke keranjang.'
        );
      }
      $stmt->close();
      $this->db->commit();
      if ($namaKunci !== null) {
        $this->lepasKunci($namaKunci);
        $namaKunci = null;
      }
      if ($kunciKeranjangDidapat && $namaKunciKeranjang !== null) {
        $this->lepasKunci($namaKunciKeranjang);
        $kunciKeranjangDidapat = false;
      }
      return $kodeSewa;
    } catch (Throwable $e) {
      $this->db->rollback();
      if ($namaKunci !== null) {
        $this->lepasKunci($namaKunci);
      }
      if ($kunciKeranjangDidapat && $namaKunciKeranjang !== null) {
        $this->lepasKunci($namaKunciKeranjang);
      }
      throw $e;
    }
  }
  private function validasiWaktuMulai(
    string $tglMulai,
    string $wktAmbil
  ): void {
    $zonaWaktu = new \DateTimeZone('Asia/Jakarta');
    $sekarangMinimum = (new \DateTimeImmutable('now', $zonaWaktu))
      ->modify('+2 hours');
    $waktuMulai = \DateTimeImmutable::createFromFormat(
      '!Y-m-d H:i:s',
      $tglMulai . ' ' . $this->normalisasiWaktu($wktAmbil),
      $zonaWaktu
    );
    if ($waktuMulai === false || $waktuMulai < $sekarangMinimum) {
      throw new InvalidArgumentException(
        'Waktu pengambilan harus minimal dua jam dari sekarang (Asia/Jakarta).'
      );
    }
  }
  public function perbaruiPeriodeKeranjang(
    string $kodeSewa,
    string $userPlg,
    string $tglMulai,
    string $tglSelesai,
    string $wktAmbil
  ): bool {
    $kodeSewa = trim($kodeSewa);
    $userPlg = trim($userPlg);
    if ($kodeSewa === '' || $userPlg === '') {
      throw new InvalidArgumentException(
        'Kode transaksi dan identitas penyewa wajib diisi.'
      );
    }
    $hari = $this->hitungHariSewa($tglMulai, $tglSelesai);
    $wktAmbil = $this->normalisasiWaktu($wktAmbil);
    $this->validasiWaktuMulai($tglMulai, $wktAmbil);
    $this->db->begin_transaction();
    try {
      $stmt = $this->db->prepare(
        "SELECT kode_sewa
FROM t_sewa
WHERE kode_sewa = ?
AND user_plg = ?
AND status_sewa = 'keranjang'
LIMIT 1
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pemeriksaan kepemilikan keranjang gagal disiapkan.'
        );
      }
      $stmt->bind_param('ss', $kodeSewa, $userPlg);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pemeriksaan kepemilikan keranjang gagal dijalankan.'
        );
      }
      $header = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ($header === null) {
        throw new RuntimeException(
          'Keranjang tidak ditemukan atau statusnya tidak valid.'
        );
      }
      $stmt = $this->db->prepare(
        "UPDATE t_sewa
SET tgl_mulai = ?, tgl_selesai = ?, wkt_ambil = ?
WHERE kode_sewa = ?
AND user_plg = ?
AND status_sewa = 'keranjang'"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pembaruan periode transaksi gagal disiapkan.'
        );
      }
      $stmt->bind_param(
        'sssss',
        $tglMulai,
        $tglSelesai,
        $wktAmbil,
        $kodeSewa,
        $userPlg
      );
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pembaruan periode transaksi gagal dijalankan.'
        );
      }
      $stmt->close();
      $stmt = $this->db->prepare(
        'UPDATE t_pilih SET n_hari = ? WHERE kode_sewa = ?'
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pembaruan durasi detail gagal disiapkan.'
        );
      }
      $stmt->bind_param('is', $hari, $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pembaruan durasi detail gagal dijalankan.'
        );
      }
      $stmt->close();
      $this->db->commit();
      return true;
    } catch (\Throwable $e) {
      $this->db->rollback();
      throw $e;
    }
  }
  public function getKeranjang(
    string $kodeSewa,
    string $userPlg
  ): ?array {
    $stmt = $this->db->prepare(
      "SELECT
kode_sewa,
tgl_mulai,
tgl_selesai,
wkt_ambil,
status_sewa,
user_plg
FROM t_sewa
WHERE kode_sewa = ?
AND user_plg = ?
AND status_sewa = 'keranjang'
LIMIT 1"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Pengambilan header keranjang gagal disiapkan.'
      );
    }
    $stmt->bind_param('ss', $kodeSewa, $userPlg);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Pengambilan header keranjang gagal dijalankan.'
      );
    }
    $header = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($header === null) {
      return null;
    }
    $stmt = $this->db->prepare(
      "SELECT
p.kode_sewa,
p.no_plat,
p.hrg_sewa,
p.n_hari,
m.nm_mobil,
m.ft_depan,
m.status_unit
FROM t_pilih p
INNER JOIN t_mobil m ON m.no_plat = p.no_plat
WHERE p.kode_sewa = ?
ORDER BY m.nm_mobil ASC"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Pengambilan detail keranjang gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $kodeSewa);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Pengambilan detail keranjang gagal dijalankan.'
      );
    }
    $detail = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $header['detail'] = $detail;
    $header['total'] = 0;
    foreach ($detail as $item) {
      $header['total'] += (int) $item['hrg_sewa'] * (int) $item['n_hari'];
    }
    return $header;
  }
  public function hapusDariKeranjang(
    string $kodeSewa,
    string $userPlg,
    string $noPlat
  ): bool {
    $stmt = $this->db->prepare(
      "DELETE p
FROM t_pilih p
INNER JOIN t_sewa s ON s.kode_sewa = p.kode_sewa
WHERE p.kode_sewa = ?
AND p.no_plat = ?
AND s.user_plg = ?
AND s.status_sewa = 'keranjang'"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Penghapusan detail keranjang gagal disiapkan.'
      );
    }
    $stmt->bind_param('sss', $kodeSewa, $noPlat, $userPlg);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Kendaraan gagal dihapus dari keranjang.'
      );
    }
    $berhasil = $stmt->affected_rows === 1;
    $stmt->close();
    return $berhasil;
  }
  public function checkout(
    string $kodeSewa,
    string $userPlg
  ): bool {
    $this->db->begin_transaction();
    try {
      // Baca awal header tanpa mengunci baris.
      $stmt = $this->db->prepare(
        "SELECT
kode_sewa,
tgl_mulai,
tgl_selesai,
wkt_ambil,
status_sewa
FROM t_sewa
WHERE kode_sewa = ?
AND user_plg = ?
LIMIT 1"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pembacaan awal transaksi checkout gagal disiapkan.'
        );
      }
      $stmt->bind_param('ss', $kodeSewa, $userPlg);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pembacaan awal transaksi checkout gagal dijalankan.'
        );
      }
      $headerAwal = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if (
        $headerAwal === null ||
        $headerAwal['status_sewa'] !== 'keranjang'
      ) {
        throw new RuntimeException(
          'Keranjang tidak ditemukan atau statusnya tidak valid.'
        );
      }
      $hariSeharusnya = $this->hitungHariSewa(
        $headerAwal['tgl_mulai'],
        $headerAwal['tgl_selesai']
      );
      $this->normalisasiWaktu($headerAwal['wkt_ambil']);
      // Ambil daftar kendaraan dan urutkan sebelum menguncinya.
      $stmt = $this->db->prepare(
        "SELECT no_plat
FROM t_pilih
WHERE kode_sewa = ?
ORDER BY no_plat ASC"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pembacaan detail checkout gagal disiapkan.'
        );
      }
      $stmt->bind_param('s', $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pembacaan detail checkout gagal dijalankan.'
        );
      }
      $daftarAwal = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $stmt->close();
      if (count($daftarAwal) === 0) {
        throw new RuntimeException(
          'Keranjang kosong dan tidak dapat di-checkout.'
        );
      }
      foreach ($daftarAwal as $baris) {
        $kendaraan = $this->kunciKendaraan($baris['no_plat']);
        if ($kendaraan['status_unit'] !== 'tersedia') {
          throw new RuntimeException(
            'Salah satu kendaraan tidak berstatus tersedia.'
          );
        }
      }
      // Setelah kendaraan terkunci, kunci ulang header.
      $stmt = $this->db->prepare(
        "SELECT
kode_sewa,
tgl_mulai,
tgl_selesai,
wkt_ambil,
status_sewa
FROM t_sewa
WHERE kode_sewa = ?
AND user_plg = ?
LIMIT 1
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Penguncian header checkout gagal disiapkan.'
        );
      }
      $stmt->bind_param('ss', $kodeSewa, $userPlg);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Penguncian header checkout gagal dijalankan.'
        );
      }
      $header = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if (
        $header === null ||
        $header['status_sewa'] !== 'keranjang' ||
        $header['tgl_mulai'] !== $headerAwal['tgl_mulai'] ||
        $header['tgl_selesai'] !== $headerAwal['tgl_selesai'] ||
        $header['wkt_ambil'] !== $headerAwal['wkt_ambil']
      ) {
        throw new RuntimeException(
          'Header keranjang berubah atau statusnya tidak valid.'
        );
      }
      // Baca ulang detail setelah header dikunci.
      $stmt = $this->db->prepare(
        "SELECT
p.no_plat,
p.hrg_sewa,
p.n_hari,
m.status_unit
FROM t_pilih p
INNER JOIN t_mobil m ON m.no_plat = p.no_plat
WHERE p.kode_sewa = ?
ORDER BY p.no_plat ASC
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Validasi detail checkout gagal disiapkan.'
        );
      }
      $stmt->bind_param('s', $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Validasi detail checkout gagal dijalankan.'
        );
      }
      $detail = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
      $stmt->close();
      if (count($detail) === 0) {
        throw new RuntimeException(
          'Keranjang kosong dan tidak dapat di-checkout.'
        );
      }
      $platAwal = array_column($daftarAwal, 'no_plat');
      $platAkhir = array_column($detail, 'no_plat');
      if ($platAwal !== $platAkhir) {
        throw new RuntimeException(
          'Detail keranjang berubah selama checkout. Silakan coba lagi.'
        );
      }
      foreach ($detail as $item) {
        if ((int) $item['n_hari'] !== $hariSeharusnya) {
          throw new RuntimeException(
            'Lama sewa detail tidak konsisten dengan periode transaksi.'
          );
        }
        // Aturan P7: status kendaraan saja yang menentukan kelayakan.
        if ($item['status_unit'] !== 'tersedia') {
          throw new RuntimeException(
            'Salah satu kendaraan tidak berstatus tersedia.'
          );
        }
      }
      // Buat record pembayaran DP dalam transaksi database yang sama.
      $hasilTanggal = $this->db->query(
        "SELECT DATE_FORMAT(NOW(), '%Y%m%d') AS tanggal_kode,
NOW() AS waktu_konfirmasi"
      );
      if (!$hasilTanggal) {
        throw new RuntimeException(
          'Tanggal konfirmasi pembayaran DP gagal dibaca.'
        );
      }
      $waktuKonfirmasi = $hasilTanggal->fetch_assoc();
      $hasilTanggal->free();
      $tanggalKode = (string) ($waktuKonfirmasi['tanggal_kode'] ?? '');
      $tglWktBayar = (string) ($waktuKonfirmasi['waktu_konfirmasi'] ?? '');
      if (!preg_match('/^\d{8}$/', $tanggalKode) || $tglWktBayar === '') {
        throw new RuntimeException(
          'Tanggal konfirmasi pembayaran DP tidak valid.'
        );
      }
      // Pastikan transaksi ini belum memiliki record DP.
      $stmt = $this->db->prepare(
        "SELECT kode_bayar
FROM t_bayar
WHERE kode_sewa = ? AND jns_byr = 'dp'
LIMIT 1
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException('Pemeriksaan pembayaran DP gagal disiapkan.');
      }
      $stmt->bind_param('s', $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Pemeriksaan pembayaran DP gagal dijalankan.');
      }
      $pembayaranDpAda = $stmt->get_result()->fetch_assoc() !== null;
      $stmt->close();
      if ($pembayaranDpAda) {
        throw new RuntimeException('Record pembayaran DP untuk transaksi ini sudah tersedia.');
      }
      // Ambil nomor terakhir pada tanggal server database.
      $polaKodeBayar = 'PAY/' . $tanggalKode . '/%';
      $stmt = $this->db->prepare(
        "SELECT kode_bayar
FROM t_bayar
WHERE kode_bayar LIKE ?
ORDER BY kode_bayar DESC
LIMIT 1
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException('Pembuatan kode pembayaran gagal disiapkan.');
      }
      $stmt->bind_param('s', $polaKodeBayar);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Pembuatan kode pembayaran gagal dijalankan.');
      }
      $pembayaranTerakhir = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      $nomorBerikutnya = 1;
      if ($pembayaranTerakhir !== null) {
        $bagianKode = explode('/', (string) $pembayaranTerakhir['kode_bayar']);
        $nomorBerikutnya = ((int) end($bagianKode)) + 1;
      }
      if ($nomorBerikutnya > 99) {
        throw new RuntimeException('Nomor pembayaran harian telah mencapai batas 99.');
      }
      $kodeBayar = sprintf('PAY/%s/%02d', $tanggalKode, $nomorBerikutnya);
      $totalSewa = 0;
      foreach ($detail as $item) {
        $totalSewa += (int) $item['hrg_sewa'] * (int) $item['n_hari'];
      }
      $nominalDp = (int) round($totalSewa * 30 / 100);
      if ($nominalDp <= 0) {
        throw new RuntimeException('Nominal pembayaran DP harus lebih besar dari nol.');
      }
      $stmt = $this->db->prepare(
        "INSERT INTO t_bayar
(kode_bayar, tgl_wkt_byr, ft_byr, mtode_byr, jns_byr,
nominal, sttus_byr, tgl_wkt_ver, kode_sewa)
VALUES (?, ?, NULL, 'transfer', 'dp', ?, 'pending', NULL, ?)"
      );
      if (!$stmt) {
        throw new RuntimeException('Pencatatan pembayaran DP gagal disiapkan.');
      }
      $stmt->bind_param('ssis', $kodeBayar, $tglWktBayar, $nominalDp, $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Pencatatan pembayaran DP gagal disimpan.');
      }
      $stmt->close();
      $stmt = $this->db->prepare(
        "UPDATE t_sewa
SET status_sewa = 'diajukan'
WHERE kode_sewa = ?
AND user_plg = ?
AND status_sewa = 'keranjang'"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Perubahan status checkout gagal disiapkan.'
        );
      }
      $stmt->bind_param('ss', $kodeSewa, $userPlg);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Status transaksi gagal diperbarui.');
      }
      $berhasil = $stmt->affected_rows === 1;
      $stmt->close();
      if (!$berhasil) {
        throw new RuntimeException(
          'Status transaksi tidak berubah; checkout dibatalkan.'
        );
      }
      $this->db->commit();
      return true;
    } catch (\Throwable $e) {
      $this->db->rollback();
      throw $e;
    }
  }
  public function getKodeKeranjangAktif(string $userPlg): ?string
  {
    $userPlg = trim($userPlg);
    if ($userPlg === '') {
      return null;
    }
    $stmt = $this->db->prepare(
      "SELECT kode_sewa
FROM t_sewa
WHERE user_plg = ?
AND status_sewa = 'keranjang'
ORDER BY kode_sewa ASC
LIMIT 1"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Pencarian kode keranjang aktif gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $userPlg);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Pencarian kode keranjang aktif gagal dijalankan.'
      );
    }
    $hasil = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    return $hasil === null
      ? null
      : (string) $hasil['kode_sewa'];
  }
}