<?php
// P9 BARU - application/views/admin/verifikasi_view.php
// Variabel dari Controller: $username, $pending (array), $riwayat (array), $flash (?array)
require APPPATH . '/views/templates/header.php';
require APPPATH . '/views/templates/sidebar.php';

$e = static function ($value): string {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
$formatRupiah = static function ($nilai): string {
    return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};
$formatWaktu = static function ($nilai): string {
    $ts = $nilai === null ? false : strtotime((string) $nilai);
    return $ts === false ? '-' : date('d-m-Y H:i', $ts);
};

$pending = is_array($pending ?? null) ? $pending : [];
$riwayat = is_array($riwayat ?? null) ? $riwayat : [];
$flash = $flash ?? null;
$flashClass = (($flash['type'] ?? '') === 'success') ? 'alert-success' : 'alert-danger';
?>
<div class="content-wrapper">
    <section class="content-header">
        <h1>
            Verifikasi Pembayaran
            <small>Periksa bukti transfer DP penyewa</small>
        </h1>
        <ol class="breadcrumb">
            <li>
                <a href="<?= $e(site_url('admin')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a>
            </li>
            <li class="active">Verifikasi Pembayaran</li>
        </ol>
    </section>

    <section class="content">
        <?php if (!empty($flash)): ?>
            <div class="alert <?= $flashClass ?> alert-dismissible">
                <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                <i class="fa <?= (($flash['type'] ?? '') === 'success') ? 'fa-check' : 'fa-warning' ?>"></i>
                <?= $e($flash['message'] ?? '') ?>
            </div>
        <?php endif; ?>

        <!-- Menunggu verifikasi -->
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">Menunggu Verifikasi</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped js-data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Transaksi</th>
                                <th>Penyewa</th>
                                <th>Nominal DP</th>
                                <th>Waktu Pencatatan/Unggah</th>
                                <th>Bukti</th>
                                <th class="no-sort">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending as $no => $baris): ?>
                                <?php $adaBukti = (string) ($baris['ft_byr'] ?? '') !== ''; ?>
                                <tr>
                                    <td><?= $no + 1 ?></td>
                                    <td><?= $e($baris['kode_sewa'] ?? '-') ?></td>
                                    <td><?= $e($baris['nm_lngkp'] ?? '-') ?> <small class="text-muted">(<?= $e($baris['user_plg'] ?? '-') ?>)</small></td>
                                    <td><?= $e($formatRupiah($baris['nominal'] ?? 0)) ?></td>
                                    <td><?= $e($formatWaktu($baris['tgl_wkt_byr'] ?? null)) ?></td>
                                    <td>
                                        <?php if ($adaBukti): ?>
                                            <span class="label label-success">Sudah diunggah</span>
                                        <?php else: ?>
                                            <span class="label label-default">Belum diunggah</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a class="btn btn-primary btn-sm"
                                           href="<?= $e(site_url('admin/pembayaran/detail?kode_sewa=' . rawurlencode((string) $baris['kode_sewa']))) ?>">
                                            <i class="fa fa-search"></i> <?= $adaBukti ? 'Periksa' : 'Lihat' ?>
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Riwayat -->
        <div class="box box-default">
            <div class="box-header with-border">
                <h3 class="box-title">Riwayat Verifikasi</h3>
            </div>
            <div class="box-body">
                <div class="table-responsive">
                    <table class="table table-bordered table-striped js-data-table">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Kode Transaksi</th>
                                <th>Penyewa</th>
                                <th>Nominal DP</th>
                                <th>Waktu Verifikasi</th>
                                <th>Status Pembayaran</th>
                                <th>Status Transaksi</th>
                                <th class="no-sort">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($riwayat as $no => $baris): ?>
                                <tr>
                                    <td><?= $no + 1 ?></td>
                                    <td><?= $e($baris['kode_sewa'] ?? '-') ?></td>
                                    <td><?= $e($baris['nm_lngkp'] ?? '-') ?> <small class="text-muted">(<?= $e($baris['user_plg'] ?? '-') ?>)</small></td>
                                    <td><?= $e($formatRupiah($baris['nominal'] ?? 0)) ?></td>
                                    <td><?= $e($formatWaktu($baris['tgl_wkt_ver'] ?? null)) ?></td>
                                    <td>
                                        <?php if (($baris['sttus_byr'] ?? '') === 'valid'): ?>
                                            <span class="label label-success">Valid</span>
                                        <?php else: ?>
                                            <span class="label label-danger">Invalid</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= $e($baris['status_sewa'] ?? '-') ?></td>
                                    <td>
                                        <a class="btn btn-default btn-sm"
                                           href="<?= $e(site_url('admin/pembayaran/detail?kode_sewa=' . rawurlencode((string) $baris['kode_sewa']))) ?>">
                                            <i class="fa fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </section>
</div>
<?php
require APPPATH . '/views/templates/footer.php';
?>