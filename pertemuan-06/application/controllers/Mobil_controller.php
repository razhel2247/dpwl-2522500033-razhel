<?php
// P5: CRUD Mobil
class Mobil_controller extends Controller
{
  private Mobil_model $model;
  public function __construct()
  {
    require_once APPPATH . 'models/Mobil_model.php';
    require_once APPPATH . 'helpers/auth_helper.php';
    require_once APPPATH . 'helpers/flash_helper.php';
    $this->model = new Mobil_model();
  }


  public function index(): void
  {
    //semua halaman/proses Madmin wajib terlindungi.
    require_admin_login();
    //tabel dan form berada pada satu View: mobil_view.php.
    $this->view('admin/mobil_view', [
      'username' => $_SESSION['admin_username'],
      'mobil' => $this->model->getAll(),
      'flash' => get_flash_message(),
      'mode' => null,
      'mobil_dipilih' => null,
      'error' => null,
    ]);
  }

  //kode method hapusFotoBaru
  private function hapusFotoBaru(array $filenames): void
  {
    $uploadDir = dirname(APPPATH) . DIRECTORY_SEPARATOR . 'uploads'
      . DIRECTORY_SEPARATOR . 'mobil' . DIRECTORY_SEPARATOR;
    foreach ($filenames as $filename) {
      // Hanya hapus nama file yang dibuat oleh pola fungsi uploadFoto().
      if (
        is_string($filename) &&
        preg_match(
          '/^(ft_depan|ft_blkg|ft_interior)_\d{14}_[a-f0-9]{16}\.(jpg|png|webp)$/',
          $filename
        )
      ) {
        $path = $uploadDir . $filename;
        if (is_file($path)) {
          @unlink($path);
        }
      }
    }
  }

  //kode method uploadFoto
  private function uploadFoto(string $field): array
  {
    if (!in_array($field, ['ft_depan', 'ft_blkg', 'ft_interior'], true)) {
      return ['success' => false, 'filename' => null, 'error' => 'Field foto tidak valid.'];
    }
    if (
      !isset($_FILES[$field]) || ($_FILES[$field]['error'] ?? UPLOAD_ERR_NO_FILE) ===
      UPLOAD_ERR_NO_FILE
    ) {
      return ['success' => true, 'filename' => null, 'error' => null];
    }
    $file = $_FILES[$field];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
      return [
        'success' => false,
        'filename' => null,
        'error' => 'Unggah foto gagal. Periksa ukuran
file dan konfigurasi PHP.'
      ];
    }
    if (
      !isset($file['tmp_name'], $file['size']) ||
      !is_uploaded_file($file['tmp_name']) ||
      (int) $file['size'] < 1 ||
      (int) $file['size'] > 5 * 1024 * 1024
    ) {
      return [
        'success' => false,
        'filename' => null,
        'error' => 'Foto harus berukuran lebih dari 0
byte dan maksimal 5 MB.'
      ];
    }
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file($file['tmp_name']);
    $allowed = [
      'image/jpeg' => 'jpg',
      'image/png' => 'png',
      'image/webp' => 'webp',
    ];
    if (!isset($allowed[$mime]) || @getimagesize($file['tmp_name']) === false) {
      return [
        'success' => false,
        'filename' => null,
        'error' => 'Format foto harus JPEG, PNG, atau
WEBP yang valid.'
      ];
    }
    $projectRoot = dirname(APPPATH);
    $uploadDir = $projectRoot . DIRECTORY_SEPARATOR . 'uploads'
      . DIRECTORY_SEPARATOR . 'mobil' . DIRECTORY_SEPARATOR;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
      return [
        'success' => false,
        'filename' => null,
        'error' => 'Folder penyimpanan foto tidak
dapat dibuat.'
      ];
    }
    if (!is_writable($uploadDir)) {
      return [
        'success' => false,
        'filename' => null,
        'error' => 'Folder penyimpanan foto tidak
dapat ditulis.'
      ];
    }
    try {
      $filename = $field . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(8)) . '.' .
        $allowed[$mime];
    } catch (Throwable $e) {
      return ['success' => false, 'filename' => null, 'error' => 'Nama file foto gagal dibuat.'];
    }
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
      return [
        'success' => false,
        'filename' => null,
        'error' => 'Foto gagal dipindahkan ke folder
penyimpanan.'
      ];
    }
    return ['success' => true, 'filename' => $filename, 'error' => null];
  }


  //kode method validateMobilInput
  private function validateMobilInput(array $data): ?string
  {
    if (
      $data['no_plat'] === '' ||
      strlen($data['no_plat']) > 15 ||
      preg_match('/[\x00-\x1F\x7F]/', $data['no_plat'])
    ) {
      return 'Nomor plat wajib diisi dan maksimal 15 karakter.';
    }
    if (
      $data['nm_mobil'] === '' ||
      mb_strlen($data['nm_mobil']) > 40
    ) {
      return 'Nama mobil wajib diisi dan maksimal 40 karakter.';
    }
    // Kolom pada skema adalah TINYINT bertanda (signed).
    if (!is_int($data['nkursi']) || $data['nkursi'] < 1 || $data['nkursi'] > 25) {
      return 'Jumlah kursi harus berupa bilangan bulat antara 1 dan 25.';
    }
    if (!is_int($data['nbagasi']) || $data['nbagasi'] < 0 || $data['nbagasi'] > 30) {
      return 'Jumlah bagasi harus berupa bilangan bulat antara 0 dan 30.';
    }
    if (!in_array($data['jns_mobil'], ['SUV', 'MPV', 'City Car'], true)) {
      return 'Jenis mobil tidak valid.';
    }
    if (!in_array($data['transmisi'], ['AT', 'MT'], true)) {
      return 'Transmisi tidak valid.';
    }
    if (!is_int($data['thn_buat']) || $data['thn_buat'] < 1901 || $data['thn_buat'] > (int) date('Y')) {
      return 'Tahun pembuatan harus antara 1901 dan tahun berjalan.';
    }
    if (!is_int($data['hrg_hari']) || $data['hrg_hari'] < 1 || $data['hrg_hari'] > 16777215) {
      return 'Harga sewa harus berupa bilangan bulat positif yang sesuai batas kolom.';
    }
    if ($data['cttn_unit'] === '' || mb_strlen($data['cttn_unit']) > 500) {
      return 'Catatan unit wajib diisi dan maksimal 500 karakter.';
    }
    if (!in_array($data['status_unit'], ['tersedia', 'disewa', 'tidaktersedia'], true)) {
      return 'Status kendaraan tidak valid.';
    }
    return null;
  }


  //kode method renderMobil
  private function renderMobil(
    string $mode,
    ?array $mobilDipilih = null,
    ?string $error = null
  ): void {
    $this->view('admin/mobil_view', [
      'username' => $_SESSION['admin_username'],
      'mobil' => $this->model->getAll(),
      'flash' => null,
      'mode' => $mode,
      'mobil_dipilih' => $mobilDipilih,
      'error' => $error,
    ]);
  }


  //kode method tambah
  public function tambah(): void
  {
    require_admin_login();
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $this->renderMobil('tambah');
      return;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      exit('Metode request tidak diizinkan.');
    }
    $data = [
      'no_plat' => trim((string) ($_POST['no_plat'] ?? '')),
      'nm_mobil' => trim((string) ($_POST['nm_mobil'] ?? '')),
      'nkursi' => filter_var(
        $_POST['nkursi'] ?? null,
        FILTER_VALIDATE_INT
      ),
      'nbagasi' => filter_var(
        $_POST['nbagasi'] ?? null,
        FILTER_VALIDATE_INT
      ),
      'jns_mobil' => (string) ($_POST['jns_mobil'] ?? ''),
      'transmisi' => (string) ($_POST['transmisi'] ?? ''),
      'thn_buat' => filter_var(
        $_POST['thn_buat'] ?? null,
        FILTER_VALIDATE_INT
      ),
      'ft_depan' => null,
      'ft_blkg' => null,
      'ft_interior' => null,
      'hrg_hari' => filter_var(
        $_POST['hrg_hari'] ?? null,
        FILTER_VALIDATE_INT
      ),
      'cttn_unit' => trim((string) ($_POST['cttn_unit'] ?? '')),
      'status_unit' => (string) ($_POST['status_unit'] ?? ''),
    ];
    $error = $this->validateMobilInput($data);
    if ($error !== null) {
      $this->renderMobil('tambah', $data, $error);
      return;
    }
    // Nomor plat adalah PRIMARY KEY. Penanganan benturan akhir tetap dilakukan
// oleh Model/database saat INSERT untuk menghindari ketergantungan pada cek awal.
    $uploadedFiles = [];
    foreach (['ft_depan', 'ft_blkg', 'ft_interior'] as $field) {
      $upload = $this->uploadFoto($field);
      if (!$upload['success']) {
        $this->hapusFotoBaru($uploadedFiles);
        $this->renderMobil('tambah', $data, $upload['error']);
        return;
      }
      $data[$field] = $upload['filename'];
      if ($upload['filename'] !== null) {
        $uploadedFiles[] = $upload['filename'];
      }
    }
    try {
      $success = $this->model->create($data);
    } catch (Throwable $e) {
      $success = false;
    }
    if (!$success) {
      $this->hapusFotoBaru($uploadedFiles);
      $this->renderMobil(
        'tambah',
        $data,
        'Data kendaraan gagal disimpan. Periksa nomor plat dan data yang dimasukkan.'
      );
      return;
    }
    set_flash_message('success', 'Data kendaraan berhasil ditambahkan.');
    header('Location: ' . site_url('admin/mobil'));
    exit;
  }

  public function ubah(string $noPlat): void
  {
    require_admin_login();
    $noPlat = rawurldecode($noPlat);
    $mobilLama = $this->model->findByNoPlat($noPlat);
    if ($mobilLama === null) {
      set_flash_message('danger', 'Data kendaraan tidak ditemukan.');
      header('Location: ' . site_url('admin/mobil'));
      exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $this->renderMobil('ubah', $mobilLama);
      return;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      exit('Metode request tidak diizinkan.');
    }
    $data = [
      'no_plat' => $noPlat,
      'nm_mobil' => trim((string) ($_POST['nm_mobil'] ?? '')),
      'nkursi' => filter_var($_POST['nkursi'] ?? null, FILTER_VALIDATE_INT),
      'nbagasi' => filter_var($_POST['nbagasi'] ?? null, FILTER_VALIDATE_INT),
      'jns_mobil' => (string) ($_POST['jns_mobil'] ?? ''),
      'transmisi' => (string) ($_POST['transmisi'] ?? ''),
      'thn_buat' => filter_var($_POST['thn_buat'] ?? null, FILTER_VALIDATE_INT),
      'ft_depan' => $mobilLama['ft_depan'],
      'ft_blkg' => $mobilLama['ft_blkg'],
      'ft_interior' => $mobilLama['ft_interior'],
      'hrg_hari' => filter_var($_POST['hrg_hari'] ?? null, FILTER_VALIDATE_INT),
      'cttn_unit' => trim((string) ($_POST['cttn_unit'] ?? '')),
      'status_unit' => (string) ($_POST['status_unit'] ?? ''),
    ];
    $error = $this->validateMobilInput($data);
    if ($error !== null) {
      $this->renderMobil('ubah', array_merge($mobilLama, $data), $error);
      return;
    }
    $uploadedFiles = [];
    $fotoLamaUntukDihapus = [];
    foreach (['ft_depan', 'ft_blkg', 'ft_interior'] as $field) {
      $upload = $this->uploadFoto($field);
      if (!$upload['success']) {
        $this->hapusFotoBaru($uploadedFiles);
        $this->renderMobil('ubah', array_merge($mobilLama, $data), $upload['error']);
        return;
      }
      if ($upload['filename'] !== null) {
        $uploadedFiles[] = $upload['filename'];
        if (!empty($mobilLama[$field])) {
          $fotoLamaUntukDihapus[] = $mobilLama[$field];
        }
        $data[$field] = $upload['filename'];
      }
    }
    try {
      $success = $this->model->update($noPlat, $data);
    } catch (Throwable $e) {
      $success = false;
    }
    if (!$success) {
      $this->hapusFotoBaru($uploadedFiles);
      $this->renderMobil(
        'ubah',
        array_merge($mobilLama, $data),
        'Data kendaraan gagal diperbarui. Periksa kembali data yang dimasukkan.'
      );
      return;
    }
    $this->hapusFotoBaru($fotoLamaUntukDihapus);
    set_flash_message('success', 'Data kendaraan berhasil diperbarui.');
    header('Location: ' . site_url('admin/mobil'));
    exit;
  }

  public function hapus(string $noPlat): void
  {
    require_admin_login();
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      exit('Metode request tidak diizinkan.');
    }
    $noPlat = rawurldecode($noPlat);
    $mobil = $this->model->findByNoPlat($noPlat);
    if ($mobil === null) {
      set_flash_message('danger', 'Data kendaraan tidak ditemukan.');
      header('Location: ' . site_url('admin/mobil'));
      exit;
    }
    try {
      $success = $this->model->delete($noPlat);
    } catch (Throwable $e) {
      $success = false;
    }
    if (!$success) {
      set_flash_message('danger', 'Data kendaraan gagal dihapus.');
      header('Location: ' . site_url('admin/mobil'));
      exit;
    }
    $this->hapusFotoBaru(array_filter([
      $mobil['ft_depan'] ?? null,
      $mobil['ft_blkg'] ?? null,
      $mobil['ft_interior'] ?? null,
    ]));
    set_flash_message('success', 'Data kendaraan berhasil dihapus.');
    header('Location: ' . site_url('admin/mobil'));
    exit;
  }
}