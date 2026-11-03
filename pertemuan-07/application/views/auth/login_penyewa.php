<?php
$e = static function ($value): string {
  return htmlspecialchars(
    (string) $value,
    ENT_QUOTES,
    'UTF-8'
  );
};
require __DIR__ . '/../katalog/header.php';
require __DIR__ . '/../katalog/navbar.php';
?>
<!-- Start Content Page -->
<section class="bg-success py-5">
  <div class="container">
    <div class="row">
      <div class="col-12 text-center">
        <h1 class="h2 text-white mt-3">Login Penyewa</h1>
        <p class="text-white">
          Masuk untuk mengakses layanan rental kendaraan.
        </p>
      </div>
    </div>
  </div>
</section>
<!-- End Content Page -->
<!-- Start Login Form -->
<section class="container py-5">
  <div class="row py-3">
    <div class="col-lg-6 m-auto">
      <div class="card shadow-sm">
        <div class="card-body p-4 p-lg-5">
          <h2 class="h4 text-success mb-3">
            Formulir Login Penyewa
          </h2>
          <p class="text-muted mb-4">
            Masukkan username dan password yang digunakan
            saat registrasi.
          </p>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
              <?= $e($error) ?>
            </div>
          <?php endif; ?>
          <form action="<?= $e(site_url('auth/login-penyewa')) ?>" method="post">
            <div class="mb-3">
              <label for="user_plg" class="form-label">
                Username <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control" id="user_plg" name="user_plg" maxlength="15"
                pattern="[A-Za-z0-9._-]{1,15}" autocomplete="username" required autofocus>
            </div>
            <div class="mb-4">
              <label for="sandi" class="form-label">
                Password <span class="text-danger">*</span>
              </label>
              <input type="password" class="form-control" id="sandi" name="sandi" autocomplete="current-password"
                required>
            </div>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-success btn-lg">
                Login
              </button>
            </div>
          </form>
          <hr class="my-4">
          <p class="text-center mb-2">
            Belum memiliki akun?
            <a href="<?= $e(site_url('auth/register')) ?>">
              Registrasi di sini
            </a>
          </p>
          <p class="text-center mb-0">
            <a href="<?= $e(site_url('katalog')) ?>">
              Kembali ke katalog kendaraan
            </a>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- End Login Form -->
<?php require __DIR__ . '/../katalog/footer.php'; ?>