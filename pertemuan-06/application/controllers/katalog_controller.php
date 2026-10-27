<?php
require_once APPPATH . 'models/Mobil_model.php';
class Katalog_controller extends Controller
{
  private Mobil_model $mobilModel;
  public function __construct()
  {
    $this->mobilModel = new Mobil_model();
  }
  public function index(): void
  {
    $data = [
      'daftarMobil' => $this->mobilModel->getKatalog()
    ];
    $this->view('katalog/index', $data);
  }
  public function detail(string $noPlat): void
  {
    $mobil = $this->mobilModel->findByNoPlat($noPlat);
    if ($mobil === null) {
      http_response_code(404);
      exit('Kendaraan tidak ditemukan.');
    }
    $this->view('katalog/detail', [
      'mobil' => $mobil
    ]);
  }
}