<?php
require_once __DIR__ . '/includes/functions.php';
$cats = db()->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$pageTitle = 'All Courses';
require __DIR__ . '/includes/header.php';
?>

<!-- ===== All Courses Page Header ===== -->
<div class="courses-page-header">
  <div class="courses-page-badge">📚 Complete Course Catalog</div>
  <h1 class="courses-page-title">Explore All Courses</h1>
  <p class="courses-page-subtitle">
    Browse our library of industry-recognized courses, professional certificates, and practical programming tracks.
  </p>
</div>

<!-- ===== Quick Category Filter Strip ===== -->
<section class="cat-strip" aria-label="Explore course categories" style="margin-top:0.5rem; margin-bottom:1.5rem;">
  <div class="cat-chips-list">
    <button type="button" class="cat-chip active" data-cat="">
      <span class="cat-chip-icon">✨</span> All Courses
    </button>
    <?php
    $catIcons = [
      'Web Development' => '💻',
      'Databases' => '👥',
      'Programming' => '&lt;&gt;',
      'Design' => '✏️',
      'Data Science' => '📊',
      'Mobile Development' => '📱',
      'DevOps' => '🧪',
      'Cybersecurity' => '🛡️'
    ];
    foreach ($cats as $c):
      $icon = $catIcons[$c['name']] ?? '📘';
    ?>
    <button type="button" class="cat-chip" data-cat="<?= e($c['name']) ?>">
      <span class="cat-chip-icon"><?= $icon ?></span> <?= e($c['name']) ?>
    </button>
    <?php endforeach; ?>
  </div>
</section>

<!-- ===== Filter & Search Controls ===== -->
<div class="courses-section-header">
  <h2 class="np-heading" style="margin-bottom:0;">Available Courses</h2>
  <span id="count" class="courses-count-text" aria-live="polite">Loading courses...</span>
</div>

<section class="filters" aria-label="Find a course">
  <input type="search" id="q" placeholder="🔍 Search courses by keyword, topic, or instructor..." aria-label="Search courses">
  <select id="cat" aria-label="Category">
    <option value="0">All categories</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <select id="level" aria-label="Level">
    <option value="">Any level</option>
    <option>Beginner</option>
    <option>Intermediate</option>
    <option>Advanced</option>
  </select>
</section>

<!-- ===== Courses Grid (All Courses Loaded) ===== -->
<section id="results" class="grid" data-expanded="true"></section>

<div class="toggle-container" id="toggleWrap" style="display:none;">
  <button type="button" id="seeMoreBtn" class="btn btn-toggle" aria-expanded="false">
    <span id="seeMoreText">See More</span>
    <span class="btn-icon" id="seeMoreIcon" aria-hidden="true">↓</span>
  </button>
</div>

<?php
$extraScripts = '<script src="assets/js/app.js"></script>';
require __DIR__ . '/includes/footer.php';
?>
