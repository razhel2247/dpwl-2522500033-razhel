<?php
// UTS DPWL: CRUD Manajemen Penyewa (sisi Admin).
// Pola sama dengan Mobil_controller: tabel + form berada pada SATU view (admin/mpenyewa_view).
class Mpenyewa_controller extends Controller
{
  private const FOTO_FIELDS = ['ft_ktp', 'ft_sim_a'];
  private const REGEX_USER = '/\A[A-Za-z0-9._-]{1,15}\z/';

  private Mpenyewa_model $model;

  public function __construct()
  {
    require_once APPPATH . 'models/Mpenyewa_model.php';
    require_once APPPATH . 'helpers/auth_helper.php';
    require_once APPPATH . 'helpers/flash_helper.php';
    $this->model = new Mpenyewa_model();
  }

  /* ------------------------------------------------------------------ *
   *  READ: halaman daftar penyewa
   * ------------------------------------------------------------------ */
  public function index(): void
  {
    require_admin_login(); // semua method wajib login admin
    $this->render(null, null, null, get_flash_message());
  }

  /* ------------------------------------------------------------------ *
   *  CREATE
   * ------------------------------------------------------------------ */
  public function tambah(): void
  {
    require_admin_login();

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $this->render('tambah', $this->dataKosong());
      return;
    }
    $this->wajibPost();
    $this->periksaRequest('admin/penyewa');

    $password = (string) ($_POST['sandi'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi_sandi'] ?? '');
    $data = $this->ambilInput(trim((string) ($_POST['user_plg'] ?? '')));
    $data['sandi'] = '';

    $error = $this->validasi($data, $password, $konfirmasi, false);
    if ($error !== null) {
      $this->render('tambah', $data, $error);
      return;
    }

    try {
      if ($this->model->findByUserPlg($data['user_plg']) !== null) {
        $this->render('tambah', $data, 'Username sudah digunakan penyewa lain.');
        return;
      }
      if ($this->model->nikSudahDipakai($data['nik'])) {
        $this->render('tambah', $data, 'NIK sudah terdaftar pada penyewa lain.');
        return;
      }
    } catch (Throwable $e) {
      error_log('Cek duplikat penyewa gagal: ' . $e->getMessage());
      $this->render('tambah', $data, 'Pemeriksaan data gagal. Silakan coba kembali.');
      return;
    }

    // Unggah foto. Jika salah satu gagal, file yang sudah terlanjur tersimpan dibersihkan.
    $baru = [];
    foreach (self::FOTO_FIELDS as $field) {
      $up = $this->uploadFoto($field);
      if (!$up['success']) {
        $this->hapusFile($baru);
        $this->render('tambah', $data, $up['error']);
        return;
      }
      if ($up['filename'] !== null) {
        $baru[] = $up['filename'];
        $data[$field] = $up['filename'];
      }
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    if ($hash === false) {
      $this->hapusFile($baru);
      $this->render('tambah', $data, 'Pembuatan hash password gagal.');
      return;
    }
    $data['sandi'] = $hash;

    try {
      $ok = $this->model->create($data);
    } catch (Throwable $e) {
      error_log('Simpan penyewa gagal: ' . $e->getMessage());
      $ok = false;
    }
    if (!$ok) {
      $this->hapusFile($baru); // DB gagal => foto baru dibuang
      $data['sandi'] = '';
      $this->render('tambah', $data, 'Data penyewa gagal disimpan. Periksa kembali data yang dimasukkan.');
      return;
    }

    // PRG: POST -> flash -> redirect -> GET
    set_flash_message('success', 'Data penyewa berhasil ditambahkan.');
    $this->redirectDaftar();
  }

  /* ------------------------------------------------------------------ *
   *  UPDATE
   * ------------------------------------------------------------------ */
  public function ubah(string $userPlg): void
  {
    require_admin_login();

    $lama = preg_match(self::REGEX_USER, $userPlg) === 1
      ? $this->cariPenyewa($userPlg)
      : null;
    if ($lama === null) {
      set_flash_message('danger', 'Data penyewa tidak ditemukan.');
      $this->redirectDaftar();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $this->render('ubah', $lama);
      return;
    }
    $this->wajibPost();
    $this->periksaRequest('admin/penyewa/ubah/' . rawurlencode($userPlg));

    $password = (string) ($_POST['sandi'] ?? '');
    $konfirmasi = (string) ($_POST['konfirmasi_sandi'] ?? '');
    // Username = kunci record, selalu dari route (bukan dari form).
    $data = $this->ambilInput($userPlg);
    $data['ft_ktp'] = $lama['ft_ktp'];
    $data['ft_sim_a'] = $lama['ft_sim_a'];

    $error = $this->validasi($data, $password, $konfirmasi, true);
    if ($error === null) {
      try {
        if ($this->model->nikSudahDipakai($data['nik'], $userPlg)) {
          $error = 'NIK sudah terdaftar pada penyewa lain.';
        }
      } catch (Throwable $e) {
        error_log('Cek NIK penyewa gagal: ' . $e->getMessage());
        $error = 'Pemeriksaan data gagal. Silakan coba kembali.';
      }
    }
    if ($error !== null) {
      $this->render('ubah', $data, $error);
      return;
    }

    $baru = [];
    $lamaDibuang = [];
    foreach (self::FOTO_FIELDS as $field) {
      $up = $this->uploadFoto($field);
      if (!$up['success']) {
        $this->hapusFile($baru);
        $this->render('ubah', $data, $up['error']);
        return;
      }
      if ($up['filename'] !== null) {
        // Ada file baru: ganti, foto lama dihapus SETELAH database sukses.
        $baru[] = $up['filename'];
        if (!empty($lama[$field])) {
          $lamaDibuang[] = $lama[$field];
        }
        $data[$field] = $up['filename'];
      } elseif (!empty($_POST['hapus_' . $field])) {
        // Tanpa file baru, tetapi admin mencentang "hapus foto".
        if (!empty($lama[$field])) {
          $lamaDibuang[] = $lama[$field];
        }
        $data[$field] = null;
      }
    }

    $hash = null;
    if ($password !== '') {
      $hash = password_hash($password, PASSWORD_DEFAULT);
      if ($hash === false) {
        $this->hapusFile($baru);
        $this->render('ubah', $data, 'Pembuatan hash password gagal.');
        return;
      }
    }

    try {
      $ok = $this->model->update($userPlg, $data, $hash);
    } catch (Throwable $e) {
      error_log('Ubah penyewa gagal: ' . $e->getMessage());
      $ok = false;
    }
    if (!$ok) {
      $this->hapusFile($baru);
      $data['ft_ktp'] = $lama['ft_ktp'];
      $data['ft_sim_a'] = $lama['ft_sim_a'];
      $this->render('ubah', $data, 'Data penyewa gagal diperbarui. Periksa kembali data yang dimasukkan.');
      return;
    }

    $this->hapusFile($lamaDibuang);
    set_flash_message('success', 'Data penyewa berhasil diperbarui.');
    $this->redirectDaftar();
  }

  /* ------------------------------------------------------------------ *
   *  DELETE (wajib POST)
   * ------------------------------------------------------------------ */
  public function hapus(string $userPlg): void
  {
    require_admin_login();
    $this->wajibPost();
    $this->periksaRequest('admin/penyewa');

    $penyewa = preg_match(self::REGEX_USER, $userPlg) === 1
      ? $this->cariPenyewa($userPlg)
      : null;
    if ($penyewa === null) {
      set_flash_message('danger', 'Data penyewa tidak ditemukan.');
      $this->redirectDaftar();
    }

    try {
      // Penyewa yang punya riwayat sewa tidak boleh dihapus (menjaga integritas data transaksi).
      $jumlah = $this->model->jumlahSewa($userPlg);
      if ($jumlah > 0) {
        set_flash_message(
          'danger',
          'Penyewa "' . $userPlg . '" tidak dapat dihapus karena memiliki ' . $jumlah . ' riwayat sewa.'
        );
        $this->redirectDaftar();
      }
      $ok = $this->model->delete($userPlg);
    } catch (Throwable $e) {
      error_log('Hapus penyewa gagal: ' . $e->getMessage());
      $ok = false;
    }
    if (!$ok) {
      set_flash_message('danger', 'Data penyewa gagal dihapus.');
      $this->redirectDaftar();
    }

    // Foto baru dihapus dari disk hanya setelah baris database benar-benar terhapus.
    $this->hapusFile(array_filter([$penyewa['ft_ktp'] ?? null, $penyewa['ft_sim_a'] ?? null]));
    set_flash_message('success', 'Data penyewa berhasil dihapus.');
    $this->redirectDaftar();
  }

  /* ================================================================== *
   *  HELPER PRIVAT
   * ================================================================== */

  private function dataKosong(): array
  {
    return [
      'user_plg' => '',
      'nik' => '',
      'nm_lngkp' => '',
      'no_hp' => '',
      'almt_lngkp' => '',
      'ft_ktp' => null,
      'ft_sim_a' => null,
    ];
  }

  // Sanitasi dasar: trim + paksa string. Password sengaja tidak di-trim.
  private function ambilInput(string $userPlg): array
  {
    return [
      'user_plg' => $userPlg,
      'nik' => trim((string) ($_POST['nik'] ?? '')),
      'nm_lngkp' => trim((string) ($_POST['nm_lngkp'] ?? '')),
      'no_hp' => trim((string) ($_POST['no_hp'] ?? '')),
      'almt_lngkp' => trim((string) ($_POST['almt_lngkp'] ?? '')),
      'ft_ktp' => null,
      'ft_sim_a' => null,
    ];
  }

  private function validasi(array $d, string $password, string $konfirmasi, bool $isEdit): ?string
  {
    if (!$isEdit && preg_match(self::REGEX_USER, $d['user_plg']) !== 1) {
      return 'Username harus 1–15 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.';
    }
    // Tambah: password wajib. Ubah: boleh kosong (hash lama dipertahankan).
    if (!$isEdit || $password !== '' || $konfirmasi !== '') {
      if (strlen($password) < 8) {
        return 'Password minimal 8 karakter.';
      }
      if (strlen($password) > 72) {
        return 'Password maksimal 72 karakter.';
      }
      if ($password !== $konfirmasi) {
        return 'Konfirmasi password tidak sama.';
      }
    }
    if (preg_match('/\A[0-9]{16}\z/', $d['nik']) !== 1) {
      return 'NIK wajib terdiri dari tepat 16 digit angka.';
    }
    if (
      $d['nm_lngkp'] === '' ||
      mb_strlen($d['nm_lngkp']) > 100 ||
      preg_match("/\A[\p{L}\p{M}]+(?:[ .'-][\p{L}\p{M}]+)*\z/u", $d['nm_lngkp']) !== 1
    ) {
      return 'Nama lengkap wajib diisi, maksimal 100 karakter, dan hanya berisi huruf serta pemisah nama yang wajar.';
    }
    if (
      $d['no_hp'] === '' ||
      strlen($d['no_hp']) > 15 ||
      preg_match('/\A[0-9+(). -]+\z/', $d['no_hp']) !== 1 ||
      preg_match('/[0-9]/', $d['no_hp']) !== 1
    ) {
      return 'Nomor HP wajib diisi, maksimal 15 karakter, dan hanya boleh berisi angka serta tanda + . ( ) - spasi.';
    }
    if (
      $d['almt_lngkp'] === '' ||
      mb_strlen($d['almt_lngkp']) > 300 ||
      preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', $d['almt_lngkp']) === 1
    ) {
      return 'Alamat wajib diisi, maksimal 300 karakter, dan tidak boleh mengandung karakter kontrol.';
    }
    return null;
  }

  private function cariPenyewa(string $userPlg): ?array
  {
    try {
      return $this->model->findByUserPlg($userPlg);
    } catch (Throwable $e) {
      error_log('Baca penyewa gagal: ' . $e->getMessage());
      return null;
    }
  }

  // Menyatukan semua pemanggilan view agar data yang dikirim ke View selalu lengkap.
  private function render(
    ?string $mode,
    ?array $dipilih = null,
    ?string $error = null,
    ?array $flash = null
  ): void {
    try {
      $daftar = $this->model->getAll();
    } catch (Throwable $e) {
      error_log('Daftar penyewa gagal dimuat: ' . $e->getMessage());
      $daftar = [];
      $error = $error ?? 'Data penyewa gagal dimuat dari basis data.';
    }
    $this->view('admin/mpenyewa_view', [
      'username' => $_SESSION['admin_username'],
      'penyewa' => $daftar,
      'flash' => $flash,
      'mode' => $mode,
      'penyewa_dipilih' => $dipilih,
      'error' => $error,
      'csrf' => $this->tokenCsrf(),
    ]);
  }

  private function redirectDaftar(): never
  {
    header('Location: ' . site_url('admin/penyewa'));
    exit;
  }

  private function wajibPost(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      header('Allow: POST');
      exit('Metode request tidak diizinkan.');
    }
  }

  // Dipanggil di awal setiap POST: cek ukuran request dan token CSRF.
  private function periksaRequest(string $kembaliKe): void
  {
    // Jika body melebihi post_max_size, PHP mengosongkan $_POST dan $_FILES.
    if (empty($_POST) && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
      set_flash_message('danger', 'Data yang dikirim melebihi batas ukuran server. Perkecil ukuran foto lalu coba lagi.');
      header('Location: ' . site_url($kembaliKe));
      exit;
    }
    $tersimpan = (string) ($_SESSION['csrf_mpenyewa'] ?? '');
    $dikirim = (string) ($_POST['csrf_token'] ?? '');
    if ($tersimpan === '' || !hash_equals($tersimpan, $dikirim)) {
      set_flash_message('danger', 'Sesi formulir tidak valid. Muat ulang halaman lalu coba lagi.');
      $this->redirectDaftar();
    }
  }

  private function tokenCsrf(): string
  {
    if (empty($_SESSION['csrf_mpenyewa'])) {
      $_SESSION['csrf_mpenyewa'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_mpenyewa'];
  }

  private function folderFoto(): string
  {
    return dirname(APPPATH) . DIRECTORY_SEPARATOR . 'uploads'
      . DIRECTORY_SEPARATOR . 'penyewa' . DIRECTORY_SEPARATOR;
  }

  // Menghapus file foto. Hanya nama file ber-pola buatan uploadFoto() yang diizinkan
  // (mencegah path traversal). Pola juga cocok dengan foto dari halaman profil penyewa.
  private function hapusFile(array $filenames): void
  {
    $dir = $this->folderFoto();
    foreach ($filenames as $filename) {
      if (
        is_string($filename) &&
        preg_match('/^(ft_ktp|ft_sim_a)_\d{10,14}_[a-f0-9]{12,16}\.(jpg|jpeg|png|webp)$/', $filename) === 1
      ) {
        $path = $dir . $filename;
        if (is_file($path)) {
          @unlink($path);
        }
      }
    }
  }

  // Validasi + simpan satu foto. Mengembalikan [success, filename, error].
  // Tidak memilih file = sukses dengan filename null (foto lama dipertahankan).
  private function uploadFoto(string $field): array
  {
    $gagal = static fn(string $pesan): array =>
      ['success' => false, 'filename' => null, 'error' => $pesan];

    if (!in_array($field, self::FOTO_FIELDS, true)) {
      return $gagal('Field foto tidak valid.');
    }
    if (!isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
      return ['success' => true, 'filename' => null, 'error' => null];
    }
    $file = $_FILES[$field];
    $label = $field === 'ft_ktp' ? 'Foto KTP' : 'Foto SIM A';

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      $pesan = match ($file['error']) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => $label . ' melebihi batas ukuran yang diizinkan server.',
        UPLOAD_ERR_PARTIAL => $label . ' hanya terunggah sebagian. Silakan ulangi.',
        default => 'Unggah ' . $label . ' gagal. Periksa ukuran file dan konfigurasi PHP.',
      };
      return $gagal($pesan);
    }
    if (
      !isset($file['tmp_name'], $file['size']) ||
      !is_uploaded_file($file['tmp_name']) ||
      (int) $file['size'] < 1 ||
      (int) $file['size'] > 5 * 1024 * 1024
    ) {
      return $gagal($label . ' harus berukuran lebih dari 0 byte dan maksimal 5 MB.');
    }

    // Jenis file dicek dari ISI file (bukan dari nama/ekstensi kiriman pengguna).
    $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
    $diizinkan = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
    if (!isset($diizinkan[$mime]) || @getimagesize($file['tmp_name']) === false) {
      return $gagal('Format ' . $label . ' harus JPEG, PNG, atau WEBP yang valid.');
    }

    $dir = $this->folderFoto();
    if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
      return $gagal('Folder penyimpanan foto penyewa tidak dapat dibuat.');
    }
    if (!is_writable($dir)) {
      return $gagal('Folder penyimpanan foto penyewa tidak dapat ditulis.');
    }

    try {
      // Nama acak buatan server: aman dari tabrakan dan dari nama berbahaya.
      $filename = $field . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' . $diizinkan[$mime];
    } catch (Throwable $e) {
      return $gagal('Nama file foto gagal dibuat.');
    }
    if (!move_uploaded_file($file['tmp_name'], $dir . $filename)) {
      return $gagal($label . ' gagal dipindahkan ke folder penyimpanan.');
    }
    return ['success' => true, 'filename' => $filename, 'error' => null];
  }
}