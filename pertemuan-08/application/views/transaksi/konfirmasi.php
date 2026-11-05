<?php
require APPPATH . 'views/katalog/header.php';
$e = static function ($value): string {
return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
require APPPATH . 'views/katalog/navbar.php';
$formatRupiah = static function ($nilai): string {
return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};
$detail = is_array($keranjang['detail'] ?? null) ? $keranjang['detail'] : [];
$kodeSewa = (string) ($keranjang['kode_sewa'] ?? '');
?>
<section class="bg-success py-4">
<div class="container">
<h1 class="h2 text-white mt-3">Konfirmasi Transaksi</h1>
<p class="text-white mb-0">Periksa kembali rincian sewa dan uang muka sebelum mengajukan
transaksi.</p>
</div>
</section>
<section class="py-5">
<div class="container">
<div class="card border-0 shadow-sm mb-4">
<div class="card-header bg-light">
<h2 class="h5 mb-0">Ringkasan Sewa</h2>
</div>
<div class="card-body">
<dl class="row mb-0">
<dt class="col-sm-4">Kode transaksi</dt>
<dd class="col-sm-8"><?= $e($kodeSewa) ?></dd>
<dt class="col-sm-4">Tanggal mulai</dt>
<dd class="col-sm-8"><?= $e($keranjang['tgl_mulai'] ?? '-') ?></dd>
<dt class="col-sm-4">Tanggal selesai</dt>
<dd class="col-sm-8"><?= $e($keranjang['tgl_selesai'] ?? '-') ?></dd>
<dt class="col-sm-4">Waktu pengambilan</dt>
<dd class="col-sm-8"><?= $e(substr((string) ($keranjang['wkt_ambil'] ?? '-'), 0, 5)) ?>
WIB</dd>
</dl>
</div>
</div>
<div class="table-responsive mb-4">
<table class="table table-bordered table-hover align-middle bg-white">
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
$subtotal = $harga * $hari;
?>
<tr>
<td><?= $e($item['nm_mobil'] ?? '-') ?></td>
<td><?= $e($item['no_plat'] ?? '-') ?></td>
<td class="text-end text-nowrap"><?= $e($formatRupiah($harga)) ?></td>
<td class="text-center"><?= $e($hari) ?> hari</td>
<td class="text-end text-nowrap"><?= $e($formatRupiah($subtotal)) ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
<div class="row justify-content-end mb-4">
<div class="col-lg-6 col-xl-5">
<div class="card border-success">
<div class="card-body">
<div class="d-flex justify-content-between gap-3 mb-2">
<span>Total sewa</span>
<strong class="text-nowrap"><?= $e($formatRupiah($total)) ?></strong>
</div>
<div class="d-flex justify-content-between gap-3 mb-2">
<span>Uang muka (DP 30%)</span>
<strong class="text-nowrap"><?= $e($formatRupiah($dp)) ?></strong>
</div>
<hr>
<div class="d-flex justify-content-between gap-3">
<span>Sisa setelah DP</span>
<strong class="text-nowrap"><?= $e($formatRupiah($sisa)) ?></strong>
</div>
<p class="small text-muted mt-3 mb-0">Saat konfirmasi diajukan, sistem mencatat DP ini
sebagai pembayaran transfer berstatus pending. Bukti pembayaran dan verifikasi dilakukan pada
P9.</p>
</div>
</div>
</div>
</div>
<div class="d-flex flex-column flex-sm-row justify-content-between gap-3">
<a class="btn btn-outline-secondary" href="<?= $e(site_url('transaksi/keranjang?kode_sewa=' .
rawurlencode($kodeSewa))) ?>">
<i class="fas fa-arrow-left me-1" aria-hidden="true"></i> Kembali ke Keranjang
</a>
<form method="post" action="<?= $e(site_url('transaksi/checkout')) ?>" onsubmit="return
confirm('Ajukan transaksi ini kepada admin?');">
<input type="hidden" name="kode_sewa" value="<?= $e($kodeSewa) ?>">
<button type="submit" class="btn btn-success btn-lg">
<i class="fas fa-check me-1" aria-hidden="true"></i> Konfirmasi dan Ajukan Transaksi
</button>
</form>
