<?php
function is_admin_logged_in(): bool
{
return isset($_SESSION['admin_username'])
&& ($_SESSION['admin_status'] ?? '') === 'aktif';
}
function require_admin_login(): void
{
if (!is_admin_logged_in()) {
header('Location: ' . site_url('auth/login'));
exit;
}
}