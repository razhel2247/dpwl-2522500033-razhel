<?php
// UTS DPWL: View Manajemen Penyewa. Tabel dan form berada pada satu file (pola sama dengan mobil_view).
require APPPATH . '/views/templates/header.php';
require APPPATH . '/views/templates/sidebar.php';

$e = static fn($v): string => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');

$penyewa = $penyewa ?? [];
$flash = $flash ?? null;
$error = $error ?? null;
$mode = $mode ?? null;
$penyewa_dipilih = $penyewa_dipilih ?? null;
$csrf = $csrf ?? '';

$isForm = in_array($mode, ['tambah', 'ubah'], true);
$isEdit = $mode === 'ubah';
$userDipilih = (string) ($penyewa_dipilih['user_plg'] ?? '');
$formAction = $isEdit
  ? site_url('admin/penyewa/ubah/' . rawurlencode($userDipilih))
  : site_url('admin/penyewa/tambah');

// Ringkasan untuk kartu statistik (dihitung dari data yang sudah ada, tanpa query tambahan).
$total = count($penyewa);
$lengkap = 0;
foreach ($penyewa as $p) {
  if (!empty($p['ft_ktp']) && !empty($p['ft_sim_a'])) {
    $lengkap++;
  }
}
$belum = $total - $lengkap;

$fotoUrl = static fn(string $nama): string => base_url('uploads/penyewa/' . rawurlencode($nama));
$inisial = static function (string $nama): string {
  $kata = preg_split('/\s+/u', trim($nama)) ?: [];
  $hasil = mb_substr($kata[0] ?? '?', 0, 1);
  if (count($kata) > 1) {
    $hasil .= mb_substr(end($kata), 0, 1);
  }
  return mb_strtoupper($hasil);
};
$warna = ['#0f766e', '#b45309', '#4338ca', '#be123c', '#0369a1', '#6d28d9', '#15803d'];
?>
<style>
  .pw-stats { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
  .pw-stat { flex: 1 1 190px; background: #fff; border-radius: 6px; padding: 14px 18px; border-left: 5px solid var(--c); box-shadow: 0 1px 3px rgba(0, 0, 0, .1); }
  .pw-stat strong { display: block; font-size: 28px; line-height: 1.1; color: #111827; }
  .pw-stat span { color: #6b7280; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .08em; }
  .pw-box { border-top: 3px solid #0f766e; }
  .pw-person { display: flex; align-items: center; gap: 10px; }
  .pw-person small { display: block; color: #6b7280; }
  .pw-avatar { flex: none; width: 38px; height: 38px; border-radius: 50%; color: #fff; font-weight: 700; font-size: 13px; display: inline-flex; align-items: center; justify-content: center; }
  .pw-nik { font-family: Consolas, Menlo, monospace; letter-spacing: .04em; }
  .pw-chip { display: inline-block; padding: 2px 9px; margin: 1px 2px 1px 0; border-radius: 12px; font-size: 11px; font-weight: 600; border: 1px solid transparent; line-height: 1.6; }
  .pw-chip-ok { background: #ccfbf1; color: #0f766e; border-color: #99f6e4; cursor: pointer; }
  .pw-chip-ok:hover { background: #99f6e4; }
  .pw-chip-no { background: #f3f4f6; color: #9ca3af; border-color: #e5e7eb; text-decoration: line-through; }
  .pw-form-title { margin: 22px 0 12px; padding-bottom: 6px; border-bottom: 2px solid #e5e7eb; font-size: 13px; font-weight: 700; text-transform: uppercase; letter-spacing: .08em; color: #0f766e; }
  .pw-form-title:first-child { margin-top: 0; }
  .pw-doc { border: 1px dashed #cbd5e1; border-radius: 6px; padding: 14px; margin-bottom: 15px; background: #f8fafc; }
  .pw-doc h5 { margin: 0 0 10px; font-weight: 700; }
  .pw-doc img { width: 100%; max-width: 240px; height: 150px; object-fit: cover; border-radius: 4px; border: 1px solid #e2e8f0; background: #fff; }
  .pw-doc .help-block { margin-bottom: 4px; }
  .pw-doc .pw-hapus { display: block; margin-top: 8px; font-weight: 400; color: #b91c1c; }
</style>

<div class="content-wrapper">
  <section class="content-header">
    <h1>
      Manajemen Penyewa
      <small>Data akun penyewa dari pendaftaran di halaman rental</small>
    </h1>
    <ol class="breadcrumb">
      <li><a href="<?= $e(site_url('admin')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li>
      <li class="active">Manajemen Penyewa</li>
    </ol>
  </section>

  <section class="content">
    <?php if (!empty($flash)): ?>
      <div class="alert <?= (($flash['type'] ?? '') === 'success') ? 'alert-success' : 'alert-danger' ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fa <?= (($flash['type'] ?? '') === 'success') ? 'fa-check' : 'fa-warning' ?>"></i>
        <?= $e($flash['message'] ?? '') ?>
      </div>
    <?php endif; ?>

    <?php if (!$isForm && $error !== null): ?>
      <div class="alert alert-danger alert-dismissible">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <i class="fa fa-warning"></i> <?= $e($error) ?>
      </div>
    <?php endif; ?>

    <div class="pw-stats">
      <div class="pw-stat" style="--c:#0f766e"><strong><?= $total ?></strong><span>Total penyewa</span></div>
      <div class="pw-stat" style="--c:#15803d"><strong><?= $lengkap ?></strong><span>Dokumen lengkap</span></div>
      <div class="pw-stat" style="--c:#b45309"><strong><?= $belum ?></strong><span>Dokumen belum lengkap</span></div>
    </div>

    <!-- ===== Tabel data penyewa ===== -->
    <div class="box pw-box">
      <div class="box-header">
        <h3 class="box-title">Daftar Penyewa</h3>
        <div class="box-tools">
          <a href="<?= $e(site_url('admin/penyewa/tambah')) ?>" class="btn btn-primary btn-sm">
            <i class="fa fa-user-plus"></i> Tambah Penyewa
          </a>
        </div>
      </div>
      <div class="box-body">
        <div class="table-responsive">
          <table id="penyewaTable" class="table table-bordered table-striped js-data-table">
            <thead>
              <tr>
                <th>No</th>
                <th>Penyewa</th>
                <th>NIK</th>
                <th>No. HP</th>
                <th>Alamat</th>
                <th class="no-sort">Dokumen</th>
                <th class="no-sort">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($penyewa as $no => $item): ?>
                <?php
                $user = (string) $item['user_plg'];
                $nama = (string) $item['nm_lngkp'];
                $alamat = (string) $item['almt_lngkp'];
                ?>
                <tr>
                  <td><?= (int) $no + 1 ?></td>
                  <td>
                    <div class="pw-person">
                      <span class="pw-avatar" style="background:<?= $warna[crc32($user) % count($warna)] ?>"><?= $e($inisial($nama)) ?></span>
                      <span>
                        <strong><?= $e($nama) ?></strong>
                        <small>@<?= $e($user) ?></small>
                      </span>
                    </div>
                  </td>
                  <td class="pw-nik"><?= $e($item['nik']) ?></td>
                  <td><?= $e($item['no_hp']) ?></td>
                  <td title="<?= $e($alamat) ?>"><?= $e(mb_strimwidth($alamat, 0, 60, '…', 'UTF-8')) ?></td>
                  <td>
                    <?php foreach (['ft_ktp' => 'KTP', 'ft_sim_a' => 'SIM A'] as $kolom => $label): ?>
                      <?php if (!empty($item[$kolom])): ?>
                        <button type="button" class="pw-chip pw-chip-ok js-lihat-foto"
                          data-src="<?= $e($fotoUrl((string) $item[$kolom])) ?>"
                          data-judul="<?= $e($label . ' — ' . $nama) ?>">
                          <i class="fa fa-check"></i> <?= $label ?>
                        </button>
                      <?php else: ?>
                        <span class="pw-chip pw-chip-no"><?= $label ?></span>
                      <?php endif; ?>
                    <?php endforeach; ?>
                  </td>
                  <td>
                    <a class="btn btn-warning btn-xs" href="<?= $e(site_url('admin/penyewa/ubah/' . rawurlencode($user))) ?>">
                      <i class="fa fa-edit"></i> Ubah
                    </a>
                    <form method="post" style="display:inline" class="js-hapus" data-nama="<?= $e($nama) ?>"
                      action="<?= $e(site_url('admin/penyewa/hapus/' . rawurlencode($user))) ?>">
                      <input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>">
                      <button type="submit" class="btn btn-danger btn-xs">
                        <i class="fa fa-trash"></i> Hapus
                      </button>
                    </form>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ===== Form tambah / ubah ===== -->
    <?php if ($isForm): ?>
      <div class="box box-primary pw-box" id="form-penyewa">
        <div class="box-header with-border">
          <h3 class="box-title"><?= $isEdit ? 'Ubah Data Penyewa: @' . $e($userDipilih) : 'Tambah Penyewa Baru' ?></h3>
        </div>
        <form role="form" method="post" enctype="multipart/form-data" autocomplete="off" action="<?= $e($formAction) ?>">
          <input type="hidden" name="csrf_token" value="<?= $e($csrf) ?>">
          <div class="box-body">
            <?php if ($error !== null): ?>
              <div class="alert alert-danger alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa fa-warning"></i> <?= $e($error) ?>
              </div>
            <?php endif; ?>

            <div class="row">
              <div class="col-md-6">
                <div class="pw-form-title">1. Akun</div>
                <div class="form-group">
                  <label for="user_plg">Username</label>
                  <input type="text" class="form-control" id="user_plg" name="user_plg" maxlength="15"
                    pattern="[A-Za-z0-9._\-]{1,15}" value="<?= $e($penyewa_dipilih['user_plg'] ?? '') ?>"
                    <?= $isEdit ? 'readonly' : 'required' ?>>
                  <p class="help-block"><?= $isEdit ? 'Username adalah kunci data dan tidak dapat diubah.' : 'Maks. 15 karakter: huruf, angka, titik, garis bawah, tanda hubung.' ?></p>
                </div>
                <div class="form-group">
                  <label for="sandi">Password<?= $isEdit ? ' baru' : '' ?></label>
                  <input type="password" class="form-control" id="sandi" name="sandi" maxlength="72"
                    autocomplete="new-password" <?= $isEdit ? '' : 'required minlength="8"' ?>>
                  <p class="help-block"><?= $isEdit ? 'Kosongkan jika tidak ingin mengganti password.' : 'Minimal 8 karakter. Disimpan dalam bentuk hash.' ?></p>
                </div>
                <div class="form-group">
                  <label for="konfirmasi_sandi">Konfirmasi password</label>
                  <input type="password" class="form-control" id="konfirmasi_sandi" name="konfirmasi_sandi" maxlength="72"
                    autocomplete="new-password" <?= $isEdit ? '' : 'required minlength="8"' ?>>
                </div>

                <div class="pw-form-title">2. Identitas</div>
                <div class="form-group">
                  <label for="nik">NIK</label>
                  <input type="text" class="form-control pw-nik" id="nik" name="nik" inputmode="numeric"
                    maxlength="16" minlength="16" pattern="[0-9]{16}" value="<?= $e($penyewa_dipilih['nik'] ?? '') ?>" required>
                  <p class="help-block">Tepat 16 digit angka.</p>
                </div>
                <div class="form-group">
                  <label for="nm_lngkp">Nama lengkap</label>
                  <input type="text" class="form-control" id="nm_lngkp" name="nm_lngkp" maxlength="100"
                    value="<?= $e($penyewa_dipilih['nm_lngkp'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label for="no_hp">Nomor HP</label>
                  <input type="tel" class="form-control" id="no_hp" name="no_hp" maxlength="15"
                    value="<?= $e($penyewa_dipilih['no_hp'] ?? '') ?>" required>
                </div>
                <div class="form-group">
                  <label for="almt_lngkp">Alamat lengkap</label>
                  <textarea class="form-control" id="almt_lngkp" name="almt_lngkp" rows="3" maxlength="300" required><?= $e($penyewa_dipilih['almt_lngkp'] ?? '') ?></textarea>
                </div>
              </div>

              <div class="col-md-6">
                <div class="pw-form-title">3. Dokumen</div>
                <?php foreach (['ft_ktp' => 'Foto KTP', 'ft_sim_a' => 'Foto SIM A'] as $field => $label): ?>
                  <?php $fotoSaatIni = $penyewa_dipilih[$field] ?? null; ?>
                  <div class="pw-doc form-group">
                    <h5><i class="fa fa-id-card-o"></i> <?= $label ?></h5>
                    <input type="file" class="form-control" id="<?= $field ?>" name="<?= $field ?>"
                      accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    <p class="help-block">JPG, PNG, atau WEBP; maksimal 5 MB. Opsional.</p>

                    <?php if (!empty($fotoSaatIni) && is_string($fotoSaatIni)): ?>
                      <p class="help-block">Foto saat ini:</p>
                      <img src="<?= $e($fotoUrl($fotoSaatIni)) ?>" alt="<?= $e($label) ?> saat ini">
                      <p class="help-block">Pilih file baru untuk menggantinya.</p>
                      <?php if ($isEdit): ?>
                        <label class="pw-hapus">
                          <input type="checkbox" name="hapus_<?= $field ?>" value="1"> Hapus <?= $e(strtolower($label)) ?> tanpa menggantinya
                        </label>
                      <?php endif; ?>
                    <?php else: ?>
                      <p class="help-block">Belum ada foto tersimpan.</p>
                    <?php endif; ?>

                    <div class="js-pratinjau" style="display:none; margin-top:8px;">
                      <p class="help-block">Pratinjau file yang dipilih:</p>
                      <img alt="Pratinjau <?= $e($label) ?>">
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <div class="box-footer">
            <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan Penyewa</button>
            <a href="<?= $e(site_url('admin/penyewa')) ?>" class="btn btn-default"><i class="fa fa-times"></i> Batal</a>
          </div>
        </form>
      </div>
    <?php endif; ?>
  </section>
</div>
<?php
require APPPATH . '/views/templates/footer.php';
?>

<!-- Modal pratinjau foto KTP / SIM A -->
<div class="modal fade" id="pwFotoModal" tabindex="-1" role="dialog" aria-labelledby="pwFotoJudul">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Tutup"><span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title" id="pwFotoJudul"></h4>
      </div>
      <div class="modal-body text-center">
        <img id="pwFotoImg" class="img-responsive center-block" alt="">
      </div>
    </div>
  </div>
</div>

<script>
  (function () {
    'use strict';

    // Klik chip KTP / SIM A -> tampilkan foto pada modal.
    document.querySelectorAll('.js-lihat-foto').forEach(function (btn) {
      btn.addEventListener('click', function () {
        document.getElementById('pwFotoJudul').textContent = btn.getAttribute('data-judul');
        var img = document.getElementById('pwFotoImg');
        img.src = btn.getAttribute('data-src');
        img.alt = btn.getAttribute('data-judul');
        $('#pwFotoModal').modal('show');
      });
    });

    // Konfirmasi hapus (delegasi event: tetap bekerja saat DataTables berpindah halaman).
    document.addEventListener('submit', function (ev) {
      var form = ev.target;
      if (form.classList && form.classList.contains('js-hapus')) {
        var nama = form.getAttribute('data-nama') || 'penyewa ini';
        if (!confirm('Hapus penyewa "' + nama + '"? Data beserta foto KTP/SIM terkait akan dihapus.')) {
          ev.preventDefault();
        }
      }
    });

    <?php if ($isForm): ?>
      // Pratinjau foto yang baru dipilih (validasi tipe dan ukuran juga dilakukan di server).
      document.querySelectorAll('.pw-doc input[type="file"]').forEach(function (input) {
        var box = input.closest('.pw-doc').querySelector('.js-pratinjau');
        var img = box.querySelector('img');
        var urlLama = null;
        input.addEventListener('change', function () {
          if (urlLama) { URL.revokeObjectURL(urlLama); urlLama = null; }
          var file = input.files && input.files.length ? input.files[0] : null;
          var valid = file && ['image/jpeg', 'image/png', 'image/webp'].indexOf(file.type) !== -1 &&
            file.size > 0 && file.size <= 5 * 1024 * 1024;
          if (!valid) {
            box.style.display = 'none';
            img.removeAttribute('src');
            if (file) { alert('File harus JPG/PNG/WEBP dan maksimal 5 MB.'); input.value = ''; }
            return;
          }
          urlLama = URL.createObjectURL(file);
          img.src = urlLama;
          box.style.display = 'block';
        });
      });

      // Gulir otomatis ke form agar langsung terlihat.
      var form = document.getElementById('form-penyewa');
      if (form) { form.scrollIntoView({ behavior: 'smooth', block: 'start' }); }
    <?php endif; ?>
  })();
</script>