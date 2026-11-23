<?php
$detail = $detailPelunasan ?? [];
$riwayat = $detail['riwayat_pembayaran'] ?? [];
$kendaraan = $detail['daftar_kendaraan'] ?? [];
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$totalTransaksi = (int) ($detail['total_transaksi'] ?? 0);
$totalTerbayar = (int) ($detail['total_terbayar'] ?? 0);
$sisaKewajiban = (int) ($detail['sisa_kewajiban'] ?? 0);
$kodeSewa = (string) ($detail['kode_sewa'] ?? '');
$tanggalCetak = date('d-m-Y H:i:s');
?>
<!DOCTYPE html>
<html lang="id">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dokumen Transaksi <?= $e($kodeSewa) ?></title>
  <style>
    * {
      box-sizing: border-box;
    }

    body {
      margin: 0;
      padding: 24px;
      color: #222;
      background: #f2f2f2;
      font: 13px/1.5 Arial, Helvetica, sans-serif;
    }

    .document {
      width: 100%;
      max-width: 900px;
      margin: 0 auto;
      padding: 34px;
      background: #fff;
      border: 1px solid #ddd;
    }

    .document-header {
      padding-bottom: 18px;
      margin-bottom: 22px;
      border-bottom: 2px solid #333;
      text-align: center;
    }

    .document-header h1 {
      margin: 0 0 5px;
      font-size: 22px;
    }

    .document-header p {
      margin: 0;
      color: #555;
    }

    h2 {
      margin: 24px 0 10px;
      font-size: 15px;
    }

    table {
      width: 100%;
      border-collapse: collapse;
    }

    th,
    td {
      padding: 7px 8px;
      border: 1px solid #bbb;
      text-align: left;
      vertical-align: top;
    }

    th {
      background: #eee;
    }

    .meta td:first-child {
      width: 190px;
      font-weight: bold;
    }

    .numeric {
      text-align: right;
      white-space: nowrap;
    }

    .summary {
      width: 55%;
      margin: 14px 0 0 auto;
    }

    .summary th {
      width: 58%;
    }

    .summary .total td,
    .summary .total th {
      font-weight: bold;
      background: #f1f1f1;
    }

    .document-footer {
      display: flex;
      justify-content: space-between;
      gap: 20px;
      margin-top: 34px;
      color: #555;
      font-size: 11px;
    }

    .actions {
      max-width: 900px;
      margin: 0 auto 14px;
      text-align: right;
    }

    .button {
      display: inline-block;
      padding: 9px 14px;
      color: #fff;
      background: #337ab7;
      border: 0;
      border-radius: 3px;
      cursor: pointer;
      font-size: 13px;
    }

    .muted {
      color: #666;
    }

    @media print {
      @page {
        size: A4;
        margin: 14mm;
      }

      body {
        padding: 0;
        background: #fff;
      }

      .document {
        max-width: none;
        padding: 0;
        border: 0;
      }

      .actions {
        display: none;
      }

      th {
        background: #eee !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
      }

      tr,
      table {
        page-break-inside: avoid;
      }
    }

    @media (max-width: 640px) {
      body {
        padding: 8px;
      }

      .document {
        padding: 14px;
      }

      .summary {
        width: 100%;
      }

      .document-footer {
        display: block;
      }
    }
  </style>
</head>

<body>
  <div class="actions">
    <button class="button" type="button" onclick="window.print()">Cetak / Simpan sebagai PDF</button>
  </div>
  <main class="document">
    <header class="document-header">
      <h1>DOKUMEN TRANSAKSI SEWA KENDARAAN</h1>
      <p>Ringkasan transaksi dan riwayat pembayaran</p>
    </header>
    <h2>A. Informasi Transaksi</h2>
    <table class="meta">
      <tbody>
        <tr>
          <td>Kode transaksi</td>
          <td><?= $e($kodeSewa) ?></td>
        </tr>
        <tr>
          <td>Status transaksi</td>
          <td><?= $e($detail['status_sewa'] ?? '-') ?></td>
        </tr>
        <tr>
          <td>Tanggal mulai sewa</td>
          <td><?= $e($detail['tgl_mulai'] ?? '-') ?></td>
        </tr>
        <tr>
          <td>Tanggal selesai sewa</td>
          <td><?= $e($detail['tgl_selesai'] ?? '-') ?></td>
        </tr>
        <tr>
          <td>Waktu pengambilan</td>
          <td><?= $e($detail['wkt_ambil'] ?? '-') ?></td>
        </tr>
      </tbody>
    </table>
    <h2>B. Identitas Penyewa</h2>
    <table class="meta">
      <tbody>
        <tr>
          <td>Nama lengkap</td>
          <td><?= $e($detail['nm_lngkp'] ?? '-') ?></td>
        </tr>
        <tr>
          <td>Username</td>
          <td><?= $e($detail['user_plg'] ?? '-') ?></td>
        </tr>
        <tr>
          <td>NIK</td>
          <td><?= $e($detail['nik'] ?? '-') ?></td>
        </tr>
        <tr>
          <td>Nomor telepon</td>
          <td><?= $e($detail['no_hp'] ?? '-') ?></td>
        </tr>
        <tr>
          <td>Alamat</td>
          <td><?= nl2br($e($detail['almt_lngkp'] ?? '-')) ?></td>
        </tr>
      </tbody>
    </table>
    <h2>C. Rincian Kendaraan</h2>
    <table>
      <thead>
        <tr>
          <th style="width:34px">No.</th>
          <th>No. Polisi</th>
          <th>Nama kendaraan</th>
          <th class="numeric">Tarif snapshot/hari</th>
          <th class="numeric">Lama sewa</th>
          <th class="numeric">Subtotal</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$kendaraan): ?>
          <tr>
            <td colspan="6" class="muted">Rincian kendaraan tidak tersedia.</td>
          </tr>
        <?php else:
          foreach ($kendaraan as $i => $item): ?>
            <tr>
              <td><?= (int) $i + 1 ?></td>
              <td><?= $e($item['no_plat'] ?? '-') ?></td>
              <td><?= $e($item['nm_mobil'] ?? '-') ?></td>
              <td class="numeric">Rp
                <?= number_format((int) ($item['hrg_sewa'] ?? 0), 0, ',', '.')

                  ?>
              </td>

              <td class="numeric"><?= (int) ($item['n_hari'] ?? 0) ?> hari</td>
              <td class="numeric">Rp
                <?= number_format((int) ($item['subtotal'] ?? 0), 0, ',', '.')

                  ?>
              </td>
            </tr>
          <?php endforeach;
        endif; ?>
      </tbody>
    </table>
    <h2>D. Riwayat Pembayaran</h2>
    <table>
      <thead>
        <tr>
          <th style="width:34px">No.</th>
          <th>Kode pembayaran</th>
          <th>Tanggal pembayaran</th>
          <th>Jenis</th>
          <th>Metode</th>
          <th class="numeric">Nominal</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php if (!$riwayat): ?>
          <tr>
            <td colspan="7" class="muted">Belum ada catatan pembayaran.</td>
          </tr>
        <?php else:
          foreach ($riwayat as $i => $item): ?>
            <tr>
              <td><?= (int) $i + 1 ?></td>
              <td><?= $e($item['kode_bayar'] ?? '-') ?></td>
              <td><?= $e($item['tgl_wkt_byr'] ?? '-') ?></td>
              <td><?= $e($item['jns_byr'] ?? '-') ?></td>
              <td><?= $e($item['mtode_byr'] ?? '-') ?></td>
              <td class="numeric">Rp <?= number_format((int) ($item['nominal'] ?? 0), 0, ',', '.') ?></td>
              <td><?= $e($item['sttus_byr'] ?? '-') ?></td>
            </tr>
          <?php endforeach;
        endif; ?>
      </tbody>
    </table>
    <table class="summary">
      <tbody>
        <tr>
          <th>Total transaksi</th>
          <td class="numeric">Rp <?= number_format($totalTransaksi, 0, ',', '.') ?></td>
        </tr>
        <tr>
          <th>Total pembayaran valid</th>
          <td class="numeric">Rp <?= number_format($totalTerbayar, 0, ',', '.') ?></td>
        </tr>
        <tr class="total">
          <th>Sisa kewajiban</th>
          <td class="numeric">Rp <?= number_format($sisaKewajiban, 0, ',', '.') ?></td>
        </tr>
      </tbody>
    </table>
    <footer class="document-footer">
      <span>Dokumen dicetak dari sistem berdasarkan data transaksi yang tersimpan.</span>
      <span>Tanggal cetak: <?= $e($tanggalCetak) ?></span>
    </footer>
  </main>
</body>

</html>