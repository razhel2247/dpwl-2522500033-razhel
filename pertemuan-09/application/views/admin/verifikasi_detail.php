<?php
// P9 BARU - application/views/admin/verifikasi_detail.php
// Variabel dari Controller: $username, $pembayaran (array), $flash (?array)
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

$detail = is_array($pembayaran['detail'] ?? null) ? $pembayaran['detail'] : [];
$kodeSewa = (string) ($pembayaran['kode_sewa'] ?? '');
$statusBayar = (string) ($pembayaran['sttus_byr'] ?? '');
$statusSewa = (string) ($pembayaran['status_sewa'] ?? '');
$fotoBukti = (string) ($pembayaran['ft_byr'] ?? '');
$adaBukti = $fotoBukti !== '';
$urlBukti = $adaBukti ? base_url('uploads/bayar/' . rawurlencode($fotoBukti)) : '';

// Tombol keputusan hanya tampil bila masih pending, transaksi diajukan, dan bukti ada.
$bisaVerifikasi = $statusBayar === 'pending' && $statusSewa === 'diajukan' && $adaBukti;

$flash = $flash ?? null;
$flashClass = (($flash['type'] ?? '') === 'success') ? 'alert-success' : 'alert-danger';
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>
      Detail Pembayaran
      <small><?= $e($kodeSewa) ?></small>
    </h1>
    <ol class="breadcrumb">
      <li>
        <a href="<?= $e(site_url('admin')) ?>"><i class="fa fa-dashboard"></i> Dashboard</a>
      </li>
      <li>
        <a href="<?= $e(site_url('admin/pembayaran')) ?>">Verifikasi Pembayaran</a>
      </li>
      <li class="active">Detail</li>
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

    <div class="row">
      <!-- Kolom kiri: penyewa, transaksi, pembayaran -->
      <div class="col-md-6">
        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Penyewa</h3>
          </div>
          <div class="box-body">
            <dl class="dl-horizontal">
              <dt>Nama</dt>
              <dd><?= $e($pembayaran['nm_lngkp'] ?? '-') ?></dd>
              <dt>Username</dt>
              <dd><?= $e($pembayaran['user_plg'] ?? '-') ?></dd>
              <dt>No. HP</dt>
              <dd><?= $e($pembayaran['no_hp'] ?? '-') ?></dd>
            </dl>
          </div>
        </div>

        <div class="box box-primary">
          <div class="box-header with-border">
            <h3 class="box-title">Transaksi</h3>
          </div>
          <div class="box-body">
            <dl class="dl-horizontal">
              <dt>Kode transaksi</dt>
              <dd><?= $e($kodeSewa) ?></dd>
              <dt>Status transaksi</dt>
              <dd><span class="label label-info"><?= $e($statusSewa) ?></span></dd>
              <dt>Periode</dt>
              <dd><?= $e($pembayaran['tgl_mulai'] ?? '-') ?> s.d. <?= $e($pembayaran['tgl_selesai'] ?? '-') ?></dd>
              <dt>Waktu ambil</dt>
              <dd><?= $e(substr((string) ($pembayaran['wkt_ambil'] ?? '-'), 0, 5)) ?> WIB</dd>
            </dl>
            <div class="table-responsive">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Kendaraan</th>
                    <th>No. Polisi</th>
                    <th>Harga/hari</th>
                    <th>Hari</th>
                    <th>Subtotal</th>
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
                      <td><?= $e($formatRupiah($harga)) ?></td>
                      <td><?= $e($hari) ?></td>
                      <td><?= $e($formatRupiah($harga * $hari)) ?></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
                <tfoot>
                  <tr>
                    <th colspan="4" class="text-right">Total sewa</th>
                    <th><?= $e($formatRupiah($pembayaran['total'] ?? 0)) ?></th>
                  </tr>
                </tfoot>
              </table>
            </div>
          </div>
        </div>
      </div>

      <!-- Kolom kanan: pembayaran, bukti, keputusan -->
      <div class="col-md-6">
        <div class="box box-warning">
          <div class="box-header with-border">
            <h3 class="box-title">Pembayaran DP</h3>
          </div>
          <div class="box-body">
            <dl class="dl-horizontal">
              <dt>Kode pembayaran</dt>
              <dd><?= $e($pembayaran['kode_bayar'] ?? '-') ?></dd>
              <dt>Metode</dt>
              <dd><?= $e($pembayaran['mtode_byr'] ?? '-') ?></dd>
              <dt>Nominal DP</dt>
              <dd><strong><?= $e($formatRupiah($pembayaran['nominal'] ?? 0)) ?></strong></dd>
              <dt>Status</dt>
              <dd>
                <?php if ($statusBayar === 'valid'): ?>
                  <span class="label label-success">Valid</span>
                <?php elseif ($statusBayar === 'invalid'): ?>
                  <span class="label label-danger">Invalid</span>
                <?php else: ?>
                  <span class="label label-warning">Pending</span>
                <?php endif; ?>
              </dd>
              <dt>Waktu pencatatan/unggah</dt>
              <dd><?= $e($formatWaktu($pembayaran['tgl_wkt_byr'] ?? null)) ?></dd>
              <dt>Waktu verifikasi</dt>
              <dd><?= $e($formatWaktu($pembayaran['tgl_wkt_ver'] ?? null)) ?></dd>
            </dl>
          </div>
        </div>

        <div class="box box-success">
          <div class="box-header with-border">
            <h3 class="box-title">Bukti Transfer</h3>
          </div>
          <div class="box-body">
            <?php if ($adaBukti): ?>
              <a href="<?= $e($urlBukti) ?>" target="_blank" rel="noopener noreferrer">
                <img src="<?= $e($urlBukti) ?>" alt="Bukti transfer DP" class="img-thumbnail"
                  style="max-width: 100%; max-height: 420px;">
              </a>
              <p class="help-block">Klik gambar untuk membuka ukuran penuh di tab baru.</p>
            <?php else: ?>
              <div class="callout callout-warning" style="margin-bottom:0;">
                <p>Penyewa belum mengunggah bukti transfer. Verifikasi belum dapat dilakukan.</p>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="box box-default">
          <div class="box-header with-border">
            <h3 class="box-title">Keputusan Verifikasi</h3>
          </div>
          <div class="box-body">
            <?php if ($bisaVerifikasi): ?>
              <p>Periksa bukti transfer dan pastikan nominalnya sesuai, lalu tentukan keputusan.</p>
              <form method="post" action="<?= $e(site_url('admin/pembayaran/verifikasi')) ?>" style="display:inline;"
                onsubmit="return confirm('Terima pembayaran ini? Transaksi akan disetujui.');">
                <input type="hidden" name="kode_sewa" value="<?= $e($kodeSewa) ?>">
                <input type="hidden" name="keputusan" value="terima">
                <button type="submit" class="btn btn-success">
                  <i class="fa fa-check"></i> Terima Pembayaran
                </button>
              </form>
              <form method="post" action="<?= $e(site_url('admin/pembayaran/verifikasi')) ?>" style="display:inline;"
                onsubmit="return confirm('Tolak pembayaran ini? Transaksi akan ditolak.');">
                <input type="hidden" name="kode_sewa" value="<?= $e($kodeSewa) ?>">
                <input type="hidden" name="keputusan" value="tolak">
                <button type="submit" class="btn btn-danger">
                  <i class="fa fa-times"></i> Tolak Pembayaran
                </button>
              </form>
            <?php elseif ($statusBayar !== 'pending'): ?>
              <div class="callout callout-info" style="margin-bottom:0;">
                <p>Pembayaran ini sudah diverifikasi pada
                  <strong><?= $e($formatWaktu($pembayaran['tgl_wkt_ver'] ?? null)) ?></strong>
                  dan tidak dapat diproses kembali.
                </p>
              </div>
            <?php elseif (!$adaBukti): ?>
              <p class="text-muted" style="margin-bottom:0;">Tombol keputusan tersedia setelah penyewa mengunggah bukti.
              </p>
            <?php else: ?>
              <p class="text-muted" style="margin-bottom:0;">Status transaksi saat ini (<?= $e($statusSewa) ?>) tidak
                memungkinkan verifikasi.</p>
            <?php endif; ?>
          </div>
          <div class="box-footer">
            <a href="<?= $e(site_url('admin/pembayaran')) ?>" class="btn btn-default">
              <i class="fa fa-arrow-left"></i> Kembali
            </a>
          </div>
        </div>
      </div>
    </div>
  </section>
</div>
<?php
require APPPATH . '/views/templates/footer.php';
?>