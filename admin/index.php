<?php
$base = '../'; require_once __DIR__ . '/../includes/functions.php'; require_admin();
$one = fn($sql) => (int)db()->query($sql)->fetchColumn();
$stats = ['Students' => $one("SELECT COUNT(*) FROM users WHERE role='student' AND is_verified=1"), 'Courses' => $one('SELECT COUNT(*) FROM courses'),
          'Lessons' => $one('SELECT COUNT(*) FROM lessons'), 'Enrollments' => $one('SELECT COUNT(*) FROM enrollments')];
$perCourse = db()->query('SELECT c.title, COUNT(e.id) n FROM courses c LEFT JOIN enrollments e ON e.course_id=c.id GROUP BY c.id ORDER BY n DESC LIMIT 8')->fetchAll();
$perCat = db()->query('SELECT cat.name, COUNT(c.id) n FROM categories cat LEFT JOIN courses c ON c.category_id=cat.id GROUP BY cat.id')->fetchAll();
$signups = db()->query("SELECT DATE(created_at) d, COUNT(*) n FROM users WHERE is_verified=1 AND created_at >= DATE_SUB(CURDATE(), INTERVAL 29 DAY) GROUP BY d ORDER BY d")->fetchAll();
$pageTitle = 'Analytics'; require __DIR__ . '/../includes/header.php'; require __DIR__ . '/nav.php'; ?>
<h1>Analytics</h1>
<section class="stats">
<?php foreach ($stats as $label => $n): ?><div class="card stat"><strong><?= $n ?></strong><span><?= e($label) ?></span></div><?php endforeach; ?>
</section>
<section class="two-col">
  <div class="card"><h2>Enrollments per course</h2><canvas id="c1" height="220"></canvas></div>
  <div class="card"><h2>Courses per category</h2><canvas id="c2" height="220"></canvas></div>
</section>
<div class="card"><h2>New students, last 30 days</h2><canvas id="c3" height="120"></canvas></div>
<?php
$j = fn($rows, $k) => json_encode(array_column($rows, $k), JSON_HEX_TAG | JSON_HEX_AMP);
ob_start(); ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
const teal = '#0E6B63', gold = '#F2B632', palette = ['#0E6B63', '#F2B632', '#3B7EA1', '#B8523A', '#6B8F3A', '#7A5C99'];
new Chart(document.getElementById('c1'), { type: 'bar',
  data: { labels: <?= $j($perCourse, 'title') ?>, datasets: [{ label: 'Enrollments', data: <?= $j($perCourse, 'n') ?>, backgroundColor: teal }] },
  options: { indexAxis: 'y', plugins: { legend: { display: false } }, scales: { x: { beginAtZero: true, ticks: { precision: 0 } } } } });
new Chart(document.getElementById('c2'), { type: 'doughnut',
  data: { labels: <?= $j($perCat, 'name') ?>, datasets: [{ data: <?= $j($perCat, 'n') ?>, backgroundColor: palette }] } });
new Chart(document.getElementById('c3'), { type: 'line',
  data: { labels: <?= $j($signups, 'd') ?>, datasets: [{ label: 'New students', data: <?= $j($signups, 'n') ?>, borderColor: teal, backgroundColor: 'rgba(14,107,99,.15)', fill: true, tension: .3 }] },
  options: { scales: { y: { beginAtZero: true, ticks: { precision: 0 } } } } });
</script>
<?php $extraScripts = ob_get_clean(); require __DIR__ . '/../includes/footer.php'; ?>
