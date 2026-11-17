<?php
// P9 BARU - application/views/pembayaran/daftar.php
// Variabel dari Controller: $daftar (array), $flash (?array), $jumlahKeranjang (int)
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
$badgeBayar = static function (string $status): array {
  return match ($status) {
    'valid' => ['bg-success', 'Valid'],
    'invalid' => ['bg-danger', 'Invalid'],
    default => ['bg-warning text-dark', 'Pending'],
  };
};

$daftar = is_array($daftar ?? null) ? $daftar : [];
$flashType = (string) ($flash['type'] ?? '');
$flashMessage = (string) ($flash['message'] ?? '');
?>
<section class="bg-success py-4">
  <div class="container">
    <h1 class="h2 text-white mt-3">Pembayaran Saya</h1>
    <p class="text-white mb-0">Unggah bukti transfer DP dan pantau status verifikasi admin.</p>
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

    <?php if (count($daftar) === 0): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5">
          <p class="mb-3">Belum ada pembayaran. Pembayaran DP dibuat otomatis saat Anda melakukan checkout.</p>
          <a class="btn btn-success" href="<?= $e(site_url('katalog')) ?>">Lihat Katalog</a>
        </div>
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-bordered table-hover align-middle bg-white">
          <thead class="table-light">
            <tr>
              <th scope="col">Kode Transaksi</th>
              <th scope="col">Kode Pembayaran</th>
              <th scope="col" class="text-end">Nominal DP</th>
              <th scope="col">Bukti</th>
              <th scope="col">Status Pembayaran</th>
              <th scope="col">Status Transaksi</th>
              <th scope="col">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($daftar as $baris): ?>
              <?php [$kelasBadge, $labelBadge] = $badgeBayar((string) ($baris['sttus_byr'] ?? '')); ?>
              <?php $sudahUnggah = (string) ($baris['ft_byr'] ?? '') !== ''; ?>
              <?php $masihPending = ($baris['sttus_byr'] ?? '') === 'pending'; ?>
              <tr>
                <td><?= $e($baris['kode_sewa'] ?? '-') ?></td>
                <td><?= $e($baris['kode_bayar'] ?? '-') ?></td>
                <td class="text-end text-nowrap"><?= $e($formatRupiah($baris['nominal'] ?? 0)) ?></td>
                <td>
                  <?php if ($sudahUnggah): ?>
                    <span class="small">Diunggah<br><?= $e($formatWaktu($baris['tgl_wkt_byr'] ?? null)) ?></span>
                  <?php else: ?>
                    <span class="text-muted small">Belum diunggah</span>
                  <?php endif; ?>
                </td>
                <td><span class="badge <?= $e($kelasBadge) ?>"><?= $e($labelBadge) ?></span></td>
                <td><?= $e($baris['status_sewa'] ?? '-') ?></td>
                <td class="text-nowrap">
                  <a class="btn btn-sm btn-outline-success"
                    href="<?= $e(site_url('pembayaran/detail?kode_sewa=' . rawurlencode((string) $baris['kode_sewa']))) ?>">
                    <?= ($masihPending && !$sudahUnggah) ? 'Bayar / Unggah Bukti' : 'Lihat Detail' ?>
                  </a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</section>
<?php require APPPATH . 'views/katalog/footer.php'; ?>