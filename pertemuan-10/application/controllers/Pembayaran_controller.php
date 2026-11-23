<?php
// P9 BARU - application/controllers/Pembayaran_controller.php
// Pembayaran DP oleh penyewa: lihat rincian + unggah bukti transfer.
require_once APPPATH . 'models/Pembayaran_model.php';
require_once APPPATH . 'models/Transaksi_model.php';
require_once APPPATH . 'helpers/auth_helper.php';

class Pembayaran_controller extends Controller
{
  private const BATAS_UKURAN = 2 * 1024 * 1024; // 2 MB
  private const PESAN_UKURAN = 'Ukuran berkas melebihi batas maksimal 2 MB.';
  private const MIME_DIIZINKAN = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
  ];
  private const EKSTENSI_DIIZINKAN = ['jpg', 'jpeg', 'png', 'webp'];

  private Pembayaran_model $model;
  private Pembayaran_model $pembayaranModel; // P10: alias model yang sama
  private Transaksi_model $transaksiModel;

  public function __construct()
  {
    $this->model = new Pembayaran_model();
    $this->pembayaranModel = $this->model;
    $this->transaksiModel = new Transaksi_model();
  }

  // GET /pembayaran - daftar pembayaran DP milik penyewa yang login.
  public function index(): void
  {
    $userPlg = $this->wajibLoginPenyewa();
    $this->wajibMetode('GET');

    try {
      $daftar = $this->model->getDaftarPenyewa($userPlg);
      $jumlahKeranjang = $this->transaksiModel->getJumlahMobilKeranjang($userPlg);
    } catch (Throwable $e) {
      error_log('Daftar pembayaran penyewa gagal: ' . $e->getMessage());
      http_response_code(500);
      exit('Daftar pembayaran belum dapat ditampilkan. Silakan coba kembali.');
    }

    $this->view('pembayaran/daftar', [
      'daftar' => $daftar,
      'flash' => $this->ambilFlash(),
      'jumlahKeranjang' => $jumlahKeranjang,
    ]);
  }

  // GET /pembayaran/detail?kode_sewa=INV/... - rincian DP + formulir unggah bukti.
  public function detail(): void
  {
    $userPlg = $this->wajibLoginPenyewa();
    $this->wajibMetode('GET');

    $kodeSewa = trim((string) ($_GET['kode_sewa'] ?? ''));
    if ($kodeSewa === '') {
      $this->redirectPesan('pembayaran', 'error', 'Kode transaksi wajib diisi.');
    }

    try {
      // Query memfilter user_plg: transaksi milik orang lain tidak pernah terbaca.
      $pembayaran = $this->model->getDpPenyewa($kodeSewa, $userPlg);
      $jumlahKeranjang = $this->transaksiModel->getJumlahMobilKeranjang($userPlg);
    } catch (Throwable $e) {
      error_log('Detail pembayaran penyewa gagal: ' . $e->getMessage());
      http_response_code(500);
      exit('Detail pembayaran belum dapat ditampilkan. Silakan coba kembali.');
    }

    if ($pembayaran === null) {
      $this->tolakAkses();
    }

    $this->view('pembayaran/detail', [
      'pembayaran' => $pembayaran,
      'flash' => $this->ambilFlash(),
      'jumlahKeranjang' => $jumlahKeranjang,
    ]);
  }

  // POST /pembayaran/unggah - menyimpan bukti transfer, lalu PRG ke halaman detail.
  public function unggah(): void
  {
    $userPlg = $this->wajibLoginPenyewa();
    $this->wajibMetode('POST');

    // Jika post_max_size terlampaui, PHP mengosongkan $_POST dan $_FILES sekaligus.
    if (
      empty($_POST)
      && empty($_FILES)
      && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0
    ) {
      $this->redirectPesan('pembayaran', 'error', self::PESAN_UKURAN);
    }

    // Nominal TIDAK dibaca dari formulir; hanya kode transaksi dan berkas.
    $kodeSewa = trim((string) ($_POST['kode_sewa'] ?? ''));
    if ($kodeSewa === '') {
      $this->redirectPesan('pembayaran', 'error', 'Kode transaksi wajib diisi.');
    }
    $urlDetail = 'pembayaran/detail?kode_sewa=' . rawurlencode($kodeSewa);

    // Periksa kepemilikan dan status SEBELUM menyentuh berkas.
    try {
      $pembayaran = $this->model->getDpPenyewa($kodeSewa, $userPlg);
    } catch (Throwable $e) {
      error_log('Pemeriksaan pembayaran gagal: ' . $e->getMessage());
      $this->redirectPesan('pembayaran', 'error', 'Permintaan belum berhasil diproses. Silakan coba kembali.');
    }

    if ($pembayaran === null) {
      $this->tolakAkses();
    }
    if (($pembayaran['sttus_byr'] ?? '') !== 'pending') {
      $this->redirectPesan(
        $urlDetail,
        'error',
        'Pembayaran sudah diverifikasi dan tidak dapat diubah kembali.'
      );
    }

    $upload = $this->simpanBerkas('ft_byr');
    if (!$upload['success']) {
      $this->redirectPesan($urlDetail, 'error', (string) $upload['error']);
    }
    $fileBaru = (string) $upload['filename'];

    try {
      $fileLama = $this->model->simpanBukti($kodeSewa, $userPlg, $fileBaru);
    } catch (PembayaranException $e) {
      $this->hapusBerkas($fileBaru);
      $this->redirectPesan($urlDetail, 'error', $e->getMessage());
    } catch (Throwable $e) {
      error_log('Simpan bukti pembayaran gagal: ' . $e->getMessage());
      $this->hapusBerkas($fileBaru);
      $this->redirectPesan(
        $urlDetail,
        'error',
        'Bukti pembayaran belum berhasil disimpan. Silakan coba kembali.'
      );
    }

    // Berkas lama (jika ini unggah ulang) dihapus hanya setelah database berhasil.
    if ($fileLama !== null) {
      $this->hapusBerkas($fileLama);
    }

    $this->redirectPesan(
      $urlDetail,
      'success',
      'Bukti pembayaran berhasil diunggah. Status pembayaran tetap pending sampai diverifikasi admin.'
    );
  }

  // ---------------------------------------------------------------
  // Helper private (tidak dapat dipanggil lewat Router)
  // ---------------------------------------------------------------

  private function wajibLoginPenyewa(): string
  {
    $userPlg = trim((string) ($_SESSION['penyewa_user_plg'] ?? ''));
    if ($userPlg === '') {
      $_SESSION['flash_penyewa_nav'] =
        'Silakan login terlebih dahulu untuk mengakses pembayaran.';
      header('Location: ' . site_url('auth/login-penyewa'));
      exit;
    }
    return $userPlg;
  }

  private function wajibMetode(string $metodeDiizinkan): void
  {
    $metodeAktif = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
    if ($metodeAktif !== $metodeDiizinkan) {
      http_response_code(405);
      header('Allow: ' . $metodeDiizinkan);
      exit('Method Not Allowed');
    }
  }

  // Respons yang sama untuk "tidak ada" dan "milik orang lain" agar tidak membocorkan data.
  private function tolakAkses(): never
  {
    http_response_code(403);
    exit('Akses ditolak. Pembayaran tidak ditemukan atau bukan milik akun ini.');
  }

  private function redirectPesan(string $uri, string $jenis, string $pesan): never
  {
    $_SESSION['flash_pembayaran'] = [
      'type' => $jenis,
      'message' => $pesan,
    ];
    header('Location: ' . site_url($uri));
    exit;
  }

  private function ambilFlash(): ?array
  {
    $flash = $_SESSION['flash_pembayaran'] ?? null;
    unset($_SESSION['flash_pembayaran']);
    return is_array($flash) ? $flash : null;
  }

  // Validasi jenis (ekstensi), MIME type nyata, isi gambar, dan ukuran; lalu simpan.
  private function simpanBerkas(string $field): array
  {
    $gagal = static fn(string $pesan): array => [
      'success' => false,
      'filename' => null,
      'error' => $pesan,
    ];

    if (!isset($_FILES[$field]) || !is_array($_FILES[$field])) {
      return $gagal('Bukti pembayaran wajib dipilih.');
    }

    $file = $_FILES[$field];
    $kodeError = $file['error'] ?? UPLOAD_ERR_NO_FILE;

    if (is_array($kodeError)) {
      return $gagal('Berkas unggahan tidak valid.');
    }
    if ($kodeError === UPLOAD_ERR_NO_FILE) {
      return $gagal('Bukti pembayaran wajib dipilih.');
    }
    if ($kodeError === UPLOAD_ERR_INI_SIZE || $kodeError === UPLOAD_ERR_FORM_SIZE) {
      return $gagal(self::PESAN_UKURAN);
    }
    if ($kodeError !== UPLOAD_ERR_OK) {
      return $gagal('Unggah berkas gagal. Silakan coba kembali.');
    }

    $tmp = (string) ($file['tmp_name'] ?? '');
    $ukuran = (int) ($file['size'] ?? 0);

    if ($tmp === '' || !is_uploaded_file($tmp)) {
      return $gagal('Berkas unggahan tidak valid.');
    }
    if ($ukuran < 1) {
      return $gagal('Berkas tidak boleh kosong.');
    }
    if ($ukuran > self::BATAS_UKURAN) {
      return $gagal(self::PESAN_UKURAN);
    }

    $ekstensiAsli = strtolower(
      pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION)
    );
    if (!in_array($ekstensiAsli, self::EKSTENSI_DIIZINKAN, true)) {
      return $gagal('Jenis berkas tidak diizinkan. Gunakan gambar JPG, PNG, atau WEBP.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($tmp);
    if (
      $mime === false
      || !isset(self::MIME_DIIZINKAN[$mime])
      || @getimagesize($tmp) === false
    ) {
      return $gagal('Isi berkas bukan gambar JPG, PNG, atau WEBP yang valid.');
    }

    $uploadDir = dirname(APPPATH) . DIRECTORY_SEPARATOR . 'uploads'
      . DIRECTORY_SEPARATOR . 'bayar' . DIRECTORY_SEPARATOR;

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
      return $gagal('Folder penyimpanan bukti pembayaran tidak dapat dibuat.');
    }
    if (!is_writable($uploadDir)) {
      return $gagal('Folder penyimpanan bukti pembayaran tidak dapat ditulis.');
    }

    // Nama dibuat server: tanpa nama asli pengguna => tidak bentrok, tidak dapat dimanipulasi.
    try {
      $namaFile = $field . '_' . date('YmdHis') . '_'
        . bin2hex(random_bytes(8)) . '.' . self::MIME_DIIZINKAN[$mime];
    } catch (Throwable $e) {
      return $gagal('Nama berkas gagal dibuat. Silakan coba kembali.');
    }

    if (!move_uploaded_file($tmp, $uploadDir . $namaFile)) {
      return $gagal('Berkas gagal dipindahkan ke folder penyimpanan.');
    }

    return ['success' => true, 'filename' => $namaFile, 'error' => null];
  }

  // Hanya menghapus berkas yang namanya cocok pola buatan server (anti path traversal).
  private function hapusBerkas(string $namaFile): void
  {
    if (!preg_match('/\Aft_byr_\d{14}_[a-f0-9]{16}\.(jpg|png|webp)\z/', $namaFile)) {
      return;
    }
    $path = dirname(APPPATH) . DIRECTORY_SEPARATOR . 'uploads'
      . DIRECTORY_SEPARATOR . 'bayar' . DIRECTORY_SEPARATOR . $namaFile;
    if (is_file($path)) {
      @unlink($path);
    }
  }
  // ---------------------------------------------------------------
  // Penghubung P10 -> helper P9 (nama method di modul P10 berbeda)
  // ---------------------------------------------------------------

  // Pola PRG: simpan flash lalu redirect (memakai redirectPesan milik P9).
  private function redirectDenganPesan(string $uri, string $jenis, string $pesan): never
  {
    $this->redirectPesan($uri, $jenis, $pesan);
  }

  // Validasi + simpan berkas bukti (memakai simpanBerkas milik P9).
  private function simpanFileBukti(string $field): array
  {
    return $this->simpanBerkas($field);
  }

  // Hapus berkas bukti baru bila proses gagal (memakai hapusBerkas milik P9).
  private function hapusBuktiBaru(string $namaFile): void
  {
    $this->hapusBerkas($namaFile);
  }

  // ---------------------------------------------------------------
  // Sisi admin - Pelunasan & Dokumen Transaksi (P10)
  // ---------------------------------------------------------------

  /**
   * P10: Menampilkan daftar transaksi yang masih memiliki kewajiban pelunasan.
   */
  public function pelunasanIndex(): void
  {
    require_admin_login();
    $this->wajibMetode('GET');

    try {
      $daftarPelunasan = $this->pembayaranModel->getDaftarPelunasan();
      $flash = $_SESSION['flash_pembayaran'] ?? null;
      unset($_SESSION['flash_pembayaran']);

      $this->view('admin/pelunasan', [
        'username' => $_SESSION['admin_username'],
        'daftarPelunasan' => $daftarPelunasan,
        'flash' => $flash
      ]);
    } catch (Throwable $e) {
      error_log(
        'Daftar pelunasan gagal ditampilkan: ' . $e->getMessage()
      );
      http_response_code(500);
      exit('Daftar transaksi pelunasan belum dapat ditampilkan. Silakan coba kembali.');
    }
  }

  /**
   * P10: Menampilkan detail transaksi, riwayat pembayaran, dan sisa kewajiban.
   */
  public function pelunasanDetail(string $kodeSewa = ''): void
  {
    require_admin_login();
    $this->wajibMetode('GET');

    $kodeSewa = trim(str_replace('~', '/', $kodeSewa));
    if ($kodeSewa === '') {
      http_response_code(400);
      exit('Kode transaksi sewa wajib tersedia.');
    }

    try {
      $detailPelunasan = $this->pembayaranModel->getDetailPelunasan($kodeSewa);
      if ($detailPelunasan === null) {
        http_response_code(404);
        exit('Transaksi sewa tidak ditemukan.');
      }

      $flash = $_SESSION['flash_pembayaran'] ?? null;
      unset($_SESSION['flash_pembayaran']);

      $this->view('admin/pelunasan_detail', [
        'username' => $_SESSION['admin_username'],
        'detailPelunasan' => $detailPelunasan,
        'flash' => $flash
      ]);
    } catch (Throwable $e) {
      error_log(
        'Detail pelunasan gagal ditampilkan: ' . $e->getMessage()
      );
      http_response_code(500);
      exit('Detail transaksi pelunasan belum dapat ditampilkan. Silakan coba kembali.');
    }
  }

  /**
   * P10: Mencatat pelunasan yang diterima langsung oleh admin.
   * Tunai: tidak memerlukan unggahan bukti.
   * Transfer: admin mengunggah bukti yang diterima dari penyewa.
   * Model menghitung nominal sisa kewajiban dan menyimpan pembayaran
   * dengan status valid dalam satu transaksi database.
   */
  public function prosesPelunasan(string $kodeSewa = ''): void
  {
    require_admin_login();
    $this->wajibMetode('POST');

    $kodeSewa = trim(str_replace('~', '/', $kodeSewa));
    $metodeBayar = trim((string) ($_POST['mtode_byr'] ?? ''));

    if ($kodeSewa === '') {
      http_response_code(400);
      exit('Kode transaksi sewa wajib tersedia.');
    }

    if (!in_array($metodeBayar, ['tunai', 'transfer'], true)) {
      $this->redirectDenganPesan(
        'admin/pembayaran/pelunasan/' . str_replace('/', '~', $kodeSewa),
        'error',
        'Metode pembayaran harus tunai atau transfer.'
      );
    }

    $namaFileBukti = null;
    if ($metodeBayar === 'transfer') {
      $hasilUpload = $this->simpanFileBukti('ft_byr');
      if (!$hasilUpload['success']) {
        $this->redirectDenganPesan(
          'admin/pembayaran/pelunasan/' . str_replace('/', '~', $kodeSewa),
          'error',
          $hasilUpload['error']
        );
      }
      $namaFileBukti = (string) $hasilUpload['filename'];
    }

    try {
      $kodeBayar = $this->pembayaranModel->simpanPelunasan(
        $kodeSewa,
        $metodeBayar,
        $namaFileBukti
      );

      $this->redirectDenganPesan(
        'admin/pembayaran/pelunasan/' . str_replace('/', '~', $kodeSewa),
        'success',
        "Pelunasan berhasil dicatat dengan kode pembayaran {$kodeBayar}. Status pembayaran: valid; status transaksi: lunas."
      );
    } catch (InvalidArgumentException $e) {
      if ($namaFileBukti !== null) {
        $this->hapusBuktiBaru($namaFileBukti);
      }
      $this->redirectDenganPesan(
        'admin/pembayaran/pelunasan/' . str_replace('/', '~', $kodeSewa),
        'error',
        $e->getMessage()
      );
    } catch (Throwable $e) {
      error_log(
        'Proses pelunasan gagal: ' . $e->getMessage()
      );
      if ($namaFileBukti !== null) {
        $this->hapusBuktiBaru($namaFileBukti);
      }
      $this->redirectDenganPesan(
        'admin/pembayaran/pelunasan/' . str_replace('/', '~', $kodeSewa),
        'error',
        'Pelunasan belum berhasil dicatat. Pastikan transaksi masih memiliki sisa kewajiban dan belum pernah dilunasi.'
      );
    }
  }

  /**
   * P10: Menampilkan dokumen transaksi yang siap dicetak.
   * Data disusun Model dari t_sewa, t_penyewa, t_pilih, t_mobil,
   * dan t_bayar. Method ini hanya menyajikan data.
   */
  public function dokumenTransaksi(string $kodeSewa = ''): void
  {
    require_admin_login();
    $this->wajibMetode('GET');

    $kodeSewa = trim(str_replace('~', '/', $kodeSewa));
    if ($kodeSewa === '') {
      http_response_code(400);
      exit('Kode transaksi sewa wajib tersedia.');
    }

    try {
      $detailPelunasan = $this->pembayaranModel->getDetailPelunasan($kodeSewa);
      if ($detailPelunasan === null) {
        http_response_code(404);
        exit('Data transaksi tidak ditemukan.');
      }

      $this->view('admin/dokumen_transaksi', [
        'detailPelunasan' => $detailPelunasan
      ]);
    } catch (Throwable $e) {
      error_log(
        'Dokumen transaksi gagal ditampilkan: ' . $e->getMessage()
      );
      http_response_code(500);
      exit('Dokumen transaksi belum dapat ditampilkan. Silakan coba kembali.');
    }
  }
}