<?php
$isPenyewaLogin = !empty($_SESSION['penyewa_user_plg']);
$flashPenyewaNav = $_SESSION['flash_penyewa_nav'] ?? null;
unset($_SESSION['flash_penyewa_nav']);
$e = static function ($value): string {
  return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};
?>
<!-- Start Top Nav -->
<nav class="navbar navbar-expand-lg bg-dark navbar-light d-none d-lg-block" id="templatemo_nav_top">
  <div class="container text-light">
    <div class="w-100 d-flex justify-content-between">
      <div>
        <i class="fa fa-envelope mx-2"></i>
        <a class="navbar-sm-brand text-light text-decoration-none" href="mailto:info@company.com">info@company.com</a>
        <i class="fa fa-phone mx-2"></i>
        <a class="navbar-sm-brand text-light text-decoration-none" href="tel:010-020-0340">010-020-
          0340</a>
      </div>
      <div>
        <a class="text-light" href="https://fb.com/templatemo" target="_blank" rel="sponsored"><i
            class="fab fa-facebook-f fa-sm fa-fw me-2"></i></a>

        <a class="text-light" href="https://www.instagram.com/" target="_blank"><i class="fab fa-
instagram fa-sm fa-fw me-2"></i></a>

        <a class="text-light" href="https://twitter.com/" target="_blank"><i class="fab fa-twitter
fa-sm fa-fw me-2"></i></a>

        <a class="text-light" href="https://www.linkedin.com/" target="_blank"><i class="fab fa-
linkedin fa-sm fa-fw"></i></a>

      </div>
    </div>
  </div>
</nav>
<!-- Close Top Nav -->
<!-- Header -->
<nav class="navbar navbar-expand-lg navbar-light shadow">
  <div class="container d-flex justify-content-between align-items-center">
    <a class="navbar-brand text-success logo h1 align-self-center"
      href="<?= htmlspecialchars(site_url('katalog'), ENT_QUOTES, 'UTF-8') ?>">
      Rental
    </a>

    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-
      target="#templatemo_main_nav" aria-controls="navbarSupportedContent" aria-expanded="false" aria-
      label="Toggle navigation">

      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="align-self-center collapse navbar-collapse flex-fill d-lg-flex justify-content-lg-
between" id="templatemo_main_nav">

      <div class="flex-fill">
        <ul class="nav navbar-nav d-flex justify-content-between mx-lg-auto">
          <li class="nav-item">
            <a class="nav-link" href="<?= $e(site_url('katalog')) ?>">
              Beranda
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link" href="<?= $e(site_url('katalog')) ?>#katalog">
              Katalog Kendaraan
            </a>
          </li>
          <?php if (!$isPenyewaLogin): ?>
            <li class="nav-item">
              <a class="nav-link" href="<?= $e(site_url('auth/register')) ?>">
                Registrasi
              </a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="<?= $e(site_url('auth/login-penyewa')) ?>">
                Login Penyewa
              </a>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <form action="<?= $e(site_url('auth/logout-penyewa')) ?>" method="post" class="d-inline">
                <button type="submit" class="nav-link border-0 bg-transparent">
                  Logout
                </button>
              </form>
            </li>
          <?php endif; ?>
        </ul>
      </div>
      <div class="navbar align-self-center d-flex">
        <div class="d-lg-none flex-sm-fill mt-3 mb-4 col-7 col-sm-auto pr-3">
          <div class="input-group">
            <input type="text" class="form-control" id="inputMobileSearch" placeholder="Search ...">
            <div class="input-group-text">
              <i class="fa fa-fw fa-search"></i>
            </div>
          </div>
        </div>

        <a class="nav-icon d-none d-lg-inline" href="#" data-bs-toggle="modal" data-bs- target="#templatemo_search">

          <i class="fa fa-fw fa-search text-dark mr-2"></i>
        </a>
        <?php if ($isPenyewaLogin): ?>
          <a class="nav-icon position-relative text-decoration-none" href="#">
            <i class="fa fa-fw fa-cart-arrow-down text-dark mr-1"></i>

            <span class="position-absolute top-0 left-100 translate-middle badge rounded-pill bg-
light text-dark">7</span>

          </a>
          <!-- Ikon Profil Penyewa -->
          <a class="nav-icon text-decoration-none" href="<?= $e(site_url('penyewa/profil')) ?>" title="Profil Saya"
            aria-label="Profil Saya">
            <i class="fa fa-fw fa-user text-dark mr-3"></i>
          </a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</nav>
<!-- Close Header -->
<?php if ($flashPenyewaNav !== null): ?>
  <div class="container-fluid px-0">
    <div class="container py-3">
      <div class="alert alert-success alert-dismissible fade show mb-0" role="alert">
        <?= $e($flashPenyewaNav) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>
      </div>
    </div>
  </div>
<?php endif; ?>
<!-- Modal -->

<div class="modal fade bg-white" id="templatemo_search" tabindex="-1" role="dialog" aria- labelledby="exampleModalLabel"
  aria-hidden="true">

  <div class="modal-dialog modal-lg" role="document">
    <div class="w-100 pt-1 mb-5 text-right">
      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
    </div>
    <form action="" method="get" class="modal-content modal-body border-0 p-0">
      <div class="input-group mb-2">
        <input type="text" class="form-control" id="inputModalSearch" name="q" placeholder="Search
...">
        <button type="submit" class="input-group-text bg-success text-light">
          <i class="fa fa-fw fa-search text-white"></i>
        </button>
      </div>
    </form>
  </div>
</div>