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
  /**
   * P10: Mengambil daftar transaksi yang dapat diproses pelunasannya.
   *
   * Total transaksi menggunakan snapshot tarif pada t_pilih.
   * Pembayaran yang mengurangi kewajiban hanya yang berstatus valid.
   */
  public function getDaftarPelunasan(): array
  {
    $stmt = $this->db->prepare(
      "SELECT
s.kode_sewa,
s.tgl_mulai,
s.tgl_selesai,
s.status_sewa,
p.user_plg,
p.nm_lngkp,
p.no_hp,
COALESCE((
SELECT SUM(tp.hrg_sewa * tp.n_hari)
FROM t_pilih tp
WHERE tp.kode_sewa = s.kode_sewa
), 0) AS total_transaksi,
COALESCE((
SELECT SUM(b.nominal)
FROM t_bayar b
WHERE b.kode_sewa = s.kode_sewa
AND b.sttus_byr = 'valid'
), 0) AS total_terbayar
FROM t_sewa s
INNER JOIN t_penyewa p
ON p.user_plg = s.user_plg
WHERE s.status_sewa IN ('disetujui', 'lunas')
AND EXISTS (
SELECT 1
FROM t_pilih tp
WHERE tp.kode_sewa = s.kode_sewa
)
ORDER BY s.tgl_mulai DESC, s.kode_sewa DESC"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Query daftar pelunasan gagal disiapkan.'
      );
    }
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Daftar pelunasan gagal diambil.'
      );
    }
    $daftar = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    foreach ($daftar as &$item) {
      $item['total_transaksi'] = (int) $item['total_transaksi'];
      $item['total_terbayar'] = (int) $item['total_terbayar'];
      $item['sisa_kewajiban'] = max(
        0,
        $item['total_transaksi'] - $item['total_terbayar']
      );
    }
    unset($item);
    return array_values(array_filter(
      $daftar,
      static fn(array $item): bool =>
        $item['sisa_kewajiban'] > 0 ||
        $item['status_sewa'] === 'lunas'
    ));
  }
  /**
   * P10: Mengambil detail transaksi, penyewa, dan riwayat pembayaran
   * untuk halaman pelunasan.
   */
  public function getDetailPelunasan(string $kodeSewa): ?array
  {
    $kodeSewa = trim($kodeSewa);
    if ($kodeSewa === '') {
      throw new InvalidArgumentException(
        'Kode transaksi sewa wajib diisi.'
      );
    }
    $stmt = $this->db->prepare(
      "SELECT
s.kode_sewa,
s.tgl_mulai,
s.tgl_selesai,
s.wkt_ambil,
s.status_sewa,
p.user_plg,
p.nm_lngkp,
p.no_hp,
p.nik,
p.almt_lngkp,
COALESCE((
SELECT SUM(tp.hrg_sewa * tp.n_hari)
FROM t_pilih tp
WHERE tp.kode_sewa = s.kode_sewa
), 0) AS total_transaksi,
COALESCE((
SELECT SUM(b.nominal)
FROM t_bayar b
WHERE b.kode_sewa = s.kode_sewa
AND b.sttus_byr = 'valid'
), 0) AS total_terbayar
FROM t_sewa s
INNER JOIN t_penyewa p
ON p.user_plg = s.user_plg
WHERE s.kode_sewa = ?
LIMIT 1"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Query detail pelunasan gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $kodeSewa);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Detail transaksi pelunasan gagal diambil.'
      );
    }
    $detail = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if ($detail === null) {
      return null;
    }
    $detail['total_transaksi'] = (int) $detail['total_transaksi'];
    $detail['total_terbayar'] = (int) $detail['total_terbayar'];
    $detail['sisa_kewajiban'] = max(
      0,
      $detail['total_transaksi'] - $detail['total_terbayar']
    );
    $stmt = $this->db->prepare(
      "SELECT
kode_bayar,
tgl_wkt_byr,
mtode_byr,
jns_byr,
nominal,
sttus_byr,
ft_byr,
tgl_wkt_ver
FROM t_bayar
WHERE kode_sewa = ?
ORDER BY tgl_wkt_byr ASC, kode_bayar ASC"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Query riwayat pembayaran gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $kodeSewa);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Riwayat pembayaran gagal diambil.'
      );
    }
    $detail['riwayat_pembayaran'] =
      $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    $stmt = $this->db->prepare(
      "SELECT
m.no_plat,
m.nm_mobil,
tp.hrg_sewa,
tp.n_hari,
(tp.hrg_sewa * tp.n_hari) AS subtotal
FROM t_pilih tp
INNER JOIN t_mobil m
ON m.no_plat = tp.no_plat
WHERE tp.kode_sewa = ?
ORDER BY m.nm_mobil ASC, m.no_plat ASC"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Query detail kendaraan pelunasan gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $kodeSewa);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Detail kendaraan pelunasan gagal diambil.'
      );
    }
    $detail['daftar_kendaraan'] =
      $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
    return $detail;
  }
  /**
   * P10: Membuat kode pembayaran dengan format PAY/YYYYMMDD/NN.
   * Pemanggil harus memegang named lock pembayaran harian.
   */
  private function buatKodeBayarPelunasan(string $tanggalKode): string
  {
    $pola = 'PAY/' . $tanggalKode . '/%';
    $stmt = $this->db->prepare(
      "SELECT kode_bayar
FROM t_bayar
WHERE kode_bayar LIKE ?
ORDER BY kode_bayar DESC
LIMIT 1"
    );
    if (!$stmt) {
      throw new RuntimeException(
        'Pembuatan kode pembayaran gagal disiapkan.'
      );
    }
    $stmt->bind_param('s', $pola);
    if (!$stmt->execute()) {
      $stmt->close();
      throw new RuntimeException(
        'Pembuatan kode pembayaran gagal dijalankan.'
      );
    }
    $terakhir = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    $nomorBerikutnya = 1;
    if ($terakhir !== null) {
      $bagianKode = explode('/', (string) $terakhir['kode_bayar']);
      $nomorBerikutnya = ((int) end($bagianKode)) + 1;
    }
    if ($nomorBerikutnya > 99) {
      throw new RuntimeException(
        'Nomor pembayaran harian telah mencapai batas 99.'
      );
    }
    return sprintf('PAY/%s/%02d', $tanggalKode, $nomorBerikutnya);
  }
  /**
   * P10: Mencatat pelunasan langsung oleh admin.
   *
   * Pembayaran langsung dicatat sebagai valid.
   * Status transaksi diperbarui menjadi lunas dalam transaksi database
   * yang sama. Status kendaraan tidak diubah pada P10.
   */
  public function simpanPelunasan(
    string $kodeSewa,
    string $metodeBayar,
    ?string $namaFileBukti = null
  ): string {
    $kodeSewa = trim($kodeSewa);
    $metodeBayar = trim($metodeBayar);
    if ($kodeSewa === '') {
      throw new InvalidArgumentException(
        'Kode transaksi sewa wajib diisi.'
      );
    }
    if (!in_array($metodeBayar, ['tunai', 'transfer'], true)) {
      throw new InvalidArgumentException(
        'Metode pembayaran tidak valid.'
      );
    }
    if ($metodeBayar === 'transfer') {
      $namaFileBukti = trim((string) $namaFileBukti);
      if ($namaFileBukti === '') {
        throw new InvalidArgumentException(
          'Bukti transfer wajib tersedia.'
        );
      }
    } else {
      $namaFileBukti = null;
    }
    // Harus sama dengan kunci yang digunakan Transaksi_model::checkout()
// ketika membuat kode pembayaran DP.
    $namaKunci = 'dpwl_payment_code_sequence';
    $kunciDiperoleh = false;
    $transaksiDimulai = false;
    try {
      $stmt = $this->db->prepare(
        'SELECT GET_LOCK(?, 10) AS lock_status'
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Kunci pembuatan kode pembayaran gagal disiapkan.'
        );
      }
      $stmt->bind_param('s', $namaKunci);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Kunci pembuatan kode pembayaran gagal dijalankan.'
        );
      }
      $hasilKunci = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ((int) ($hasilKunci['lock_status'] ?? 0) !== 1) {
        throw new RuntimeException(
          'Proses pembayaran sedang digunakan. Silakan coba kembali.'
        );
      }
      $kunciDiperoleh = true;
      $this->db->begin_transaction();
      $transaksiDimulai = true;
      // Kunci transaksi agar dua permintaan pelunasan tidak berjalan
// bersamaan untuk transaksi yang sama.
      $stmt = $this->db->prepare(
        "SELECT
s.kode_sewa,
s.status_sewa,
COALESCE((
SELECT SUM(tp.hrg_sewa * tp.n_hari)
FROM t_pilih tp
WHERE tp.kode_sewa = s.kode_sewa
), 0) AS total_transaksi,
COALESCE((
SELECT SUM(b.nominal)
FROM t_bayar b
WHERE b.kode_sewa = s.kode_sewa
AND b.sttus_byr = 'valid'
), 0) AS total_terbayar
FROM t_sewa s
WHERE s.kode_sewa = ?
LIMIT 1
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pemeriksaan transaksi pelunasan gagal disiapkan.'
        );
      }
      $stmt->bind_param('s', $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pemeriksaan transaksi pelunasan gagal dijalankan.'
        );
      }
      $transaksi = $stmt->get_result()->fetch_assoc();
      $stmt->close();
      if ($transaksi === null) {
        throw new RuntimeException(
          'Transaksi sewa tidak ditemukan.'
        );
      }
      if (
        !in_array(
          $transaksi['status_sewa'],
          ['disetujui', 'lunas'],
          true
        )
      ) {
        throw new RuntimeException(
          'Status transaksi belum memenuhi syarat pelunasan.'
        );
      }
      $totalTransaksi = (int) $transaksi['total_transaksi'];
      $totalTerbayar = (int) $transaksi['total_terbayar'];
      $sisaKewajiban = $totalTransaksi - $totalTerbayar;
      if ($totalTransaksi <= 0 || $sisaKewajiban <= 0) {
        throw new RuntimeException(
          'Transaksi tidak memiliki sisa kewajiban untuk dilunasi.'
        );
      }
      if ($sisaKewajiban > 8388607) {
        throw new RuntimeException(
          'Sisa kewajiban melebihi kapasitas kolom nominal pada database.'
        );
      }
      // Pastikan transaksi belum memiliki record pelunasan.
      $stmt = $this->db->prepare(
        "SELECT kode_bayar
FROM t_bayar
WHERE kode_sewa = ?
AND jns_byr = 'pelunasan'
LIMIT 1
FOR UPDATE"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pemeriksaan pelunasan sebelumnya gagal disiapkan.'
        );
      }
      $stmt->bind_param('s', $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pemeriksaan pelunasan sebelumnya gagal dijalankan.'
        );
      }
      $pelunasanAda = $stmt->get_result()->fetch_assoc() !== null;
      $stmt->close();
      if ($pelunasanAda) {
        throw new RuntimeException(
          'Transaksi ini sudah memiliki record pelunasan.'
        );
      }
      $hasilWaktu = $this->db->query(
        "SELECT
DATE_FORMAT(NOW(), '%Y%m%d') AS tanggal_kode,
NOW() AS waktu_pembayaran"
      );
      if (!$hasilWaktu) {
        throw new RuntimeException(
          'Waktu pembayaran gagal dibaca.'
        );
      }
      $waktu = $hasilWaktu->fetch_assoc();
      $hasilWaktu->free();
      $tanggalKode = (string) ($waktu['tanggal_kode'] ?? '');
      $waktuPembayaran = (string) ($waktu['waktu_pembayaran'] ?? '');
      if (
        !preg_match('/^\d{8}$/', $tanggalKode) ||
        $waktuPembayaran === ''
      ) {
        throw new RuntimeException(
          'Waktu pembayaran tidak valid.'
        );
      }
      $kodeBayar = $this->buatKodeBayarPelunasan($tanggalKode);
      $stmt = $this->db->prepare(
        "INSERT INTO t_bayar (
kode_bayar,
tgl_wkt_byr,
ft_byr,
mtode_byr,
jns_byr,
nominal,
sttus_byr,
tgl_wkt_ver,
kode_sewa
) VALUES (?, ?, ?, ?, 'pelunasan', ?, 'valid', NOW(), ?)"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pencatatan pelunasan gagal disiapkan.'
        );
      }
      $stmt->bind_param(
        'ssssis',
        $kodeBayar,
        $waktuPembayaran,
        $namaFileBukti,
        $metodeBayar,
        $sisaKewajiban,
        $kodeSewa
      );
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Pelunasan gagal disimpan.'
        );
      }
      $stmt->close();
      // Pelunasan berhasil dicatat; perbarui status transaksi
// dalam transaksi database yang sama.
      $stmt = $this->db->prepare(
        "UPDATE t_sewa
SET status_sewa = 'lunas'
WHERE kode_sewa = ?
AND status_sewa IN ('disetujui', 'lunas')"
      );
      if (!$stmt) {
        throw new RuntimeException(
          'Pembaruan status transaksi menjadi lunas gagal disiapkan.'
        );
      }
      $stmt->bind_param('s', $kodeSewa);
      if (!$stmt->execute()) {
        $stmt->close();
        throw new RuntimeException(
          'Status transaksi gagal diperbarui menjadi lunas.'
        );
      }
      if ($stmt->affected_rows !== 1) {
        $stmt->close();
        throw new RuntimeException(
          'Status transaksi tidak berubah menjadi lunas. '
          . 'Periksa kembali status transaksi.'
        );
      }
      $stmt->close();
      $this->db->commit();
      $transaksiDimulai = false;
      return $kodeBayar;
    } catch (Throwable $e) {
      if ($transaksiDimulai) {
        $this->db->rollback();
      }
      throw $e;
    } finally {
      if ($kunciDiperoleh) {
        $stmt = $this->db->prepare(
          'SELECT RELEASE_LOCK(?)'
        );
        if ($stmt) {
          $stmt->bind_param('s', $namaKunci);
          $stmt->execute();
          $stmt->close();
        }
      }
    }
  }
}