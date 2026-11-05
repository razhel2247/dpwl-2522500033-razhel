<?php
class Controller
{
protected function view(string $view, array $data = []): void
{
$file = APPPATH . 'views/' . $view . '.php';
if (!is_file($file)) {
http_response_code(500);
exit('View tidak ditemukan.');
}
extract($data, EXTR_SKIP);
require $file;
}
}