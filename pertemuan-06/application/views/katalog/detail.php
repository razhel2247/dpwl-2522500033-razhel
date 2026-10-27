<?php
$namaMobil = (string) ($mobil['nm_mobil'] ?? 'Kendaraan');
$status = (string) ($mobil['status_unit'] ?? '');
$statusTersedia = $status === 'tersedia';
?>
<?php require APPPATH . 'views/katalog/header.php'; ?>
<?php require APPPATH . 'views/katalog/navbar.php'; ?>
<!-- Open Content -->
<section class="bg-light">
  <div class="container pb-5">
    <div class="row">
      <div class="col-lg-5 mt-5">
        <?php
        $fotoKendaraan = [];
        foreach (['ft_depan', 'ft_blkg', 'ft_interior'] as $fieldFoto) {
          $namaFile = trim((string) ($mobil[$fieldFoto] ?? ''));
          if ($namaFile !== '') {
            $fotoKendaraan[] = $namaFile;
          }
        }
        $fotoUtama = $fotoKendaraan[0] ?? null;
        ?>
        <div class="card mb-3">
          <?php if ($fotoUtama !== null): ?>
            <img class="card-img img-fluid" src="<?= htmlspecialchars(
              base_url('uploads/mobil/') . rawurlencode($fotoUtama),
              ENT_QUOTES,
              'UTF-8'
            ) ?>" alt="<?= htmlspecialchars($namaMobil, ENT_QUOTES, 'UTF-8') ?>" id="product-detail"
              style="width: 100%; height: 456px; object-fit: contain;">
          <?php else: ?>
            <div class="d-flex align-items-center justify-content-center text-muted" style="height: 456px;"
              id="product-detail-placeholder">
              Foto kendaraan belum tersedia.
            </div>
          <?php endif; ?>
        </div>
        <div class="row">
          <!-- Panah kiri -->
          <div class="col-1 align-self-center">
            <a href="#" id="foto-sebelumnya" role="button" aria-label="Foto sebelumnya">
              <i class="text-dark fas fa-chevron-left"></i>
            </a>
          </div>
          <!-- Tiga thumbnail -->
          <div class="col-10">
            <div class="row" id="galeri-thumbnail">
              <?php for ($i = 0; $i < 3; $i++): ?>
                <?php $namaFile = $fotoKendaraan[$i] ?? null; ?>
                <div class="col-4">
                  <?php if ($namaFile !== null): ?>
                    <a href="#" class="thumbnail-kendaraan d-block" data-index="<?= $i ?>"
                      aria-label="Tampilkan foto kendaraan <?= $i + 1 ?>">
                      <img class="card-img img-fluid" src="<?= htmlspecialchars(
                        base_url('uploads/mobil/') . rawurlencode($namaFile),
                        ENT_QUOTES,
                        'UTF-8'
                      ) ?>" alt="<?= htmlspecialchars(
                         $namaMobil . ' - foto ' . ($i + 1),
                         ENT_QUOTES,
                         'UTF-8'
                       ) ?>" style="height: 112px; object-fit: contain;">
                    </a>
                  <?php else: ?>
                    <div class="d-flex align-items-center justify-content-center text-muted text-
center" style="height: 112px; font-size: 12px;">
                      Foto tidak tersedia
                    </div>
                  <?php endif; ?>
                </div>
              <?php endfor; ?>
            </div>
          </div>
          <!-- Panah kanan -->
          <div class="col-1 align-self-center">
            <a href="#" id="foto-berikutnya" role="button" aria-label="Foto berikutnya">
              <i class="text-dark fas fa-chevron-right"></i>
            </a>
          </div>
        </div>
      </div>
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const foto = <?= json_encode(
            array_map(
              static fn(string $namaFile): string =>
                base_url('uploads/mobil/') . rawurlencode($namaFile),
              $fotoKendaraan
            ),
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT
          ) ?>;

          const gambarUtama = document.getElementById('product-detail');
          const thumbnail = document.querySelectorAll('.thumbnail-kendaraan');
          if (!gambarUtama || foto.length === 0) {
            return;
          }
          let indeksAktif = 0;
          function tampilkanFoto(indeks) {
            indeksAktif = (indeks + foto.length) % foto.length;
            gambarUtama.src = foto[indeksAktif];
            thumbnail.forEach(function (item) {
              item.classList.toggle(
                'border',
                Number(item.dataset.index) === indeksAktif
              );
              item.classList.toggle(
                'border-success',
                Number(item.dataset.index) === indeksAktif
              );
            });
          }
          thumbnail.forEach(function (item) {
            item.addEventListener('click', function (event) {
              event.preventDefault();
              tampilkanFoto(Number(item.dataset.index));
            });
          });
          document.getElementById('foto-sebelumnya')
            ?.addEventListener('click', function (event) {
              event.preventDefault();
              tampilkanFoto(indeksAktif - 1);
            });
          document.getElementById('foto-berikutnya')
            ?.addEventListener('click', function (event) {
              event.preventDefault();
              tampilkanFoto(indeksAktif + 1);
            });
          tampilkanFoto(0);
        });
      </script>
      <!-- col end -->
      <div class="col-lg-7 mt-5">
        <div class="card">
          <div class="card-body">
            <h1 class="h2">
              <?= htmlspecialchars($namaMobil, ENT_QUOTES, 'UTF-8') ?>
            </h1>
            <p class="h3 py-2 detail-harga text-success">
              Rp <?= number_format(
                (int) $mobil['hrg_hari'],
                0,
                ',',
                '.'
              ) ?> / hari
            </p>
            <ul class="list-unstyled">
              <li class="mb-2">
                <strong>Jenis:</strong>
                <?= htmlspecialchars(
                  (string) ($mobil['jns_mobil'] ?? '-'),
                  ENT_QUOTES,
                  'UTF-8'
                ) ?>
              </li>
              <li class="mb-2">
                <strong>Tahun:</strong>
                <?= htmlspecialchars(
                  (string) $mobil['thn_buat'],
                  ENT_QUOTES,
                  'UTF-8'
                ) ?>
              </li>
              <li class="mb-2">
                <strong>Transmisi:</strong>
                <?= htmlspecialchars(
                  (string) ($mobil['transmisi'] ?? '-'),
                  ENT_QUOTES,
                  'UTF-8'
                ) ?>
              </li>
              <li class="mb-2">
                <strong>Kapasitas:</strong>
                <?= (int) $mobil['nkursi'] ?> kursi,
                <?= (int) $mobil['nbagasi'] ?> bagasi
              </li>
              <li class="mb-2">
                <strong>Status:</strong>
                <?php if ($statusTersedia): ?>
                  <span class="badge bg-success">Tersedia</span>
                <?php else: ?>
                  <span class="badge bg-secondary">
                    <?= htmlspecialchars($status, ENT_QUOTES, 'UTF-8') === ''
                      ? 'Tidak tersedia'
                      : htmlspecialchars($status, ENT_QUOTES, 'UTF-8') ?>
                  </span>
                <?php endif; ?>
              </li>
            </ul>
            <h6 class="h6">Deskripsi Kendaraan</h6>
            <p>
              <?= nl2br(htmlspecialchars(
                (string) $mobil['cttn_unit'],
                ENT_QUOTES,
                'UTF-8'
              )) ?>
            </p>
            <form action="" method="GET">
              <input type="hidden" name="product-title" value="Activewear">
              <div class="row pb-3">
                <div class="col d-grid">
                  <button type="submit" class="btn btn-success btn-lg" name="submit" value="addtocard">Add To
                    Cart</button>

                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>
<!-- Close Content -->
<?php require APPPATH . 'views/katalog/footer.php'; ?>