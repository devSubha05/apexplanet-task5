<?php
require_once __DIR__ . '/includes/functions.php';
$cats = db()->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$pageTitle = 'Courses'; require __DIR__ . '/includes/header.php'; ?>
<section class="hero">
  <span class="hero-badge">✦ Interactive E-Learning</span>
  <h1>Learn <span class="hero-highlight">one lesson</span> at a time, and see your progress add up.</h1>
  <p>Short courses on web development, databases and programming. Enroll, finish lessons, and track your percentage live.</p>
</section>
<section class="filters" aria-label="Find a course">
  <input type="search" id="q" placeholder="Search courses" aria-label="Search courses">
  <select id="cat" aria-label="Category">
    <option value="0">All categories</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <select id="level" aria-label="Level">
    <option value="">Any level</option><option>Beginner</option><option>Intermediate</option><option>Advanced</option>
  </select>
</section>
<p id="count" aria-live="polite"></p>
<section id="results" class="grid"></section>
<?php $extraScripts = '<script src="assets/js/app.js"></script>'; require __DIR__ . '/includes/footer.php'; ?>
