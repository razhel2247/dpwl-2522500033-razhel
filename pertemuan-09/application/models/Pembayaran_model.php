<?php
// P9 BARU - application/models/Pembayaran_model.php
// Mengelola pembayaran DP yang SUDAH dibuat saat checkout P7 (t_bayar.jns_byr = 'dp').
// Tidak ada INSERT ke t_bayar di sini: P9 hanya memperbarui record yang sudah ada.

// Kesalahan aturan bisnis yang pesannya aman ditampilkan kepada pengguna.
class PembayaranException extends RuntimeException
{
}

class Pembayaran_model
{
  private mysqli $db;

  // Kolom header pembayaran + transaksi yang dipakai bersama oleh beberapa query.
  private const KOLOM_HEADER = "b.kode_bayar, b.tgl_wkt_byr, b.ft_byr, b.mtode_byr,
        b.jns_byr, b.nominal, b.sttus_byr, b.tgl_wkt_ver, b.kode_sewa,
        s.tgl_mulai, s.tgl_selesai, s.wkt_ambil, s.status_sewa, s.user_plg";

  public function __construct()
  {
    global $db;
    $this->db = $db;
  }

  // ---------------------------------------------------------------
  // Helper query (prepared statement)
  // ---------------------------------------------------------------

  private function ambilSemua(string $sql, string $tipe = '', array $param = []): array
  {
    $stmt = $this->db->prepare($sql);
    if (!$stmt) {
      throw new RuntimeException('Query pembayaran gagal disiapkan.');
    }
    if ($tipe !== '') {
      $stmt->bind_param($tipe, ...$param);
    }
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException('Query pembayaran gagal dijalankan.');
    }
    $hasil = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $hasil;
  }

  private function ambilSatu(string $sql, string $tipe = '', array $param = []): ?array
  {
    $baris = $this->ambilSemua($sql, $tipe, $param);
    return $baris[0] ?? null;
  }

  // Menambahkan rincian kendaraan dan total sewa ke header pembayaran.
  private function lengkapiDetail(array $header): array
  {
    $detail = $this->ambilSemua(
      "SELECT p.no_plat, p.hrg_sewa, p.n_hari, m.nm_mobil
             FROM t_pilih p
             INNER JOIN t_mobil m ON m.no_plat = p.no_plat
             WHERE p.kode_sewa = ?
             ORDER BY m.nm_mobil ASC",
      's',
      [(string) $header['kode_sewa']]
    );

    $total = 0;
    foreach ($detail as $item) {
      $total += (int) $item['hrg_sewa'] * (int) $item['n_hari'];
    }

    $header['detail'] = $detail;
    $header['total'] = $total;
    return $header;
  }

  // ---------------------------------------------------------------
  // Sisi penyewa
  // ---------------------------------------------------------------

  // Daftar pembayaran DP milik satu penyewa.
  public function getDaftarPenyewa(string $userPlg): array
  {
    return $this->ambilSemua(
      "SELECT b.kode_bayar, b.tgl_wkt_byr, b.ft_byr, b.nominal, b.sttus_byr,
                    b.kode_sewa, s.status_sewa, s.tgl_mulai, s.tgl_selesai
             FROM t_bayar b
             INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
             WHERE s.user_plg = ? AND b.jns_byr = 'dp'
             ORDER BY b.kode_bayar DESC",
      's',
      [$userPlg]
    );
  }

  // Detail DP satu transaksi. Mengembalikan null bila tidak ada ATAU bukan milik penyewa.
  public function getDpPenyewa(string $kodeSewa, string $userPlg): ?array
  {
    $header = $this->ambilSatu(
      "SELECT " . self::KOLOM_HEADER . "
             FROM t_bayar b
             INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
             WHERE b.kode_sewa = ? AND s.user_plg = ? AND b.jns_byr = 'dp'
             LIMIT 1",
      'ss',
      [$kodeSewa, $userPlg]
    );

    return $header === null ? null : $this->lengkapiDetail($header);
  }

  // Memperbarui record DP yang sudah ada: ft_byr dan tgl_wkt_byr.
  // sttus_byr TIDAK diubah (tetap pending sampai admin memverifikasi).
  // Mengembalikan nama berkas lama (atau null) agar Controller dapat menghapusnya
  // setelah commit berhasil.
  public function simpanBukti(string $kodeSewa, string $userPlg, string $namaFile): ?string
  {
    $this->db->begin_transaction();

    try {
      $bayar = $this->ambilSatu(
        "SELECT b.kode_bayar, b.ft_byr, b.sttus_byr, s.status_sewa
                 FROM t_bayar b
                 INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                 WHERE b.kode_sewa = ? AND s.user_plg = ? AND b.jns_byr = 'dp'
                 LIMIT 1
                 FOR UPDATE",
        'ss',
        [$kodeSewa, $userPlg]
      );

      if ($bayar === null) {
        throw new PembayaranException('Pembayaran tidak ditemukan.');
      }
      if ($bayar['sttus_byr'] !== 'pending') {
        throw new PembayaranException(
          'Pembayaran sudah diverifikasi dan tidak dapat diubah kembali.'
        );
      }
      if ($bayar['status_sewa'] !== 'diajukan') {
        throw new PembayaranException(
          'Status transaksi tidak memungkinkan pengunggahan bukti pembayaran.'
        );
      }

      $stmt = $this->db->prepare(
        "UPDATE t_bayar
                 SET ft_byr = ?, tgl_wkt_byr = NOW()
                 WHERE kode_bayar = ? AND sttus_byr = 'pending'"
      );
      if (!$stmt) {
        throw new RuntimeException('Pembaruan bukti pembayaran gagal disiapkan.');
      }
      $kodeBayar = (string) $bayar['kode_bayar'];
      $stmt->bind_param('ss', $namaFile, $kodeBayar);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Pembaruan bukti pembayaran gagal dijalankan.');
      }
      $berubah = $stmt->affected_rows === 1;
      $stmt->close();

      if (!$berubah) {
        throw new PembayaranException(
          'Bukti pembayaran tidak tersimpan karena status pembayaran telah berubah.'
        );
      }

      $this->db->commit();

      $lama = (string) ($bayar['ft_byr'] ?? '');
      return $lama === '' ? null : $lama;
    } catch (Throwable $e) {
      $this->db->rollback();
      throw $e;
    }
  }

  // ---------------------------------------------------------------
  // Sisi admin
  // ---------------------------------------------------------------

  // Pembayaran DP yang belum diverifikasi. Yang sudah ada bukti diurutkan lebih dulu.
  public function getDaftarPending(): array
  {
    return $this->ambilSemua(
      "SELECT b.kode_bayar, b.tgl_wkt_byr, b.ft_byr, b.nominal, b.sttus_byr,
                    b.kode_sewa, s.status_sewa, s.user_plg, p.nm_lngkp
             FROM t_bayar b
             INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
             INNER JOIN t_penyewa p ON p.user_plg = s.user_plg
             WHERE b.jns_byr = 'dp'
               AND b.sttus_byr = 'pending'
               AND s.status_sewa = 'diajukan'
             ORDER BY (b.ft_byr IS NULL) ASC, b.tgl_wkt_byr ASC"
    );
  }

  // Riwayat pembayaran yang sudah diverifikasi (valid / invalid), terbaru dulu.
  public function getRiwayat(): array
  {
    return $this->ambilSemua(
      "SELECT b.kode_bayar, b.tgl_wkt_byr, b.tgl_wkt_ver, b.nominal, b.sttus_byr,
                    b.kode_sewa, s.status_sewa, s.user_plg, p.nm_lngkp
             FROM t_bayar b
             INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
             INNER JOIN t_penyewa p ON p.user_plg = s.user_plg
             WHERE b.jns_byr = 'dp'
               AND b.sttus_byr IN ('valid', 'invalid')
             ORDER BY b.tgl_wkt_ver DESC
             LIMIT 100"
    );
  }

  // Detail lengkap untuk admin (termasuk identitas penyewa).
  public function getDetailAdmin(string $kodeSewa): ?array
  {
    $header = $this->ambilSatu(
      "SELECT " . self::KOLOM_HEADER . ", p.nm_lngkp, p.no_hp
             FROM t_bayar b
             INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
             INNER JOIN t_penyewa p ON p.user_plg = s.user_plg
             WHERE b.kode_sewa = ? AND b.jns_byr = 'dp'
             LIMIT 1",
      's',
      [$kodeSewa]
    );

    return $header === null ? null : $this->lengkapiDetail($header);
  }

  // Menerima / menolak pembayaran. Dua tabel diperbarui dalam SATU transaksi database.
  //   terima => t_bayar.sttus_byr = 'valid',   t_sewa.status_sewa = 'disetujui'
  //   tolak  => t_bayar.sttus_byr = 'invalid', t_sewa.status_sewa = 'ditolak'
  public function verifikasi(string $kodeSewa, string $keputusan): void
  {
    $peta = [
      'terima' => ['valid', 'disetujui'],
      'tolak' => ['invalid', 'ditolak'],
    ];
    if (!isset($peta[$keputusan])) {
      throw new PembayaranException('Keputusan verifikasi tidak valid.');
    }
    [$statusBayarBaru, $statusSewaBaru] = $peta[$keputusan];

    $this->db->begin_transaction();

    try {
      // Kunci baris pembayaran + transaksi agar dua admin tidak memproses bersamaan.
      $bayar = $this->ambilSatu(
        "SELECT b.kode_bayar, b.ft_byr, b.sttus_byr, s.status_sewa
                 FROM t_bayar b
                 INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                 WHERE b.kode_sewa = ? AND b.jns_byr = 'dp'
                 LIMIT 1
                 FOR UPDATE",
        's',
        [$kodeSewa]
      );

      if ($bayar === null) {
        throw new PembayaranException('Data pembayaran tidak ditemukan.');
      }
      if ($bayar['sttus_byr'] !== 'pending') {
        throw new PembayaranException(
          'Pembayaran sudah diverifikasi dan tidak dapat diproses kembali.'
        );
      }
      if ($bayar['status_sewa'] !== 'diajukan') {
        throw new PembayaranException(
          'Status transaksi tidak valid untuk diverifikasi.'
        );
      }
      if ((string) ($bayar['ft_byr'] ?? '') === '') {
        throw new PembayaranException(
          'Bukti pembayaran belum diunggah. Verifikasi tidak dapat dilakukan.'
        );
      }

      $kodeBayar = (string) $bayar['kode_bayar'];

      // 1) t_bayar: status + waktu verifikasi
      $stmt = $this->db->prepare(
        "UPDATE t_bayar
                 SET sttus_byr = ?, tgl_wkt_ver = NOW()
                 WHERE kode_bayar = ? AND sttus_byr = 'pending'"
      );
      if (!$stmt) {
        throw new RuntimeException('Pembaruan status pembayaran gagal disiapkan.');
      }
      $stmt->bind_param('ss', $statusBayarBaru, $kodeBayar);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Pembaruan status pembayaran gagal dijalankan.');
      }
      $bayarBerubah = $stmt->affected_rows === 1;
      $stmt->close();
      if (!$bayarBerubah) {
        throw new PembayaranException(
          'Status pembayaran telah berubah. Verifikasi dibatalkan.'
        );
      }

      // 2) t_sewa: status transaksi
      $stmt = $this->db->prepare(
        "UPDATE t_sewa
                 SET status_sewa = ?
                 WHERE kode_sewa = ? AND status_sewa = 'diajukan'"
      );
      if (!$stmt) {
        throw new RuntimeException('Pembaruan status transaksi gagal disiapkan.');
      }
      $stmt->bind_param('ss', $statusSewaBaru, $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException('Pembaruan status transaksi gagal dijalankan.');
      }
      $sewaBerubah = $stmt->affected_rows === 1;
      $stmt->close();
      if (!$sewaBerubah) {
        throw new PembayaranException(
          'Status transaksi telah berubah. Verifikasi dibatalkan.'
        );
      }

      // 3) Pengaman: pastikan nilai yang tersimpan benar-benar sesuai.
      // Bila definisi ENUM di database belum memuat nilai baru dan MySQL tidak
      // berjalan dalam strict mode, nilai dapat tersimpan kosong. Batalkan bila begitu.
      $cek = $this->ambilSatu(
        "SELECT b.sttus_byr, s.status_sewa
                 FROM t_bayar b
                 INNER JOIN t_sewa s ON s.kode_sewa = b.kode_sewa
                 WHERE b.kode_bayar = ?
                 LIMIT 1",
        's',
        [$kodeBayar]
      );
      if (
        $cek === null
        || $cek['sttus_byr'] !== $statusBayarBaru
        || $cek['status_sewa'] !== $statusSewaBaru
      ) {
        throw new RuntimeException(
          'Status hasil verifikasi tidak tersimpan sesuai. '
          . 'Periksa definisi kolom sttus_byr dan status_sewa.'
        );
      }

      $this->db->commit();
    } catch (Throwable $e) {
      $this->db->rollback();
      throw $e;
    }
  }
}