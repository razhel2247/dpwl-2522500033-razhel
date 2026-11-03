<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title><?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?></title>
<link
rel="stylesheet"
href="<?= htmlspecialchars(
base_url('assets/css/app.css'),
ENT_QUOTES,
'UTF-8'
) ?>">
</head>
<body>
<h1><?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?></h1>
<p><?= htmlspecialchars($pesan, ENT_QUOTES, 'UTF-8') ?></p>
<p>
<a href="<?= htmlspecialchars(
site_url('info/routing'),
ENT_QUOTES,
'UTF-8'
) ?>">
Uji custom route dan parameter
</a>
</p>
</body>
</html>