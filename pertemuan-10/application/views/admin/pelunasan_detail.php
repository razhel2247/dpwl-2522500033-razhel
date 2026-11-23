<?php
require APPPATH . 'views/templates/header.php';
require APPPATH . 'views/templates/sidebar.php';
$detailPelunasan = $detailPelunasan ?? [];
$flash = $flash ?? null;
$riwayatPembayaran = $detailPelunasan['riwayat_pembayaran'] ?? [];
$daftarKendaraan = $detailPelunasan['daftar_kendaraan'] ?? [];
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$totalTransaksi = (int) ($detailPelunasan['total_transaksi'] ?? 0);
$totalTerbayar = (int) ($detailPelunasan['total_terbayar'] ?? 0);
$sisaKewajiban = (int) ($detailPelunasan['sisa_kewajiban'] ?? 0);
$statusSewa = (string) ($detailPelunasan['status_sewa'] ?? '');
$adaRecordPelunasan = false;
foreach ($riwayatPembayaran as $pembayaran) {
  if (($pembayaran['jns_byr'] ?? '') === 'pelunasan') {
    $adaRecordPelunasan = true;
    break;
  }
}
$statusMemenuhiSyarat = in_array($statusSewa, ['disetujui', 'lunas'], true);
$bolehPelunasan = $statusMemenuhiSyarat
  && $sisaKewajiban > 0
  && !$adaRecordPelunasan;
$kodeSewa = (string) ($detailPelunasan['kode_sewa'] ?? '');
$urlProses = site_url('admin/pembayaran/pelunasan/proses/' . str_replace('/', '~', $kodeSewa));
?>
<div class="content-wrapper">
  <section class="content-header">
    <h1>Detail Pelunasan <small>Informasi transaksi dan pencatatan pembayaran akhir</small></h1>
    <ol class="breadcrumb">
      <li><a href="<?= $e(site_url('admin')) ?>">Dashboard</a></li>
      <li><a href="<?= $e(site_url('admin/pembayaran/pelunasan')) ?>">Pelunasan Transaksi</a></li>
      <li class="active">Detail</li>
    </ol>
  </section>
  <section class="content">
    <?php if (is_array($flash) && !empty($flash['message'])): ?>
      <?php $jenisFlash = ($flash['type'] ?? '') === 'success' ? 'success' : (($flash['type'] ?? '') ===
        'info' ? 'info' : 'danger'); ?>
      <div class="alert alert-<?= $e($jenisFlash) ?> alert-dismissible">
        <button type="button" class="close" data-dismiss="alert">&times;</button>
        <?= $e($flash['message']) ?>
      </div>
    <?php endif; ?>
    <div class="box">
      <div class="box-header with-border">
        <h3 class="box-title">Informasi Transaksi dan Penyewa</h3>
      </div>
      <div class="box-body">
        <dl class="dl-horizontal">
          <dt>Kode transaksi</dt>
          <dd><?= $e($kodeSewa) ?></dd>
          <dt>Nama penyewa</dt>
          <dd><?= $e($detailPelunasan['nm_lngkp'] ?? '') ?></dd>
          <dt>Username</dt>
          <dd><?= $e($detailPelunasan['user_plg'] ?? '') ?></dd>
          <dt>NIK</dt>
          <dd><?= $e($detailPelunasan['nik'] ?? '') ?></dd>
          <dt>Nomor HP</dt>
          <dd><?= $e($detailPelunasan['no_hp'] ?? '') ?></dd>
          <dt>Alamat</dt>
          <dd><?= nl2br($e($detailPelunasan['almt_lngkp'] ?? '')) ?></dd>
          <dt>Periode sewa</dt>
          <dd><?= $e($detailPelunasan['tgl_mulai'] ?? '') ?> s.d. <?= $e($detailPelunasan['tgl_selesai']
                 ?? '') ?></dd>
          <dt>Waktu pengambilan</dt>
          <dd><?= $e($detailPelunasan['wkt_ambil'] ?? '') ?></dd>
          <dt>Status transaksi</dt>
          <dd><?= $e($statusSewa) ?></dd>
        </dl>
        <h4>Rincian Kendaraan</h4>
        <?php if (!$daftarKendaraan): ?>
          <div class="alert alert-warning">Data kendaraan pada transaksi ini tidak ditemukan.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>No.</th>
                  <th>No. Polisi</th>
                  <th>Nama Kendaraan</th>
                  <th>Tarif Snapshot / Hari</th>
                  <th>Lama Sewa</th>
                  <th>Subtotal</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($daftarKendaraan as $i => $kendaraan): ?>
                  <tr>
                    <td><?= (int) $i + 1 ?></td>
                    <td><?= $e($kendaraan['no_plat'] ?? '') ?></td>
                    <td><?= $e($kendaraan['nm_mobil'] ?? '') ?></td>
                    <td>Rp <?= number_format((int) ($kendaraan['hrg_sewa'] ?? 0), 0, ',', '.') ?></td>
                    <td><?= (int) ($kendaraan['n_hari'] ?? 0) ?> hari</td>
                    <td>Rp <?= number_format((int) ($kendaraan['subtotal'] ?? 0), 0, ',', '.') ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
              <tfoot>
                <tr>
                  <th colspan="5" class="text-right">Total transaksi</th>
                  <th>Rp <?= number_format($totalTransaksi, 0, ',', '.') ?></th>
                </tr>
              </tfoot>
            </table>
          </div>
        <?php endif; ?>
        <h4>Ringkasan Pembayaran</h4>
        <div class="table-responsive">
          <table class="table table-bordered">
            <tbody>
              <tr>
                <th style="width:35%">Total transaksi</th>
                <td>Rp <?= number_format($totalTransaksi, 0, ',', '.') ?></td>
              </tr>
              <tr>
                <th>Total pembayaran valid sebelumnya</th>
                <td>Rp <?= number_format($totalTerbayar, 0, ',', '.') ?></td>
              </tr>
              <tr class="active">
                <th>Sisa kewajiban pelunasan</th>
                <td><strong>Rp <?= number_format($sisaKewajiban, 0, ',', '.') ?></strong></td>
              </tr>
            </tbody>
          </table>
        </div>
        <h4>Riwayat Pembayaran</h4>
        <div class="table-responsive">
          <table class="table table-bordered table-striped">
            <thead>
              <tr>
                <th>No.</th>
                <th>Kode Bayar</th>
                <th>Tanggal</th>
                <th>Jenis</th>
                <th>Metode</th>
                <th>Nominal</th>
                <th>Status</th>
                <th>Bukti</th>
              </tr>
            </thead>
            <tbody>
              <?php if (!$riwayatPembayaran): ?>
                <tr>
                  <td colspan="8" class="text-center">Belum ada riwayat pembayaran.</td>
                </tr>
              <?php else: ?>
                <?php foreach ($riwayatPembayaran as $i => $pembayaran): ?>
                  <?php $namaBukti = trim((string) ($pembayaran['ft_byr'] ?? '')); ?>
                  <tr>
                    <td><?= (int) $i + 1 ?></td>
                    <td><?= $e($pembayaran['kode_bayar'] ?? '') ?></td>
                    <td><?= $e($pembayaran['tgl_wkt_byr'] ?? '') ?></td>
                    <td><?= $e($pembayaran['jns_byr'] ?? '') ?></td>
                    <td><?= $e($pembayaran['mtode_byr'] ?? '') ?></td>
                    <td>Rp <?= number_format((int) ($pembayaran['nominal'] ?? 0), 0, ',', '.') ?></td>
                    <td><?= $e($pembayaran['sttus_byr'] ?? '') ?></td>
                    <td>
                      <?php if ($namaBukti !== ''): ?>
                        <a href="<?= $e(base_url('uploads/bayar/' . rawurlencode($namaBukti))) ?>" target="_blank"
                          rel="noopener">Lihat bukti</a>
                      <?php else: ?>
                        -
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
    <div class="box box-primary">
      <div class="box-header with-border">
        <h3 class="box-title">Pencatatan Pelunasan</h3>
      </div>
      <div class="box-body">
        <?php if ($bolehPelunasan): ?>
          <div class="alert alert-info">
            Pelunasan dilakukan satu kali sebesar seluruh sisa kewajiban.
            Pembayaran akan langsung dicatat dengan status <strong>valid</strong>
            dan status transaksi diperbarui menjadi <strong>lunas</strong>.
            Status kendaraan tidak diubah pada P10 karena proses penyerahan
            kendaraan dilakukan pada P11.
          </div>

          <form method="post" action="<?= $e($urlProses) ?>" enctype="multipart/form-data" id="form-
pelunasan">

            <div class="form-group">
              <label for="mtode_byr">Metode pembayaran</label>
              <select name="mtode_byr" id="mtode_byr" class="form-control" required>
                <option value="">-- Pilih metode pembayaran --</option>
                <option value="tunai">Tunai</option>
                <option value="transfer">Transfer</option>
              </select>
            </div>
            <div class="form-group" id="grup-bukti-transfer" style="display:none">
              <label for="ft_byr">Bukti transfer</label>
              <input type="file" name="ft_byr" id="ft_byr" class="form-control"
                accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
              <p class="help-block">Unggah bukti transfer yang diterima admin melalui WhatsApp. Format

                JPEG, PNG, atau WEBP, maksimal 5 MB.</p>

            </div>
            <button type="submit" class="btn btn-success" onclick="return confirm('Catat pelunasan sebesar Rp <?= number_format(
              $sisaKewajiban,
              0,

              ',',
              '.'
            ) ?>? Pastikan pembayaran benar-benar telah diterima.')">

              <i class="fa fa-check"></i> Catat Pelunasan Rp <?= number_format(
                $sisaKewajiban,
                0,
                ',',

                '.'
              ) ?>

            </button>
          </form>
        <?php elseif ($adaRecordPelunasan): ?>
          <div class="alert alert-success">
            Transaksi ini sudah memiliki record pelunasan. Pencatatan pelunasan tambahan tidak diizinkan.
          </div>
        <?php elseif ($sisaKewajiban <= 0): ?>
          <div class="alert alert-info">Tidak terdapat sisa kewajiban pembayaran.</div>
        <?php else: ?>
          <div class="alert alert-warning">
            Status transaksi saat ini tidak memenuhi syarat pencatatan pelunasan pada P10.
          </div>
        <?php endif; ?>
      </div>
    </div>
    <a href="<?= $e(site_url('admin/pembayaran/dokumen/' . str_replace('/', '~', $kodeSewa))) ?>" class="btn btn-info"
      target="_blank" rel="noopener">
      <i class="fa fa-print"></i> Lihat / Cetak Dokumen Transaksi
    </a>
    <a href="<?= $e(site_url('admin/pembayaran/pelunasan')) ?>" class="btn btn-default">
      <i class="fa fa-arrow-left"></i> Kembali ke daftar pelunasan
    </a>
  </section>
</div>
<script>
  (function () {
    'use strict';
    var metode = document.getElementById('mtode_byr');
    var grupBukti = document.getElementById('grup-bukti-transfer');
    var inputBukti = document.getElementById('ft_byr');
    if (!metode || !grupBukti || !inputBukti) {
      return;
    }
    function perbaruiFormBukti() {
      var perluBukti = metode.value === 'transfer';
      grupBukti.style.display = perluBukti ? '' : 'none';
      inputBukti.required = perluBukti;
      if (!perluBukti) {
        inputBukti.value = '';
      }
    }
    metode.addEventListener('change', perbaruiFormBukti);
    perbaruiFormBukti();
  })();
</script>
<?php require APPPATH . 'views/templates/footer.php'; ?>