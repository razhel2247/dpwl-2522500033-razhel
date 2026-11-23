<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';

$daftarPelunasan = $daftarPelunasan ?? [];
$flash = $flash ?? null;
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
?>

<div class="content-wrapper">
  <section class="content-header">
    <h1>Pelunasan Transaksi <small>Daftar transaksi dengan sisa kewajiban pembayaran</small></h1>
    <ol class="breadcrumb">
      <li><a href="<?= $e(site_url('admin')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a></li>
      <li class="active">Pelunasan Transaksi</li>
    </ol>
  </section>

  <section class="content">
    <?php if (is_array($flash) && !empty($flash['message'])): ?>
      <?php $jenisFlash = ($flash['type'] ?? '') === 'success' ? 'success' : (($flash['type'] ?? '') === 'info' ? 'info' : 'danger'); ?>
      <div class="alert alert-<?= $e($jenisFlash) ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= $e($flash['message']) ?>
      </div>
    <?php endif; ?>

    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title">Transaksi yang Dapat Diproses Pelunasannya</h3>
      </div>
      <div class="box-body table-responsive">
        <table class="table table-bordered table-striped">
          <thead>
            <tr>
              <th>No.</th>
              <th>Kode Sewa</th>
              <th>Penyewa</th>
              <th>No. HP</th>
              <th>Periode Sewa</th>
              <th>Status Transaksi</th>
              <th>Total Transaksi</th>
              <th>Total Terbayar</th>
              <th>Sisa Kewajiban</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if (!$daftarPelunasan): ?>
              <tr>
                <td colspan="10" class="text-center">
                  Tidak ada transaksi yang memiliki sisa kewajiban untuk dilunasi.
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($daftarPelunasan as $i => $item): ?>
                <tr>
                  <td><?= (int) $i + 1 ?></td>
                  <td><?= $e($item['kode_sewa'] ?? '') ?></td>
                  <td>
                    <?= $e($item['nm_lngkp'] ?? '') ?><br>
                    <small><?= $e($item['user_plg'] ?? '') ?></small>
                  </td>
                  <td><?= $e($item['no_hp'] ?? '') ?></td>
                  <td>
                    <?= $e($item['tgl_mulai'] ?? '') ?>
                    s.d.
                    <?= $e($item['tgl_selesai'] ?? '') ?>
                  </td>
                  <td><?= $e($item['status_sewa'] ?? '') ?></td>
                  <td>Rp <?= number_format((int) ($item['total_transaksi'] ?? 0), 0, ',', '.') ?></td>
                  <td>Rp <?= number_format((int) ($item['total_terbayar'] ?? 0), 0, ',', '.') ?></td>
                  <td><strong>Rp <?= number_format((int) ($item['sisa_kewajiban'] ?? 0), 0, ',', '.') ?></strong></td>
                  <td>
                    <a class="btn btn-primary btn-xs"
                      href="<?= $e(site_url('admin/pembayaran/pelunasan/' . str_replace('/', '~', (string) ($item['kode_sewa'] ?? '')))) ?>">
                      <i class="fa fa-search"></i> Detail / Proses
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </section>
</div>

<?php require APPPATH . 'views/templates/footer.php'; ?>