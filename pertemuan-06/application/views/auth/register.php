<?php
$old = $old ?? [];
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
        <h1 class="h2 text-white mt-3">Registrasi Penyewa</h1>
        <p class="text-white">
          Buat akun untuk menggunakan layanan rental kendaraan.
        </p>
      </div>
    </div>
  </div>
</section>
<!-- End Content Page -->
<!-- Start Registration Form -->
<section class="container py-5">
  <div class="row py-3">
    <div class="col-lg-8 m-auto">
      <div class="card shadow-sm">
        <div class="card-body p-4 p-lg-5">
          <h2 class="h4 text-success mb-3">
            Formulir Registrasi
          </h2>
          <p class="text-muted mb-4">
            Lengkapi seluruh data wajib. Foto KTP dan SIM A
            dapat ditambahkan melalui profil setelah registrasi.
          </p>
          <?php if (!empty($success)): ?>
            <div class="alert alert-success" role="alert">
              <?= $e($success) ?>
              <div class="mt-3">
                <a href="<?= $e(site_url('auth/login-penyewa')) ?>" class="btn btn-success">
                  Login Penyewa
                </a>
              </div>
            </div>
          <?php endif; ?>
          <?php if (!empty($error)): ?>
            <div class="alert alert-danger" role="alert">
              <?= $e($error) ?>
            </div>
          <?php endif; ?>
          <form action="<?= $e(site_url('auth/register')) ?>" method="post">
            <div class="mb-3">
              <label for="user_plg" class="form-label">
                Username <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control" id="user_plg" name="user_plg" maxlength="15"
                pattern="[A-Za-z0-9._-]{1,15}" value="<?= $e($old['user_plg'] ?? '') ?>" autocomplete="username"
                required>
              <div class="form-text">
                Maksimal 15 karakter: huruf, angka, titik,
                garis bawah, atau tanda hubung.
              </div>
            </div>
            <div class="row">
              <div class="col-md-6 mb-3">
                <label for="sandi" class="form-label">
                  Password <span class="text-danger">*</span>
                </label>
                <input type="password" class="form-control" id="sandi" name="sandi" minlength="8"
                  autocomplete="new-password" required>
                <div class="form-text">
                  Minimal 8 karakter.
                </div>
              </div>
              <div class="col-md-6 mb-3">
                <label for="konfirmasi_sandi" class="form-label">
                  Konfirmasi Password
                  <span class="text-danger">*</span>
                </label>
                <input type="password" class="form-control" id="konfirmasi_sandi" name="konfirmasi_sandi" minlength="8"
                  autocomplete="new-password" required>
              </div>
            </div>
            <div class="mb-3">
              <label for="nik" class="form-label">
                NIK <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control" id="nik" name="nik" maxlength="16" minlength="16"
                pattern="[0-9]{16}" inputmode="numeric" value="<?= $e($old['nik'] ?? '') ?>" required>
              <div class="form-text">
                Masukkan 16 digit NIK.
              </div>
            </div>
            <div class="mb-3">
              <label for="nm_lngkp" class="form-label">
                Nama Lengkap <span class="text-danger">*</span>
              </label>
              <input type="text" class="form-control" id="nm_lngkp" name="nm_lngkp" maxlength="100"
                value="<?= $e($old['nm_lngkp'] ?? '') ?>" autocomplete="name" required>
            </div>
            <div class="mb-3">
              <label for="no_hp" class="form-label">
                Nomor HP <span class="text-danger">*</span>
              </label>
              <input type="tel" class="form-control" id="no_hp" name="no_hp" maxlength="15"
                value="<?= $e($old['no_hp'] ?? '') ?>" autocomplete="tel" required>
            </div>
            <div class="mb-4">
              <label for="almt_lngkp" class="form-label">
                Alamat Lengkap <span class="text-danger">*</span>
              </label>
              <textarea class="form-control" id="almt_lngkp" name="almt_lngkp" rows="4" maxlength="300"
                autocomplete="street-address" required><?= $e($old['almt_lngkp'] ?? '') ?></textarea>
            </div>
            <p class="small text-muted">
              Tanda <span class="text-danger">*</span>
              menunjukkan field wajib diisi.
            </p>
            <div class="d-grid gap-2">
              <button type="submit" class="btn btn-success btn-lg">
                Daftar
              </button>
            </div>
          </form>
          <hr class="my-4">
          <p class="text-center mb-0">
            Sudah memiliki akun?
            <a href="<?= $e(site_url('auth/login-penyewa')) ?>">
              Login di sini
            </a>
          </p>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- End Registration Form -->
<?php require __DIR__ . '/../katalog/footer.php'; ?>