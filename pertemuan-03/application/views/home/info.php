<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Informasi P2</title>
</head>
<body>
<h1>Informasi P2</h1>
<p>
Parameter topik dari URL:
<strong><?= htmlspecialchars($topik, ENT_QUOTES, 'UTF-8') ?></strong>
</p>
<p>
<a href="<?= htmlspecialchars(
site_url('home/index'),
ENT_QUOTES,
'UTF-8'
) ?>">
Kembali ke halaman utama
</a>
</p>
</body>
</html>