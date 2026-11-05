<?php
// Controller khusus untuk pengelolaan data admin (CRUD).
class Madmin_controller extends Controller
{
  private Admin_model $model;
  public function __construct()
  {
    require_once APPPATH . 'models/Admin_model.php';
    require_once APPPATH . 'helpers/auth_helper.php';
    require_once APPPATH . 'helpers/flash_helper.php';
    $this->model = new Admin_model();
  }
  public function index(): void
  {
    //semua halaman/proses Madmin wajib terlindungi.
    require_admin_login();
    //Controller mengambil data; SQL tetap berada di Admin_model.php.
    $admins = $this->model->getAll();
    $flash = get_flash_message();
    //tabel dan form berada pada satu View: madmin_view.php.
    $this->view('admin/madmin_view', [
      'username' => $_SESSION['admin_username'],
      'admins' => $admins,
      'flash' => $flash,
      'mode' => null,
      'admin_dipilih' => null,
      'error' => null,
    ]);
  }
  public function tambah(): void
  {
    require_admin_login();
    //GET hanya menampilkan form; POST baru menjalankan proses CREATE.
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $this->renderMadmin('tambah', [
        'username' => '',
        'status_akun' => '',
      ]);
      return;
    }
    //metode selain GET/POST ditolak.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      exit('Metode request tidak diizinkan.');
    }
    //username dinormalisasi dengan trim(); password tidak di-trim().
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $statusAkun = $_POST['status_akun'] ?? '';
    //validasi dilakukan terpusat di Controller.
    $error = $this->validateInput($username, $password, $statusAkun, false);
    if ($error !== null) {
      $this->renderMadmin('tambah', [
        'username' => $username,
        'status_akun' => $statusAkun,
      ], $error);
      return;
    }
    //username adalah PRIMARY KEY sehingga harus dicek sebelum INSERT.
    if ($this->model->findByUsername($username) !== null) {
      $this->renderMadmin('tambah', [
        'username' => $username,
        'status_akun' => $statusAkun,
      ], 'Username sudah digunakan.');
      return;
    }
    //password tidak disimpan sebagai plainteks.
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    //Controller meminta Model menjalankan INSERT.
    if (!$this->model->create($username, $passwordHash, $statusAkun)) {
      $this->renderMadmin('tambah', [
        'username' => $username,
        'status_akun' => $statusAkun,
      ], 'Data admin gagal disimpan.');
      return;
    }
    //POST -> flash -> redirect -> GET ke /admin/madmin (PRG).
    set_flash_message('success', 'Data admin berhasil ditambahkan.');
    header('Location: ' . site_url('admin/madmin'));
    exit;
  }
  public function ubah(string $username): void
  {
    require_admin_login();
    //username berasal dari parameter route /admin/madmin/ubah/:username.
    $admin = $this->model->findByUsername($username);
    if ($admin === null) {
      set_flash_message('danger', 'Data admin tidak ditemukan.');
      header('Location: ' . site_url('admin/madmin'));
      exit;
    }
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      //GET menampilkan form edit pada View yang sama dengan tabel.
      $this->renderMadmin('ubah', [
        'username' => $admin['username'],
        'status_akun' => $admin['status_akun'],
      ]);
      return;
    }
    //metode selain GET/POST ditolak.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      exit('Metode request tidak diizinkan.');
    }
    //username tetap menggunakan parameter route; tidak diubah dari form.
    $password = $_POST['password'] ?? '';
    $statusAkun = $_POST['status_akun'] ?? '';
    //validasi edit menggunakan username dari route.
    $error = $this->validateInput($username, $password, $statusAkun, true);
    if ($error !== null) {
      $this->renderMadmin('ubah', [
        'username' => $username,
        'status_akun' => $statusAkun,
      ], $error);
      return;
    }
    //password kosong berarti hash lama tetap dipertahankan.
    $passwordHash = null;
    if ($password !== '') {
      $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    }
    //Model menentukan UPDATE sesuai ada/tidaknya password baru.
    if (!$this->model->update($username, $passwordHash, $statusAkun)) {
      $this->renderMadmin('ubah', [
        'username' => $username,
        'status_akun' => $statusAkun,
      ], 'Data admin gagal diperbarui.');
      return;
    }
    //POST berhasil dikembalikan ke halaman Madmin menggunakan PRG.
    set_flash_message('success', 'Data admin berhasil diperbarui.');
    header('Location: ' . site_url('admin/madmin'));
    exit;
  }
  public function hapus(string $username): void
  {
    require_admin_login();
    //DELETE adalah perubahan data, jadi wajib POST.
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      exit('Metode request tidak diizinkan.');
    }
    //pastikan record memang ada sebelum DELETE.
    if ($this->model->findByUsername($username) === null) {
      set_flash_message('danger', 'Data admin tidak ditemukan.');
      header('Location: ' . site_url('admin/madmin'));
      exit;
    }
    //Controller meminta Model menjalankan DELETE.
    if (!$this->model->delete($username)) {
      set_flash_message('danger', 'Data admin gagal dihapus.');
      header('Location: ' . site_url('admin/madmin'));
      exit;
    }
    //kembali ke GET /admin/madmin setelah proses berhasil.
    set_flash_message('success', 'Data admin berhasil dihapus.');
    header('Location: ' . site_url('admin/madmin'));
    exit;
  }
  //merapikan pengiriman data ke View agar tabel/form tetap satu file.
  private function renderMadmin(
    string $mode,
    ?array $adminDipilih = null,
    ?string $error = null
  ): void {
    // Ambil ulang daftar admin agar tabel tetap tampil ketika validasi/form gagal.
    $admins = $this->model->getAll();
    $this->view('admin/madmin_view', [
      'username' => $_SESSION['admin_username'],
      'admins' => $admins,
      'flash' => null,
      'mode' => $mode,
      'admin_dipilih' => $adminDipilih,
      'error' => $error,
    ]);
  }
  //validasi sisi peladen terpusat untuk CREATE dan UPDATE.
  private function validateInput(
    string $username,
    string $password,
    string $statusAkun,
    bool $isEdit
  ): ?string {
    // Username wajib, maksimal 15 karakter, dan tidak boleh mengandung karakter kontrol.
    if (
      $username === '' ||
      strlen($username) > 15 ||
      preg_match('/[\x00-\x1F\x7F]/', $username)
    ) {
      return 'Username tidak valid.';
    }
    // Saat tambah, password wajib diisi. Saat edit, password boleh kosong.
    if (!$isEdit && $password === '') {
      return 'Password wajib diisi.';
    }
    // Status harus sesuai nilai ENUM pada t_admin.
    if (!in_array($statusAkun, ['aktif', 'nonaktif'], true)) {
      return 'Status akun tidak valid.';
    }
    return null;
  }

}