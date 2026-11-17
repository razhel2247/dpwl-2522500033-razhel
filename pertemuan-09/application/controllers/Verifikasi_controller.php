<?php
// P9 BARU - application/controllers/Verifikasi_controller.php
// Verifikasi pembayaran DP oleh admin. Pola mengikuti Madmin_controller (helper auth + flash + PRG).
require_once APPPATH . 'models/Pembayaran_model.php';
require_once APPPATH . 'helpers/auth_helper.php';
require_once APPPATH . 'helpers/flash_helper.php';

class Verifikasi_controller extends Controller
{
  private Pembayaran_model $model;

  public function __construct()
  {
    $this->model = new Pembayaran_model();
  }

  // GET /admin/pembayaran - daftar menunggu verifikasi + riwayat.
  public function index(): void
  {
    $this->wajibAdmin();
    $this->wajibMetode('GET');

    try {
      $pending = $this->model->getDaftarPending();
      $riwayat = $this->model->getRiwayat();
    } catch (Throwable $e) {
      error_log('Daftar verifikasi pembayaran gagal: ' . $e->getMessage());
      http_response_code(500);
      exit('Daftar verifikasi belum dapat ditampilkan. Silakan coba kembali.');
    }

    $this->view('admin/verifikasi_view', [
      'username' => $_SESSION['admin_username'],
      'pending' => $pending,
      'riwayat' => $riwayat,
      'flash' => get_flash_message(),
    ]);
  }

  // GET /admin/pembayaran/detail?kode_sewa=INV/... - detail + bukti + tombol keputusan.
  public function detail(): void
  {
    $this->wajibAdmin();
    $this->wajibMetode('GET');

    $kodeSewa = trim((string) ($_GET['kode_sewa'] ?? ''));
    if ($kodeSewa === '') {
      $this->kembaliDenganPesan('danger', 'Kode transaksi wajib diisi.');
    }

    try {
      $pembayaran = $this->model->getDetailAdmin($kodeSewa);
    } catch (Throwable $e) {
      error_log('Detail verifikasi pembayaran gagal: ' . $e->getMessage());
      http_response_code(500);
      exit('Detail pembayaran belum dapat ditampilkan. Silakan coba kembali.');
    }

    if ($pembayaran === null) {
      $this->kembaliDenganPesan('danger', 'Data pembayaran tidak ditemukan.');
    }

    $this->view('admin/verifikasi_detail', [
      'username' => $_SESSION['admin_username'],
      'pembayaran' => $pembayaran,
      'flash' => get_flash_message(),
    ]);
  }

  // POST /admin/pembayaran/verifikasi - terima / tolak, lalu PRG.
  public function verifikasi(): void
  {
    $this->wajibAdmin();
    $this->wajibMetode('POST');

    $kodeSewa = trim((string) ($_POST['kode_sewa'] ?? ''));
    $keputusan = (string) ($_POST['keputusan'] ?? '');

    if ($kodeSewa === '' || !in_array($keputusan, ['terima', 'tolak'], true)) {
      $this->kembaliDenganPesan('danger', 'Data verifikasi tidak lengkap atau tidak valid.');
    }

    try {
      $this->model->verifikasi($kodeSewa, $keputusan);
    } catch (PembayaranException $e) {
      // Aturan bisnis (tanpa bukti, sudah diproses, dst.): kembali ke detail.
      set_flash_message('danger', $e->getMessage());
      header('Location: ' . site_url(
        'admin/pembayaran/detail?kode_sewa=' . rawurlencode($kodeSewa)
      ));
      exit;
    } catch (Throwable $e) {
      error_log('Verifikasi pembayaran gagal: ' . $e->getMessage());
      $this->kembaliDenganPesan(
        'danger',
        'Verifikasi belum berhasil diproses. Tidak ada perubahan data yang disimpan.'
      );
    }

    $hasil = $keputusan === 'terima'
      ? 'diterima. Status pembayaran: valid, status transaksi: disetujui.'
      : 'ditolak. Status pembayaran: invalid, status transaksi: ditolak.';

    set_flash_message('success', 'Pembayaran untuk transaksi ' . $kodeSewa . ' ' . $hasil);
    header('Location: ' . site_url('admin/pembayaran'));
    exit;
  }

  // ---------------------------------------------------------------
  // Helper private
  // ---------------------------------------------------------------

  // Hanya admin aktif. Penyewa yang login diberi 403; pengunjung anonim diarahkan ke login admin.
  private function wajibAdmin(): void
  {
    if (is_admin_logged_in()) {
      return;
    }
    if (!empty($_SESSION['penyewa_user_plg'])) {
      http_response_code(403);
      exit('Akses ditolak. Halaman ini khusus admin.');
    }
    require_admin_login();
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

  private function kembaliDenganPesan(string $jenis, string $pesan): never
  {
    set_flash_message($jenis, $pesan);
    header('Location: ' . site_url('admin/pembayaran'));
    exit;
  }
}