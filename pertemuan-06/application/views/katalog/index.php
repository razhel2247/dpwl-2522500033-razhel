<?php require APPPATH . 'views/katalog/header.php'; ?>
<?php require APPPATH . 'views/katalog/navbar.php'; ?>
<!-- Start Banner Hero -->
<div id="template-mo-zay-hero-carousel" class="carousel slide" data-bs-ride="carousel">
  <ol class="carousel-indicators">
    <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="0" class="active"></li>
    <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="1"></li>
    <li data-bs-target="#template-mo-zay-hero-carousel" data-bs-slide-to="2"></li>
  </ol>
  <div class="carousel-inner">
    <div class="carousel-item active">
      <div class="container">
        <div class="row p-5">
          <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
            <img class="img-fluid" src="<?= htmlspecialchars(
              base_url('assets/zay/'),
              ENT_QUOTES,

              'UTF-8'
            ) ?>img/banner_img_01.jpg" alt="">
          </div>
          <div class="col-lg-6 mb-0 d-flex align-items-center">
            <div class="text-align-left align-self-center">
              <h1 class="h1 text-success"><b>Rental</b> Kendaraan</h1>
              <h3 class="h2">Temukan Kendaraan untuk Perjalanan Anda</h3>
              <p>
                Pilih kendaraan sesuai kebutuhan perjalanan Anda.
                Jelajahi katalog untuk melihat informasi dan spesifikasi kendaraan
                yang tersedia.
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="container">
        <div class="row p-5">
          <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
            <img class="img-fluid" src="<?= htmlspecialchars(
              base_url('assets/zay/'),
              ENT_QUOTES,

              'UTF-8'
            ) ?>img/banner_img_02.jpg" alt="">
          </div>
          <div class="col-lg-6 mb-0 d-flex align-items-center">
            <div class="text-align-left">
              <h1 class="h1">Proident occaecat</h1>
              <h3 class="h2">Aliquip ex ea commodo consequat</h3>
              <p>
                You are permitted to use this Zay CSS template for your commercial websites.
                You are <strong>not permitted</strong> to re-distribute the template ZIP file in any

                kind of template collection websites.

              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div class="carousel-item">
      <div class="container">
        <div class="row p-5">
          <div class="mx-auto col-md-8 col-lg-6 order-lg-last">
            <img class="img-fluid" src="<?= htmlspecialchars(
              base_url('assets/zay/'),
              ENT_QUOTES,

              'UTF-8'
            ) ?>img/banner_img_03.jpg" alt="">
          </div>
          <div class="col-lg-6 mb-0 d-flex align-items-center">
            <div class="text-align-left">
              <h1 class="h1">Repr in voluptate</h1>
              <h3 class="h2">Ullamco laboris nisi ut </h3>
              <p>
                We bring you 100% free CSS templates for your websites.
                If you wish to support TemplateMo, please make a small contribution via PayPal or

                tell your friends about our website. Thank you.

              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <a class="carousel-control-prev text-decoration-none w-auto ps-3" href="#template-mo-zay-hero-
carousel" role="button" data-bs-slide="prev">

    <i class="fas fa-chevron-left"></i>
  </a>

  <a class="carousel-control-next text-decoration-none w-auto pe-3" href="#template-mo-zay-hero-
carousel" role="button" data-bs-slide="next">

    <i class="fas fa-chevron-right"></i>
  </a>
</div>
<!-- End Banner Hero -->
<!-- Start Kategori Kendaraan -->
<section class="container py-5">
  <div class="row text-center pt-3">
    <div class="col-lg-6 m-auto">
      <h1 class="h1">Kategori Kendaraan</h1>
      <p>
        Pilih jenis kendaraan sesuai kebutuhan perjalanan Anda.
        Informasi kendaraan lebih lengkap tersedia pada katalog.
      </p>
    </div>
  </div>
  <div class="row">
    <div class="col-12 col-md-4 p-5 mt-3">
      <a href="#katalog">
        <img src="<?= htmlspecialchars(
          base_url('assets/zay/img/category_img_01.jpg'),
          ENT_QUOTES,
          'UTF-8'
        ) ?>" class="rounded-circle img-fluid border" alt="Kategori SUV">
      </a>
      <h5 class="text-center mt-3 mb-3">SUV</h5>
      <p class="text-center">
        <a href="#katalog" class="btn btn-success">Lihat Kendaraan</a>
      </p>
    </div>
    <div class="col-12 col-md-4 p-5 mt-3">
      <a href="#katalog">
        <img src="<?= htmlspecialchars(
          base_url('assets/zay/img/category_img_02.jpg'),
          ENT_QUOTES,
          'UTF-8'
        ) ?>" class="rounded-circle img-fluid border" alt="Kategori MPV">
      </a>
      <h5 class="text-center mt-3 mb-3">MPV</h5>
      <p class="text-center">
        <a href="#katalog" class="btn btn-success">Lihat Kendaraan</a>
      </p>
    </div>
    <div class="col-12 col-md-4 p-5 mt-3">
      <a href="#katalog">
        <img src="<?= htmlspecialchars(
          base_url('assets/zay/img/category_img_03.jpg'),
          ENT_QUOTES,
          'UTF-8'
        ) ?>" class="rounded-circle img-fluid border" alt="Kategori City Car">
      </a>
      <h5 class="text-center mt-3 mb-3">City Car</h5>
      <p class="text-center">
        <a href="#katalog" class="btn btn-success">Lihat Kendaraan</a>
      </p>
    </div>
  </div>
</section>
<!-- End Kategori Kendaraan -->
<!-- Start Katalog Kendaraan -->
<section class="bg-light" id="katalog">
  <div class="container py-5">
    <div class="row text-center py-3">
      <div class="col-lg-6 m-auto">
        <h1 class="h1">Katalog Kendaraan</h1>
        <p>
          Lihat informasi kendaraan, spesifikasi, dan harga sewa
          sebelum menentukan pilihan.
        </p>
      </div>
    </div>
    <div class="row">
      <?php if (empty($daftarMobil)): ?>
        <div class="col-12">
          <div class="alert alert-info" role="status">
            Belum ada data kendaraan yang dapat ditampilkan.
          </div>
        </div>
      <?php else: ?>
        <?php foreach ($daftarMobil as $mobil): ?>
          <div class="col-12 col-md-6 col-lg-4 mb-4">
            <div class="card h-100">
              <div class="katalog-foto">
                <?php if (!empty($mobil['ft_depan'])): ?>
                  <a href="<?= htmlspecialchars(

                    site_url('katalog/detail/' . rawurlencode($mobil['no_plat'])),
                    ENT_QUOTES,
                    'UTF-8'
                  ) ?>">
                    <img src="<?= htmlspecialchars(
                      base_url('uploads/mobil/' . rawurlencode($mobil['ft_depan'])),
                      ENT_QUOTES,
                      'UTF-8'
                    ) ?>" class="card-img-top" alt="<?= htmlspecialchars(
                       $mobil['nm_mobil'],
                       ENT_QUOTES,
                       'UTF-8'
                     ) ?>">

                  </a>
                <?php else: ?>
                  <div class="katalog-foto-kosong">
                    <span>Foto kendaraan belum tersedia</span>
                  </div>
                <?php endif; ?>
              </div>
              <div class="card-body">
                <h2 class="text-dark">
                  <a class="text-decoration-none text-dark" href="<?= htmlspecialchars(
                    site_url('katalog/detail/' . rawurlencode($mobil['no_plat'])),
                    ENT_QUOTES,
                    'UTF-8'
                  ) ?>">
                    <?= htmlspecialchars($mobil['nm_mobil'], ENT_QUOTES, 'UTF-8') ?>
                  </a>
                </h2>
                <p class="mb-2">
                  <strong>Jenis:</strong>
                  <?= htmlspecialchars(
                    $mobil['jns_mobil'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                  ) ?>
                </p>
                <p class="mb-2">
                  <strong>Tahun:</strong>
                  <?= htmlspecialchars(
                    (string) $mobil['thn_buat'],
                    ENT_QUOTES,
                    'UTF-8'
                  ) ?>
                </p>
                <p class="mb-2">
                  <strong>Transmisi:</strong>
                  <?= htmlspecialchars(
                    $mobil['transmisi'] ?? '-',
                    ENT_QUOTES,
                    'UTF-8'
                  ) ?>
                </p>
                <p class="mb-2">
                  <strong>Kapasitas:</strong>
                  <?= (int) $mobil['nkursi'] ?> kursi,
                  <?= (int) $mobil['nbagasi'] ?> bagasi
                </p>
                <p class="h5 text-success mt-3">
                  Rp <?= number_format(
                    (int) $mobil['hrg_hari'],
                    0,
                    ',',
                    '.'
                  ) ?> / hari
                </p>
                <p class="mb-3">
                  <strong>Status:</strong>
                  <?php if ($mobil['status_unit'] === 'tersedia'): ?>
                    <span class="badge bg-success">Tersedia</span>
                  <?php elseif ($mobil['status_unit'] === 'disewa'): ?>
                    <span class="badge bg-warning text-dark">Sedang disewa</span>
                  <?php else: ?>
                    <span class="badge bg-secondary">Tidak tersedia</span>
                  <?php endif; ?>
                </p>
                <?php if ($mobil['status_unit'] === 'tersedia'): ?>
                  <button type="button" class="btn btn-success w-100" disabled>
                    Tambah ke Keranjang
                  </button>
                <?php else: ?>
                  <button type="button" class="btn btn-secondary w-100" disabled>
                    Tidak Dapat Disewa
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </div>
</section>
<!-- End Katalog Kendaraan -->
<?php require APPPATH . 'views/katalog/footer.php'; ?>