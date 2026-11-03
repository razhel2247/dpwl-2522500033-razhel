<?php
require APPPATH . '/views/templates/header.php';
require APPPATH . '/views/templates/sidebar.php';
?>
<!-- Content Wrapper. Contains page content -->
<div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <section class="content-header">
        <h1>
            Page Header
            <small>Optional description</small>
        </h1>
        <ol class="breadcrumb">
            <li><a href="#"><i class="fa fa-dashboard"></i> Level</a></li>
            <li class="active">Here</li>
        </ol>
    </section>
    <!-- Main content -->
    <section class="content container-fluid">
        <!--------------------------
| Your Page Content Here |
-------------------------->
        <p>
            Login sebagai:
            <strong><?= htmlspecialchars(
                $username,
                ENT_QUOTES,
                'UTF-8'
            ) ?></strong>

        </p>
        <form method="post" action="<?= htmlspecialchars(
            site_url('auth/logout'),
            ENT_QUOTES,
            'UTF-8'
        ) ?>">
            <button type="submit" class="btn btn-danger">
                Logout
            </button>
        </form>
    </section>
    <!-- /.content -->
</div>
<!-- /.content-wrapper -->
<?php
require APPPATH . '/views/templates/footer.php';
?>