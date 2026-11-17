<?php
// P9 BARU - application/views/pembayaran/detail.php
// Variabel dari Controller: $pembayaran (array), $flash (?array), $jumlahKeranjang (int)
require APPPATH . 'views/katalog/header.php';
$e = static function ($value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
require APPPATH . 'views/katalog/navbar.php';

$formatRupiah = static function ($nilai): string {
  return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};
$formatWaktu = static function ($nilai): string {
  $ts = $nilai === null ? false : strtotime((string) $nilai);
  return $ts === false ? '-' : date('d-m-Y H:i', $ts);
};

$detail = is_array($pembayaran['detail'] ?? null) ? $pembayaran['detail'] : [];
$kodeSewa = (string) ($pembayaran['kode_sewa'] ?? '');
$statusBayar = (string) ($pembayaran['sttus_byr'] ?? '');
$statusSewa = (string) ($pembayaran['status_sewa'] ?? '');
$fotoBukti = (string) ($pembayaran['ft_byr'] ?? '');
$sudahUnggah = $fotoBukti !== '';

[$kelasBadge, $labelBadge] = match ($statusBayar) {
  'valid' => ['bg-success', 'Valid'],
  'invalid' => ['bg-danger', 'Invalid'],
  default => ['bg-warning text-dark', 'Pending'],
};

// Formulir hanya tampil selama pembayaran belum diverifikasi.
$bolehUnggah = $statusBayar === 'pending' && $statusSewa === 'diajukan';

$flashType = (string) ($flash['type'] ?? '');
$flashMessage = (string) ($flash['message'] ?? '');
?>
<section class="bg-success py-4">
  <div class="container">
    <h1 class="h2 text-white mt-3">Pembayaran DP</h1>
    <p class="text-white mb-0">Transaksi <?= $e($kodeSewa) ?></p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <?php if ($flashMessage !== ''): ?>
      <div class="alert alert-<?= $flashType === 'success' ? 'success' : 'danger' ?> alert-dismissible fade show"
        role="alert">
        <?= $e($flashMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
      </div>
    <?php endif; ?>

    <!-- Ringkasan transaksi -->
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-light">
        <h2 class="h5 mb-0">Rincian Transaksi</h2>
      </div>
      <div class="card-body">
        <dl class="row mb-0">
          <dt class="col-sm-4">Kode transaksi</dt>
          <dd class="col-sm-8"><?= $e($kodeSewa) ?></dd>
          <dt class="col-sm-4">Status transaksi</dt>
          <dd class="col-sm-8"><?= $e($statusSewa) ?></dd>
          <dt class="col-sm-4">Tanggal mulai</dt>
          <dd class="col-sm-8"><?= $e($pembayaran['tgl_mulai'] ?? '-') ?></dd>
          <dt class="col-sm-4">Tanggal selesai</dt>
          <dd class="col-sm-8"><?= $e($pembayaran['tgl_selesai'] ?? '-') ?></dd>
          <dt class="col-sm-4">Waktu pengambilan</dt>
          <dd class="col-sm-8"><?= $e(substr((string) ($pembayaran['wkt_ambil'] ?? '-'), 0, 5)) ?> WIB</dd>
        </dl>
      </div>
    </div>

    <div class="table-responsive mb-4">
      <table class="table table-bordered align-middle bg-white">
        <thead class="table-light">
          <tr>
            <th scope="col">Kendaraan</th>
            <th scope="col">Nomor Polisi</th>
            <th scope="col" class="text-end">Harga per Hari</th>
            <th scope="col" class="text-center">Durasi</th>
            <th scope="col" class="text-end">Subtotal</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($detail as $item): ?>
            <?php
            $harga = (int) ($item['hrg_sewa'] ?? 0);
            $hari = (int) ($item['n_hari'] ?? 0);
            ?>
            <tr>
              <td><?= $e($item['nm_mobil'] ?? '-') ?></td>
              <td><?= $e($item['no_plat'] ?? '-') ?></td>
              <td class="text-end text-nowrap"><?= $e($formatRupiah($harga)) ?></td>
              <td class="text-center"><?= $e($hari) ?> hari</td>
              <td class="text-end text-nowrap"><?= $e($formatRupiah($harga * $hari)) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <div class="row g-4">
      <!-- Rincian pembayaran DP -->
      <div class="col-lg-6">
        <div class="card border-success h-100">
          <div class="card-header bg-light">
            <h2 class="h5 mb-0">Pembayaran DP</h2>
          </div>
          <div class="card-body">
            <dl class="row mb-0">
              <dt class="col-sm-5">Kode pembayaran</dt>
              <dd class="col-sm-7"><?= $e($pembayaran['kode_bayar'] ?? '-') ?></dd>
              <dt class="col-sm-5">Metode</dt>
              <dd class="col-sm-7"><?= $e($pembayaran['mtode_byr'] ?? '-') ?></dd>
              <dt class="col-sm-5">Total sewa</dt>
              <dd class="col-sm-7"><?= $e($formatRupiah($pembayaran['total'] ?? 0)) ?></dd>
              <dt class="col-sm-5">Nominal DP (30%)</dt>
              <dd class="col-sm-7"><strong><?= $e($formatRupiah($pembayaran['nominal'] ?? 0)) ?></strong></dd>
              <dt class="col-sm-5">Status pembayaran</dt>
              <dd class="col-sm-7"><span class="badge <?= $e($kelasBadge) ?>"><?= $e($labelBadge) ?></span></dd>
              <dt class="col-sm-5">Waktu pencatatan</dt>
              <dd class="col-sm-7"><?= $e($formatWaktu($pembayaran['tgl_wkt_byr'] ?? null)) ?></dd>
              <dt class="col-sm-5">Waktu verifikasi</dt>
              <dd class="col-sm-7"><?= $e($formatWaktu($pembayaran['tgl_wkt_ver'] ?? null)) ?></dd>
            </dl>
          </div>
        </div>
      </div>

      <!-- Bukti + formulir -->
      <div class="col-lg-6">
        <div class="card border-0 shadow-sm h-100">
          <div class="card-header bg-light">
            <h2 class="h5 mb-0">Bukti Transfer</h2>
          </div>
          <div class="card-body">
            <?php if ($sudahUnggah): ?>
              <p class="text-muted mb-2">Bukti yang telah diunggah:</p>
              <a href="<?= $e(base_url('uploads/bayar/' . rawurlencode($fotoBukti))) ?>" target="_blank"
                rel="noopener noreferrer">
                <img src="<?= $e(base_url('uploads/bayar/' . rawurlencode($fotoBukti))) ?>" alt="Bukti transfer DP"
                  class="img-thumbnail mb-3" style="max-width: 320px; max-height: 240px;">
              </a>
            <?php else: ?>
              <p class="text-muted">Bukti transfer belum diunggah.</p>
            <?php endif; ?>

            <?php if ($bolehUnggah): ?>
              <p class="mb-3">
                Transfer sesuai nominal DP
                <strong><?= $e($formatRupiah($pembayaran['nominal'] ?? 0)) ?></strong>,
                lalu unggah bukti transfernya. Nominal tidak dapat diubah dari formulir ini.
              </p>
              <form action="<?= $e(site_url('pembayaran/unggah')) ?>" method="post" enctype="multipart/form-data">
                <input type="hidden" name="kode_sewa" value="<?= $e($kodeSewa) ?>">
                <div class="mb-3">
                  <label for="ft_byr" class="form-label">
                    <?= $sudahUnggah ? 'Ganti bukti transfer' : 'Bukti transfer' ?>
                    <span class="text-danger">*</span>
                  </label>
                  <input type="file" class="form-control" id="ft_byr" name="ft_byr"
                    accept="image/jpeg,image/png,image/webp" required>
                  <small class="form-text text-muted">Format JPG, PNG, atau WEBP. Maksimal 2 MB.</small>
                </div>
                <button type="submit" class="btn btn-success">
                  <?= $sudahUnggah ? 'Unggah Ulang Bukti' : 'Unggah Bukti' ?>
                </button>
              </form>
              <?php if ($sudahUnggah): ?>
                <p class="small text-muted mt-3 mb-0">Status tetap <strong>pending</strong> sampai admin melakukan
                  verifikasi.</p>
              <?php endif; ?>
            <?php elseif ($statusBayar === 'valid'): ?>
              <div class="alert alert-success mb-0">Pembayaran telah diverifikasi dan diterima. Transaksi disetujui.</div>
            <?php elseif ($statusBayar === 'invalid'): ?>
              <div class="alert alert-danger mb-0">Pembayaran ditolak oleh admin. Transaksi ditolak.</div>
            <?php else: ?>
              <div class="alert alert-secondary mb-0">Pembayaran tidak dapat diubah pada status transaksi saat ini.</div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    </div>

    <div class="mt-4">
      <a class="btn btn-outline-secondary" href="<?= $e(site_url('pembayaran')) ?>">
        <i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Kembali ke Daftar Pembayaran
      </a>
    </div>
  </div>
</section>
<?php require APPPATH . 'views/katalog/footer.php'; ?>