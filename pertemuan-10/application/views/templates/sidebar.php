<?php
// Penentu menu aktif berdasarkan URL yang sedang dibuka.
$currentRoute = trim($_GET['route'] ?? ($_SERVER['PATH_INFO'] ?? ''), '/');
$isActive = static function (string $prefix) use ($currentRoute): string {
  if ($prefix === 'admin') {
    return $currentRoute === 'admin' ? 'active' : '';
  }
  return strpos($currentRoute, $prefix) === 0 ? 'active' : '';
};
?>
<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">
  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">
    <!-- Sidebar user panel (optional) -->
    <div class="user-panel">
      <div class="pull-left image">
        <img src="<?= htmlspecialchars($adminlte_url . 'dist/img/user2-160x160.jpg', ENT_QUOTES, 'UTF-8') ?>"
          class="img-circle" alt="User Image">
      </div>
      <div class="pull-left info">
        <p>
          Login sebagai:
          <strong><?= htmlspecialchars($username, ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
        <!-- Status -->
        <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
      </div>
    </div>
    <!-- search form (Optional) -->
    <form action="#" method="get" class="sidebar-form">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Search...">
        <span class="input-group-btn">
          <button type="submit" name="search" id="search-btn" class="btn btn-flat">
            <i class="fa fa-search"></i>
          </button>
        </span>
      </div>
    </form>
    <!-- /.search form -->
    <!-- Sidebar Menu -->
    <ul class="sidebar-menu" data-widget="tree">
      <li class="header">MENU ADMIN</li>
      <li class="<?= $isActive('admin') ?>">
        <a href="<?= htmlspecialchars(site_url('admin'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-dashboard"></i> <span>Dashboard</span>
        </a>
      </li>
      <li class="<?= $isActive('admin/madmin') ?>">
        <a href="<?= htmlspecialchars(site_url('admin/madmin'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-users"></i> <span>Manajemen Admin</span>
        </a>
      </li>
      <li class="<?= $isActive('admin/mobil') ?>">
        <a href="<?= htmlspecialchars(site_url('admin/mobil'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-car"></i> <span>Manajemen Kendaraan</span>
        </a>
      </li>
      <li class="<?= $isActive('admin/penyewa') ?>">
        <a href="<?= htmlspecialchars(site_url('admin/penyewa'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-user"></i> <span>Manajemen Penyewa</span>
        </a>
      </li>
      <li class="<?= $isActive('admin/pembayaran') ?>">
        <a href="<?= htmlspecialchars(site_url('admin/pembayaran'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-money"></i> <span>Verifikasi Pembayaran</span>
        </a>
      </li>
      <li>
        <a href="<?= htmlspecialchars(site_url('admin/pembayaran/pelunasan'), ENT_QUOTES, 'UTF-8') ?>">
          <i class="fa fa-credit-card"></i> <span>Pelunasan Transaksi</span>
        </a>
      </li>
    </ul>
    <!-- /.sidebar-menu -->
  </section>
  <!-- /.sidebar -->
</aside>