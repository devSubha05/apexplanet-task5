<?php require_once __DIR__ . '/functions.php'; $me = current_user(); $base = $base ?? ''; ?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e(($pageTitle ?? 'Home') . ' · ' . SITE_NAME) ?></title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;700;800&family=Source+Sans+3:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= $base ?>assets/css/style.css">
</head>
<body>
<header class="site-header">
  <a class="brand" href="<?= $base ?>index.php"><?= e(SITE_NAME) ?></a>
  <button class="nav-toggle" aria-label="Menu" aria-expanded="false" aria-controls="nav">Menu</button>
  <nav id="nav">
    <a href="<?= $base ?>index.php">Courses</a>
    <?php if ($me): ?>
      <a href="<?= $base ?>dashboard.php">My learning</a>
      <?php if ($me['role'] === 'admin'): ?><a href="<?= $base ?>admin/index.php">Admin</a><?php endif; ?>
      <a class="nav-user" href="<?= $base ?>profile.php"><img src="<?= $base . e(avatar_url($me)) ?>" alt="" width="28" height="28"><?= e($me['name']) ?></a>
      <a href="<?= $base ?>logout.php">Log out</a>
    <?php else: ?>
      <a href="<?= $base ?>login.php">Log in</a>
      <a class="btn btn-small" href="<?= $base ?>register.php">Create account</a>
    <?php endif; ?>
  </nav>
</header>
<main class="wrap">
<?php foreach (flashes() as [$t, $m]): ?><div class="flash flash-<?= e($t) ?>" role="status"><?= e($m) ?></div><?php endforeach; ?>
