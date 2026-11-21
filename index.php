<?php
require_once __DIR__ . '/includes/functions.php';
$cats = db()->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();

// --- Fetch courses for "New and Popular" section ---
$popularCourses = db()->query('SELECT c.id, c.title, c.level, c.price, c.original_price,
  COALESCE(c.image, CONCAT("assets/img/courses/course-", c.id, ".jpg")) AS image,
  cat.name AS category,
  (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS enrollments,
  (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lesson_count
  FROM courses c JOIN categories cat ON cat.id=c.category_id
  ORDER BY enrollments DESC, c.id ASC LIMIT 3')->fetchAll();

$newCourses = db()->query('SELECT c.id, c.title, c.level, c.price, c.original_price,
  COALESCE(c.image, CONCAT("assets/img/courses/course-", c.id, ".jpg")) AS image,
  cat.name AS category,
  (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lesson_count
  FROM courses c JOIN categories cat ON cat.id=c.category_id
  ORDER BY c.id DESC LIMIT 3')->fetchAll();

$trendingCourses = db()->query('SELECT c.id, c.title, c.level, c.price, c.original_price,
  COALESCE(c.image, CONCAT("assets/img/courses/course-", c.id, ".jpg")) AS image,
  cat.name AS category,
  (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lesson_count
  FROM courses c JOIN categories cat ON cat.id=c.category_id
  WHERE cat.name IN ("Data Science","Cybersecurity","DevOps","Programming")
  ORDER BY c.id DESC LIMIT 3')->fetchAll();

$pageTitle = 'Courses'; require __DIR__ . '/includes/header.php'; ?>
<section class="hero">
  <span class="hero-badge">✦ Interactive E-Learning</span>
  <h1>Learn <span class="hero-highlight">one lesson</span> at a time, and see your progress add up.</h1>
  <p>Short courses on web development, databases and programming. Enroll, finish lessons, and track your percentage live.</p>
</section>

<!-- ===== New and Popular Section ===== -->
<section class="new-popular-section">
  <h2 class="np-heading">New and popular</h2>
  <div class="np-grid">
    <!-- Column 1: Most Popular -->
    <div class="np-column">
      <div class="np-col-header">
        <span class="np-col-title">Most popular</span>
        <span class="np-arrow">→</span>
      </div>
      <?php foreach ($popularCourses as $pc): ?>
      <a href="course.php?id=<?= (int)$pc['id'] ?>" class="np-card">
        <div class="np-card-img-wrap">
          <img src="<?= e($pc['image']) ?>" alt="<?= e($pc['title']) ?>" class="np-card-photo" loading="lazy">
        </div>
        <div class="np-card-body">
          <span class="np-provider"><?= e($pc['category']) ?></span>
          <span class="np-title"><?= e($pc['title']) ?></span>
          <div class="np-price-row">
            <span class="np-price">₹<?= number_format((float)$pc['price']) ?></span>
            <span class="np-orig-price">₹<?= number_format((float)$pc['original_price']) ?></span>
            <span class="np-meta">· ⭐ 4.8</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Column 2: Hot New Releases -->
    <div class="np-column">
      <div class="np-col-header">
        <span class="np-col-title">Hot new releases</span>
        <span class="np-arrow">→</span>
      </div>
      <?php foreach ($newCourses as $nc): ?>
      <a href="course.php?id=<?= (int)$nc['id'] ?>" class="np-card">
        <div class="np-card-img-wrap">
          <img src="<?= e($nc['image']) ?>" alt="<?= e($nc['title']) ?>" class="np-card-photo" loading="lazy">
        </div>
        <div class="np-card-body">
          <span class="np-provider"><?= e($nc['category']) ?></span>
          <span class="np-title"><?= e($nc['title']) ?></span>
          <div class="np-price-row">
            <span class="np-price">₹<?= number_format((float)$nc['price']) ?></span>
            <span class="np-orig-price">₹<?= number_format((float)$nc['original_price']) ?></span>
            <span class="np-meta">· ⭐ 4.7</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>

    <!-- Column 3: Trending Courses -->
    <div class="np-column">
      <div class="np-col-header">
        <span class="np-col-title">Trending courses</span>
        <span class="np-arrow">→</span>
      </div>
      <?php foreach ($trendingCourses as $tc): ?>
      <a href="course.php?id=<?= (int)$tc['id'] ?>" class="np-card">
        <div class="np-card-img-wrap">
          <img src="<?= e($tc['image']) ?>" alt="<?= e($tc['title']) ?>" class="np-card-photo" loading="lazy">
        </div>
        <div class="np-card-body">
          <span class="np-provider"><?= e($tc['category']) ?></span>
          <span class="np-title"><?= e($tc['title']) ?></span>
          <div class="np-price-row">
            <span class="np-price">₹<?= number_format((float)$tc['price']) ?></span>
            <span class="np-orig-price">₹<?= number_format((float)$tc['original_price']) ?></span>
            <span class="np-meta">· ⭐ 4.9</span>
          </div>
        </div>
      </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<!-- ===== End New and Popular ===== -->
<!-- ===== All Courses & Filters Section ===== -->
<div class="courses-section-header">
  <h2 class="np-heading" style="margin-bottom:0;">Explore all courses</h2>
  <span id="count" class="courses-count-text" aria-live="polite"></span>
</div>

<section class="filters" aria-label="Find a course">
  <input type="search" id="q" placeholder="🔍 Search courses by keyword..." aria-label="Search courses">
  <select id="cat" aria-label="Category">
    <option value="0">All categories</option>
    <?php foreach ($cats as $c): ?><option value="<?= (int)$c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?>
  </select>
  <select id="level" aria-label="Level">
    <option value="">Any level</option><option>Beginner</option><option>Intermediate</option><option>Advanced</option>
  </select>
</section>
<section id="results" class="grid"></section>
<div class="toggle-container" id="toggleWrap" style="display:none;">
  <button type="button" id="seeMoreBtn" class="btn btn-toggle" aria-expanded="false">
    <span id="seeMoreText">See More</span>
    <span class="btn-icon" id="seeMoreIcon" aria-hidden="true">↓</span>
  </button>
</div>
<?php $extraScripts = '<script src="assets/js/app.js"></script>'; require __DIR__ . '/includes/footer.php'; ?>
