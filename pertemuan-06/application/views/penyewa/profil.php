<?php
$e = static function ($value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$profil = $profil ?? [];
$old = $old ?? [];
$nik = $old['nik'] ?? $profil['nik'] ?? '';
$nama = $old['nm_lngkp'] ?? $profil['nm_lngkp'] ?? '';
$noHp = $old['no_hp'] ?? $profil['no_hp'] ?? '';
$alamat = $old['almt_lngkp'] ?? $profil['almt_lngkp'] ?? '';
$fotoKtp = $profil['ft_ktp'] ?? null;
$fotoSim = $profil['ft_sim_a'] ?? null;
$folderFoto = 'uploads/penyewa/';
require __DIR__ . '/../katalog/header.php';
require __DIR__ . '/../katalog/navbar.php';
?>
<!-- Judul Halaman -->
<section class="bg-success py-5">
<div class="container">
<div class="row">
<div class="col-12 text-center">
<h1 class="h2 text-white mt-3">Profil Penyewa</h1>
<p class="text-white">
Periksa dan perbarui informasi akun Anda.
</p>
</div>
</div>
</div>
</section>
<!-- Form Profil -->
<section class="container py-5">
<div class="row">
<div class="col-lg-8 m-auto">
<div class="card shadow-sm">
<div class="card-body p-4 p-lg-5">
<h2 class="h4 text-success mb-3">Informasi Akun</h2>
<p class="text-muted mb-4">
Username tidak dapat diubah. NIK dapat diperbarui.
Foto KTP dan SIM A dapat ditambahkan atau diganti.
</p>
<?php if (!empty($flash)): ?>
<div class="alert alert-success" role="alert">
<?= $e($flash) ?>
</div>
<?php endif; ?>
<?php if (!empty($error)): ?>
<div class="alert alert-danger" role="alert">
<?= $e($error) ?>
</div>
<?php endif; ?>
<form
action="<?= $e(site_url('penyewa/profil')) ?>"
method="post"
enctype="multipart/form-data">
<!-- Identitas akun -->
<div class="mb-3">
<label for="user_plg" class="form-label">Username</label>
<input
type="text"
class="form-control"
id="user_plg"
value="<?= $e($profil['user_plg'] ?? '') ?>"
readonly>
</div>
<div class="mb-4">
<label for="nik" class="form-label">
NIK <span class="text-danger">*</span>
</label>
<input
type="text"
class="form-control"
id="nik"
name="nik"
maxlength="16"
minlength="16"
pattern="[0-9]{16}"
inputmode="numeric"
value="<?= $e($nik) ?>"
required>
<small class="form-text text-muted">
Masukkan tepat 16 digit angka.
</small>
</div>
<hr class="my-4">
<!-- Data profil -->
<div class="mb-3">
<label for="nm_lngkp" class="form-label">
Nama Lengkap <span class="text-danger">*</span>
</label>
<input
type="text"
class="form-control"
id="nm_lngkp"
name="nm_lngkp"
maxlength="100"
pattern="[\p{L}\p{M} .'-]+"
value="<?= $e($nama) ?>"
required>
<small class="form-text text-muted">
Gunakan nama tanpa angka.
</small>
</div>
<div class="mb-3">
<label for="no_hp" class="form-label">
Nomor HP <span class="text-danger">*</span>
</label>
<input
type="tel"
class="form-control"
id="no_hp"
name="no_hp"
maxlength="15"
pattern="[0-9+(). -]+"
inputmode="tel"
value="<?= $e($noHp) ?>"
required>
<small class="form-text text-muted">
Gunakan angka dan tanda +, titik, kurung, spasi,
atau tanda hubung.
</small>
</div>
<div class="mb-4">
<label for="almt_lngkp" class="form-label">
Alamat Lengkap <span class="text-danger">*</span>
</label>
<textarea
class="form-control"
id="almt_lngkp"
name="almt_lngkp"
rows="4"
maxlength="300"
required><?= $e($alamat) ?></textarea>
</div>
<hr class="my-4">
<!-- Ganti Password -->
<h3 class="h5 text-success mb-3">Ganti Password</h3>
<p class="text-muted">
Kosongkan ketiga kolom jika tidak ingin mengganti password.
</p>
<div class="mb-3">
<label for="password_saat_ini" class="form-label">
Password Saat Ini
</label>
<input
type="password"
class="form-control"
id="password_saat_ini"
name="password_saat_ini"
autocomplete="current-password">
</div>
<div class="mb-3">
<label for="password_baru" class="form-label">
Password Baru
</label>
<input
type="password"
class="form-control"
id="password_baru"
name="password_baru"
minlength="8"
maxlength="72"
autocomplete="new-password">
<small class="form-text text-muted">
Minimal 8 karakter.
</small>
</div>
<div class="mb-4">
<label for="konfirmasi_password" class="form-label">
Konfirmasi Password Baru
</label>
<input
type="password"
class="form-control"
id="konfirmasi_password"
name="konfirmasi_password"
minlength="8"
maxlength="72"
autocomplete="new-password">
</div>
<hr class="my-4">
<!-- Foto KTP -->
<div class="mb-4">
<label for="ft_ktp" class="form-label">
Foto KTP
</label>
<?php if (!empty($fotoKtp)): ?>
<div class="mb-3">
<p class="text-muted mb-2">Foto KTP saat ini:</p>
<a
href="<?= $e(base_url($folderFoto . rawurlencode($fotoKtp))) ?>"
target="_blank"
rel="noopener noreferrer">
<img
src="<?= $e(base_url($folderFoto . rawurlencode($fotoKtp))) ?>"
alt="Foto KTP penyewa"
class="img-thumbnail"
style="max-width: 320px; max-height: 220px;">
</a>
</div>
<?php else: ?>
<p class="text-muted">
Belum ada foto KTP yang diunggah.
</p>
<?php endif; ?>
<input
type="file"
class="form-control"
id="ft_ktp"
name="ft_ktp"
accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
<small class="form-text text-muted">
Format JPEG, PNG, atau WEBP; maksimal 5 MB.
Kosongkan jika tidak ingin mengganti foto.
</small>
</div>
<!-- Foto SIM A -->
<div class="mb-4">
<label for="ft_sim_a" class="form-label">
Foto SIM A
</label>
<?php if (!empty($fotoSim)): ?>
<div class="mb-3">
<p class="text-muted mb-2">Foto SIM A saat ini:</p>
<a
href="<?= $e(base_url($folderFoto . rawurlencode($fotoSim))) ?>"
target="_blank"
rel="noopener noreferrer">
<img
src="<?= $e(base_url($folderFoto . rawurlencode($fotoSim))) ?>"
alt="Foto SIM A penyewa"
class="img-thumbnail"
style="max-width: 320px; max-height: 220px;">
</a>
</div>
<?php else: ?>
<p class="text-muted">
Belum ada foto SIM A yang diunggah.
</p>
<?php endif; ?>
<input
type="file"
class="form-control"
id="ft_sim_a"
name="ft_sim_a"
accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
<small class="form-text text-muted">
Format JPEG, PNG, atau WEBP; maksimal 5 MB.
Kosongkan jika tidak ingin mengganti foto.
</small>
</div>
<!-- Tombol -->
<div class="d-grid gap-2">
<button type="submit" class="btn btn-success btn-lg">
Simpan Perubahan
</button>
<a
href="<?= $e(site_url('katalog')) ?>"
class="btn btn-outline-secondary">
Kembali ke Katalog
</a>
</div>
</form>
</div>
</div>
</div>
</div>
</section>
<?php require __DIR__ . '/../katalog/footer.php'; ?>


