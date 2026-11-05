<?php
require APPPATH . 'views/katalog/header.php';
$e = static function ($value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
require APPPATH . 'views/katalog/navbar.php';
$formatRupiah = static function ($nilai): string {
  return 'Rp ' . number_format((float) $nilai, 0, ',', '.');
};
$detail = is_array($keranjang['detail'] ?? null)
  ? $keranjang['detail']
  : [];
$flashType = (string) ($flash['type'] ?? '');
$flashMessage = (string) ($flash['message'] ?? '');
$flashClass = $flashType === 'success' ? 'alert-success' : 'alert-danger';
$zonaWaktu = new DateTimeZone('Asia/Jakarta');
$minimumMulai = (new DateTimeImmutable('now', $zonaWaktu))->modify('+2 hours');
$minTanggalMulai = $minimumMulai->format('Y-m-d');
$minTanggalSelesai = (new DateTimeImmutable((string) ($keranjang['tgl_mulai'] ?? $minTanggalMulai), $zonaWaktu))
  ->modify('+1 day')->format('Y-m-d');
?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css">
<style>
  .select2-container {
    width: 100% !important;
  }

  .select2-container--default .select2-selection--single {
    min-height: 38px;
    border: 1px solid #ced4da;
    border-radius: .375rem;
    display: flex;
    align-items: center;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    padding-left: .75rem;
    color: #212529;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
    right: .35rem;
  }

  .select2-dropdown {
    border-color: #ced4da;
  }

  .select2-search--dropdown .select2-search__field {
    outline: none;
    border: 1px solid #ced4da;
    border-radius: .25rem;
    padding: .375rem .5rem;
  }
</style>
<section class="bg-success py-4">
  <div class="container">
    <div class="row align-items-center">
      <div class="col-md-8">
        <h1 class="h2 text-white mt-3">Keranjang Sewa</h1>
        <p class="text-white mb-0">Periksa kendaraan dan periode sewa sebelum mengajukan transaksi.</p>
      </div>
      <div class="col-md-4 text-md-end mt-3 mt-md-0">
        <a class="btn btn-danger" href="<?= $e(site_url('katalog')) ?>#katalog">
          <i class="fas fa-car me-1"></i> Lanjut Pilih Kendaraan
        </a>
      </div>
    </div>
  </div>
</section>
<section class="py-5">
  <div class="container">
    <?php if ($flashMessage !== ''): ?>
      <div class="alert <?= $e($flashClass) ?> alert-dismissible fade show" role="alert">
        <?= $e($flashMessage) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
      </div>
    <?php endif; ?>
    <div class="card border-0 shadow-sm mb-4">
      <div class="card-header bg-white">
        <h2 class="h5 mb-0">Informasi Transaksi</h2>
      </div>

      <form method="post" action="<?= $e(site_url('transaksi/perbarui-periode')) ?>" id="form-periode-sewa">
        <input type="hidden" name="kode_sewa" value="<?= $e($keranjang['kode_sewa'] ?? '') ?>">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="text-muted small">Kode transaksi</div>
              <div class="fw-bold"><?= $e($keranjang['kode_sewa'] ?? '') ?></div>
            </div>
            <div class="col-md-6">
              <div class="text-muted small">Status</div>
              <span class="badge bg-secondary"><?= $e($keranjang['status_sewa'] ?? 'keranjang') ?></span>
            </div>
            <div class="col-md-4">
              <label for="tgl_mulai" class="form-label">Tanggal mulai</label>
              <input type="date" class="form-control" id="tgl_mulai" name="tgl_mulai" min="<?= $e($minTanggalMulai) ?>"
                value="<?= $e($keranjang['tgl_mulai'] ?? $minTanggalMulai) ?>" required>
            </div>
            <div class="col-md-4">
              <label for="tgl_selesai" class="form-label">Tanggal selesai</label>
              <input type="date" class="form-control" id="tgl_selesai" name="tgl_selesai"
                min="<?= $e($minTanggalSelesai) ?>" value="<?= $e($keranjang['tgl_selesai'] ?? $minTanggalSelesai) ?>"
                required>
            </div>
            <div class="col-md-4">
              <label for="wkt_ambil" class="form-label">Waktu pengambilan</label>
              <?php $waktuTersimpan = substr((string) ($keranjang['wkt_ambil'] ?? $minimumMulai->format('H:i:s')), 0, 5); ?>
              <select class="form-select" id="wkt_ambil" name="wkt_ambil"
                data-current-value="<?= $e($waktuTersimpan) ?>" required>
                <option value="<?= $e($waktuTersimpan) ?>"><?= $e($waktuTersimpan) ?></option>
              </select>
              <small class="text-muted" id="batas-waktu-mulai">Waktu mulai minimal dua jam dari sekarang (WIB).</small>
            </div>
          </div>
          <div class="mt-3">
            <button type="submit" class="btn btn-outline-success">
              <i class="fas fa-save me-1" aria-hidden="true"></i> Update Keranjang
            </button>
          </div>
        </div>
      </form>
    </div>
    <?php if ($detail === []): ?>
      <div class="alert alert-info" role="status">
        Keranjang masih kosong. Pilih kendaraan dari katalog untuk memulai transaksi.
      </div>
    <?php else: ?>
      <div class="table-responsive mb-4">
        <table class="table table-bordered table-hover align-middle bg-white">
          <thead class="table-light">
            <tr>
              <th scope="col">Kendaraan</th>
              <th scope="col">Nomor Polisi</th>
              <th scope="col" class="text-end">Harga per Hari</th>
              <th scope="col" class="text-center">Durasi</th>
              <th scope="col" class="text-end">Subtotal</th>
              <th scope="col" class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($detail as $item): ?>
              <?php
              $harga = (int) ($item['hrg_sewa'] ?? 0);
              $hari = (int) ($item['n_hari'] ?? 0);
              $subtotal = $harga * $hari;
              $foto = trim((string) ($item['ft_depan'] ?? ''));
              ?>
              <tr>
                <td>
                  <div class="d-flex align-items-center gap-3">
                    <?php if ($foto !== ''): ?>
                      <img src="<?= $e(base_url('uploads/mobil/') . rawurlencode($foto)) ?>"
                        alt="<?= $e($item['nm_mobil'] ?? 'Foto kendaraan') ?>"
                        style="width: 88px; height: 62px; object-fit: cover;" loading="lazy">
                    <?php else: ?>
                      <div class="bg-light border d-flex align-items-center justify-content-center"
                        style="width: 88px; height: 62px;">
                        <i class="fas fa-car text-muted" aria-hidden="true"></i>
                      </div>
                    <?php endif; ?>
                    <span class="fw-semibold"><?= $e($item['nm_mobil'] ?? '') ?></span>
                  </div>
                </td>
                <td><?= $e($item['no_plat'] ?? '') ?></td>
                <td class="text-end text-nowrap"><?= $e($formatRupiah($harga)) ?></td>
                <td class="text-center"><?= $e($hari) ?> hari</td>
                <td class="text-end text-nowrap fw-semibold"><?= $e($formatRupiah($subtotal)) ?></td>
                <td class="text-center">
                  <form method="post" action="<?= $e(site_url('transaksi/hapus')) ?>"
                    onsubmit="return confirm('Hapus kendaraan ini dari keranjang?');">
                    <input type="hidden" name="kode_sewa" value="<?= $e($keranjang['kode_sewa'] ?? '') ?>">
                    <input type="hidden" name="no_plat" value="<?= $e($item['no_plat'] ?? '') ?>">
                    <button type="submit" class="btn btn-sm btn-outline-danger">
                      <i class="fas fa-trash-alt me-1" aria-hidden="true"></i> Hapus
                    </button>
                  </form>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <th colspan="4" class="text-end">Total Sewa</th>
              <th class="text-end text-nowrap"><?= $e($formatRupiah($keranjang['total'] ?? 0)) ?></th>
              <th></th>
            </tr>
          </tfoot>
        </table>
      </div>

      <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <p class="mb-0 small">
          Checkout mengajukan transaksi kepada admin. Status kendaraan tidak berubah menjadi disewa sampai transaksi
          disetujui admin.
        </p>
        <form method="get" action="<?= $e(site_url('transaksi/checkout')) ?>">
          <input type="hidden" name="kode_sewa" value="<?= $e($keranjang['kode_sewa'] ?? '') ?>">
          <button type="submit" class="btn btn-success btn-lg" <?= $detail === [] ? 'disabled' : '' ?>>
            <i class="fas fa-arrow-right me-1" aria-hidden="true"></i> Checkout / Lihat Konfirmasi
          </button>
        </form>
      </div>
    <?php endif; ?>
  </div>
</section>
<script>
  (() => {
    const tanggalMulai = document.getElementById('tgl_mulai');
    const tanggalSelesai = document.getElementById('tgl_selesai');
    const waktuAmbil = document.getElementById('wkt_ambil');
    const formPeriode = document.getElementById('form-periode-sewa');
    if (!tanggalMulai || !tanggalSelesai || !waktuAmbil || !formPeriode) return;
    const pad = (angka) => String(angka).padStart(2, '0');
    const formatTanggal = (tanggal) =>
      `${tanggal.getFullYear()}-${pad(tanggal.getMonth() + 1)}-${pad(tanggal.getDate())}`;
    const formatWaktu = (tanggal) =>
      `${pad(tanggal.getHours())}:${pad(tanggal.getMinutes())}`;
    function hitungBatasWaktu() {
      const sekarang = new Date();
      const batas = new Date(sekarang.getTime() + 2 * 60 * 60 * 1000);
      if (batas.getSeconds() > 0 || batas.getMilliseconds() > 0) {
        batas.setMinutes(batas.getMinutes() + 1);
      }
      batas.setSeconds(0, 0);
      const tanggalBatas = formatTanggal(batas);
      tanggalMulai.min = tanggalBatas;
      if (!tanggalMulai.value || tanggalMulai.value < tanggalBatas) {
        tanggalMulai.value = tanggalBatas;
      }
      const mulai = new Date(`${tanggalMulai.value}T00:00:00`);
      mulai.setDate(mulai.getDate() + 1);
      tanggalSelesai.min = formatTanggal(mulai);
      if (!tanggalSelesai.value || tanggalSelesai.value < tanggalSelesai.min) {
        tanggalSelesai.value = tanggalSelesai.min;
      }
      const waktuSebelumnya = waktuAmbil.value || waktuAmbil.dataset.currentValue || '';
      const batasWaktu = tanggalMulai.value === tanggalBatas ?
        formatWaktu(batas) :
        '00:00';
      waktuAmbil.replaceChildren();
      let waktuTerpilihTersedia = false;
      for (let menit = 0; menit < 24 * 60; menit++) {
        const jam = Math.floor(menit / 60);
        const sisaMenit = menit % 60;
        const nilai = `${pad(jam)}:${pad(sisaMenit)}`;
        if (nilai < batasWaktu) {
          continue;
        }
        const opsi = document.createElement('option');
        opsi.value = nilai;
        opsi.textContent = nilai;
        if (nilai === waktuSebelumnya) {
          opsi.selected = true;
          waktuTerpilihTersedia = true;
        }
        waktuAmbil.appendChild(opsi);
      }
      if (!waktuTerpilihTersedia && waktuAmbil.options.length > 0) {
        waktuAmbil.selectedIndex = 0;
      }
      const label = document.getElementById('batas-waktu-mulai');
      if (label) {
        label.textContent = `Waktu paling awal: ${tanggalMulai.value} pukul ${batasWaktu} WIB.`;
      }
    }
    tanggalMulai.addEventListener('change', hitungBatasWaktu);
    tanggalSelesai.addEventListener('change', () => {
      if (tanggalSelesai.value < tanggalSelesai.min) {
        tanggalSelesai.value = tanggalSelesai.min;
      }
    });
    formPeriode.addEventListener('submit', (event) => {
      hitungBatasWaktu();
      if (tanggalSelesai.value <= tanggalMulai.value) {
        event.preventDefault();
        alert('Tanggal selesai harus setelah tanggal mulai.');
        tanggalSelesai.focus();
        return;
      }
      if (!waktuAmbil.value) {
        event.preventDefault();
        alert('Pilih waktu pengambilan yang valid.');
        waktuAmbil.focus();
        return;
      }
      if (tanggalMulai.value === tanggalMulai.min) {
        const batas = new Date(Date.now() + 2 * 60 * 60 * 1000);
        if (batas.getSeconds() > 0 || batas.getMilliseconds() > 0) {
          batas.setMinutes(batas.getMinutes() + 1);
        }
        batas.setSeconds(0, 0);
        if (waktuAmbil.value < formatWaktu(batas)) {
          event.preventDefault();
          alert('Waktu pengambilan harus minimal dua jam dari sekarang.');
          waktuAmbil.focus();
        }
      }
    });
    hitungBatasWaktu();
  })();
</script>
<script defer src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  window.addEventListener('DOMContentLoaded', () => {
    if (!window.jQuery || !window.jQuery.fn || !window.jQuery.fn.select2) {
      console.error('Select2 belum tersedia. Periksa koneksi internet atau pemuatan jQuery.');
      return;
    }
    const $waktu = window.jQuery('#wkt_ambil');
    $waktu.select2({
      width: '100%',
      placeholder: 'Pilih atau ketik waktu (HH:mm)',
      minimumResultsForSearch: 0,
      language: {
        noResults: () => 'Waktu tidak ditemukan',
        searching: () => 'Mencari waktu...',
        searchPlaceholder: 'Ketik waktu, misalnya 18:45'
      }
    });
    const waktuAmbil = document.getElementById('wkt_ambil');
    if (waktuAmbil) {
      const observer = new MutationObserver(() => $waktu.trigger('change.select2'));
      observer.observe(waktuAmbil, {
        childList: true
      });
    }
  });
</script>
<?php require APPPATH . 'views/katalog/footer.php'; ?>