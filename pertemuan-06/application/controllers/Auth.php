<?php

class Auth extends Controller
{
  public function login(): void
  {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
      $this->view('auth/login');
      return;
    }

    if ($method !== 'POST') {
      http_response_code(405);
      header('Allow: GET, POST');
      exit('Method Not Allowed');
    }

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    /*
     * Validasi sisi peladen.
     * username mengikuti struktur t_admin.username varchar(15).
     */
    if (
      $username === '' ||
      strlen($username) > 15 ||
      preg_match('/[\x00-\x1F\x7F]/', $username)
    ) {
      $this->view('auth/login', [
        'error' => 'Username tidak valid.'
      ]);
      return;
    }

    if ($password === '') {
      $this->view('auth/login', [
        'error' => 'Password wajib diisi.'
      ]);
      return;
    }

    require_once APPPATH . 'models/Admin_model.php';

    $model = new Admin_model();
    $admin = $model->findByUsername($username);

    if (
      !$admin ||
      $admin['status_akun'] !== 'aktif' ||
      !password_verify($password, $admin['password'])
    ) {
      $this->view('auth/login', [
        'error' => 'Username atau password tidak sesuai.'
      ]);
      return;
    }

    session_regenerate_id(true);

    $_SESSION['admin_username'] = $admin['username'];
    $_SESSION['admin_status'] = $admin['status_akun'];

    header('Location: ' . site_url('admin'));
    exit;
  }

  public function logout(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      header('Allow: POST');
      exit('Method Not Allowed');
    }

    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
      $params = session_get_cookie_params();

      setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
      );
    }

    session_destroy();

    header('Location: ' . site_url('auth/login'));
    exit;
  }
  // P6: register penyewa
  public function register(): void
  {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $flash = $_SESSION['flash_penyewa'] ?? null;
      unset($_SESSION['flash_penyewa']);
      $this->view('auth/register', [
        'success' => $flash
      ]);
      return;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      header('Allow: GET, POST');
      exit('Method Not Allowed');
    }
    $userPlg = trim($_POST['user_plg'] ?? '');
    $password = $_POST['sandi'] ?? '';
    $confirmPassword = $_POST['konfirmasi_sandi'] ?? '';
    $nik = trim($_POST['nik'] ?? '');
    $nama = trim($_POST['nm_lngkp'] ?? '');
    $noHp = trim($_POST['no_hp'] ?? '');
    $alamat = trim($_POST['almt_lngkp'] ?? '');
    $old = [
      'user_plg' => $userPlg,
      'nik' => $nik,
      'nm_lngkp' => $nama,
      'no_hp' => $noHp,
      'almt_lngkp' => $alamat
    ];
    $error = null;
    if (
      !preg_match('/\A[A-Za-z0-9._-]{1,15}\z/', $userPlg)
    ) {
      $error = 'Username harus 1–15 karakter: huruf, angka, titik, garis bawah, atau tanda hubung.';
    } elseif (strlen($password) < 8) {
      $error = 'Password minimal 8 karakter.';
    } elseif ($password !== $confirmPassword) {
      $error = 'Konfirmasi password tidak sama.';
    } elseif (!preg_match('/\A[0-9]{16}\z/', $nik)) {
      $error = 'NIK harus terdiri dari 16 digit angka.';
    } elseif ($nama === '' || strlen($nama) > 100) {
      $error = 'Nama lengkap wajib diisi, maksimal 100 karakter.';
    } elseif (
      $noHp === '' ||
      strlen($noHp) > 15 ||
      !preg_match('/\A[0-9+(). -]+\z/', $noHp)
    ) {
      $error = 'Nomor HP wajib diisi dan formatnya tidak valid.';
    } elseif ($alamat === '' || strlen($alamat) > 300) {
      $error = 'Alamat wajib diisi, maksimal 300 karakter.';
    }
    if ($error !== null) {
      $this->view('auth/register', [
        'error' => $error,
        'old' => $old
      ]);
      return;
    }
    require_once APPPATH . 'models/Penyewa_model.php';
    try {
      $model = new Penyewa_model();
      if ($model->findByUserPlg($userPlg) !== null) {
        $this->view('auth/register', [
          'error' => 'Username sudah digunakan.',
          'old' => $old
        ]);
        return;
      }
      $hash = password_hash($password, PASSWORD_DEFAULT);
      if ($hash === false) {
        throw new RuntimeException('Pembuatan hash password gagal.');
      }
      $created = $model->create([
        'user_plg' => $userPlg,
        'sandi' => $hash,
        'nik' => $nik,
        'nm_lngkp' => $nama,
        'no_hp' => $noHp,
        'almt_lngkp' => $alamat
      ]);
      if (!$created) {
        throw new RuntimeException('Penyimpanan data penyewa gagal.');
      }
      $_SESSION['flash_penyewa'] =
        'Registrasi berhasil. Data akun Anda telah tersimpan.';
      header('Location: ' . site_url('auth/register'));
      exit;
    } catch (Throwable $e) {
      error_log('Registrasi penyewa gagal: ' . $e->getMessage());
      $this->view('auth/register', [
        'error' => 'Registrasi belum berhasil diproses. Periksa data dan coba kembali.',
        'old' => $old
      ]);
    }
  }
  // P6: login penyewa
  public function loginPenyewa(): void
  {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
      $this->view('auth/login_penyewa');
      return;
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      header('Allow: GET, POST');
      exit('Method Not Allowed');
    }
    $userPlg = trim($_POST['user_plg'] ?? '');
    $password = $_POST['sandi'] ?? '';
    if (
      !preg_match('/\A[A-Za-z0-9._-]{1,15}\z/', $userPlg)
    ) {
      $this->view('auth/login_penyewa', [
        'error' => 'Username atau password tidak sesuai.'
      ]);
      return;
    }
    if ($password === '') {
      $this->view('auth/login_penyewa', [
        'error' => 'Username atau password tidak sesuai.'
      ]);
      return;
    }
    require_once APPPATH . 'models/Penyewa_model.php';
    try {
      $model = new Penyewa_model();
      $penyewa = $model->findForLogin($userPlg);
      if (
        !$penyewa ||
        !password_verify($password, $penyewa['sandi'])
      ) {
        $this->view('auth/login_penyewa', [
          'error' => 'Username atau password tidak sesuai.'
        ]);
        return;
      }
      session_regenerate_id(true);
      $_SESSION['penyewa_user_plg'] = $penyewa['user_plg'];
      $_SESSION['flash_penyewa_nav'] =
        'Login berhasil. Selamat datang, ' . $penyewa['nm_lngkp'] . '!';
      header('Location: ' . site_url('katalog'));
      exit;
    } catch (Throwable $e) {
      error_log('Login penyewa gagal: ' . $e->getMessage());
      $this->view('auth/login_penyewa', [
        'error' => 'Login belum dapat diproses. Silakan coba kembali.'
      ]);
    }
  }
  // P6: logout penyewa
  public function logoutPenyewa(): void
  {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
      http_response_code(405);
      header('Allow: POST');
      exit('Method Not Allowed');
    }
    unset($_SESSION['penyewa_user_plg']);
    session_regenerate_id(true);
    $_SESSION['flash_penyewa_nav'] =
      'Anda telah logout dari akun penyewa.';
    header('Location: ' . site_url('katalog'));
    exit;
  }
}