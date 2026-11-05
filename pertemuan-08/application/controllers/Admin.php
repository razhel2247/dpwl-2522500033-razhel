<?php
class Admin extends Controller
{
  public function index(): void
  {
    require_once APPPATH . 'helpers/auth_helper.php';
    require_admin_login();
    $this->view('admin/index', [
      'username' => $_SESSION['admin_username']
    ]);
  }
}