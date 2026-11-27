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
<script>
if ('scrollRestoration' in history) {
  history.scrollRestoration = 'manual';
}
if (window.location.hash) {
  history.replaceState(null, '', window.location.pathname + window.location.search);
}
</script>
</head>
<body>
<header class="site-header">
  <a class="brand" href="<?= $base ?>index.php"><?= e(SITE_NAME) ?></a>

  <form class="header-search-form" action="<?= $base ?>courses.php" method="get" role="search">
    <div class="header-search-inner">
      <span class="header-search-icon" aria-hidden="true">🔍</span>
      <input type="search" name="q" class="header-search-input" id="headerSearchInput" placeholder="Search for any course..." value="<?= e($_GET['q'] ?? '') ?>" aria-label="Search courses" autocomplete="off">
      <button type="submit" class="header-search-btn" aria-label="Submit search">Search</button>
    </div>
  </form>

  <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false" aria-controls="nav">
    <span class="hamburger-box" aria-hidden="true">
      <span class="nav-toggle-bar"></span>
      <span class="nav-toggle-bar"></span>
      <span class="nav-toggle-bar"></span>
    </span>
  </button>

  <div class="nav-backdrop" id="navBackdrop"></div>

  <nav id="nav" class="nav-drawer">
    <div class="nav-drawer-header">
      <span class="nav-drawer-title"><?= e(SITE_NAME) ?></span>
      <button type="button" class="nav-close-btn" id="navClose" aria-label="Close menu">&times;</button>
    </div>

    <?php if ($me): ?>
      <a href="<?= $base ?>dashboard.php" class="nav-drawer-highlight">
        <span>⚡ Continue Learning</span>
        <span class="drawer-arrow">→</span>
      </a>
    <?php else: ?>
      <a href="<?= $base ?>courses.php" class="nav-drawer-highlight">
        <span>✦ Explore All Courses</span>
        <span class="drawer-arrow">→</span>
      </a>
    <?php endif; ?>

    <div class="nav-drawer-links">
      <a href="<?= $base ?>courses.php" class="drawer-link <?= (basename($_SERVER['SCRIPT_NAME'] ?? '') === 'courses.php') ? 'active-nav-link' : '' ?>">
        <span class="nav-icon">📚</span> Courses
      </a>
      <?php if ($me): ?>
        <a href="<?= $base ?>dashboard.php" class="drawer-link">
          <span class="nav-icon">📊</span> My learning
        </a>
        <?php if ($me['role'] === 'instructor' || $me['role'] === 'admin'): ?>
          <a href="<?= $base ?>instructor/index.php" class="drawer-link" style="color:var(--gold);font-weight:700;">
            <span class="nav-icon">👨‍🏫</span> Teacher Panel
          </a>
        <?php endif; ?>
        <?php if ($me['role'] === 'admin'): ?>
          <a href="<?= $base ?>admin/index.php" class="drawer-link">
            <span class="nav-icon">⚙️</span> Admin
          </a>
        <?php endif; ?>
        <a class="drawer-link nav-user" href="<?= $base ?>profile.php">
          <img src="<?= $base . e(avatar_url($me)) ?>" alt="" width="28" height="28"> <?= e($me['name']) ?>
        </a>
        <a class="drawer-link nav-logout" href="<?= $base ?>logout.php" style="color:#ff5c5c;font-weight:700;">
          <span class="nav-icon">🚪</span> Log Out
        </a>
      <?php else: ?>
        <a href="<?= $base ?>login.php" class="drawer-link">
          <span class="nav-icon">🔑</span> Log in
        </a>
        <a class="drawer-link nav-btn-register btn btn-small" href="<?= $base ?>register.php">
          <span class="nav-icon">✨</span> Create account
        </a>
      <?php endif; ?>
    </div>
  </nav>
</header>
<main class="wrap">
<?php foreach (flashes() as [$t, $m]): ?><div class="flash flash-<?= e($t) ?>" role="status"><?= e($m) ?></div><?php endforeach; ?>
