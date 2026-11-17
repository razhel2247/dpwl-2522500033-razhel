<?php
// P4 BARU - application/helpers/flash_helper.php
function set_flash_message(string $type, string $message): void
{
  $_SESSION['_flash'] = [
    'type' => $type,
    'message' => $message,
  ];
}
function get_flash_message(): ?array
{
  $flash = $_SESSION['_flash'] ?? null;
  unset($_SESSION['_flash']);
  return is_array($flash) ? $flash : null;
}