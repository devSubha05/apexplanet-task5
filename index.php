<?php
require_once __DIR__ . '/includes/functions.php';
$cats = db()->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();

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

$trendingSearches = [
  'python' => [
    'title' => 'Python',
    'query' => 'Python',
    'cards' => [
      [
        'course_id' => 9,
        'title' => 'Microsoft Python Development',
        'org' => 'Microsoft',
        'org_icon_html' => '<svg width="12" height="12" viewBox="0 0 20 20" class="ts-svg-icon" aria-hidden="true"><rect x="1" y="1" width="8" height="8" fill="#F25022"/><rect x="11" y="1" width="8" height="8" fill="#7FBA00"/><rect x="1" y="11" width="8" height="8" fill="#00A4EF"/><rect x="11" y="11" width="8" height="8" fill="#FFB900"/></svg>',
        'tag' => 'PYTHON',
        'type' => 'Professional Certificate',
        'rating' => '4.4',
        'image' => 'assets/img/courses/course-9.jpg'
      ],
      [
        'course_id' => 4,
        'title' => 'Google IT Automation with Python',
        'org' => 'Google',
        'org_icon_html' => '<svg width="13" height="13" viewBox="0 0 24 24" class="ts-svg-icon" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>',
        'tag' => 'AUTOMATION',
        'type' => 'Professional Certificate',
        'rating' => '4.8',
        'image' => 'assets/img/courses/course-4.jpg'
      ],
      [
        'course_id' => 10,
        'title' => 'Introduction to Programming with Python and Java',
        'org' => 'University of Pennsylvania',
        'org_icon_html' => '<span class="ts-org-tag penn-tag">Penn</span>',
        'tag' => 'PROGRAM',
        'type' => 'Specialization',
        'rating' => '4.5',
        'image' => 'assets/img/courses/course-10.jpg'
      ]
    ]
  ],
  'data_analytics' => [
    'title' => 'Data Analytics',
    'query' => 'Data Analytics',
    'cards' => [
      [
        'course_id' => 24,
        'title' => 'IBM Cybersecurity Analyst',
        'org' => 'IBM',
        'org_icon_html' => '<span class="ts-org-tag ibm-tag">IBM</span>',
        'tag' => 'CYBER',
        'type' => 'Professional Certificate',
        'rating' => '4.6',
        'image' => 'assets/img/courses/course-24.jpg'
      ],
      [
        'course_id' => 19,
        'title' => 'Tableau Business Intelligence Analyst',
        'org' => 'Tableau Learning Partner',
        'org_icon_html' => '<span class="ts-org-tag tab-tag">✦</span>',
        'tag' => 'TABLEAU',
        'type' => 'Professional Certificate',
        'rating' => '4.7',
        'image' => 'assets/img/courses/course-19.jpg'
      ],
      [
        'course_id' => 17,
        'title' => 'Microsoft Azure Data Fundamentals DP-900 Exam...',
        'org' => 'Microsoft',
        'org_icon_html' => '<svg width="12" height="12" viewBox="0 0 20 20" class="ts-svg-icon" aria-hidden="true"><rect x="1" y="1" width="8" height="8" fill="#F25022"/><rect x="11" y="1" width="8" height="8" fill="#7FBA00"/><rect x="1" y="11" width="8" height="8" fill="#00A4EF"/><rect x="11" y="11" width="8" height="8" fill="#FFB900"/></svg>',
        'tag' => 'AZURE',
        'type' => 'Specialization',
        'rating' => '4.6',
        'image' => 'assets/img/courses/course-17.jpg'
      ]
    ]
  ],
  'healthcare' => [
    'title' => 'Healthcare',
    'query' => 'Healthcare',
    'cards' => [
      [
        'course_id' => 18,
        'title' => 'Google AI for Healthcare Professionals',
        'org' => 'Google',
        'org_icon_html' => '<svg width="13" height="13" viewBox="0 0 24 24" class="ts-svg-icon" aria-hidden="true"><path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/><path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/><path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.06H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.94l2.85-2.22.81-.63z"/><path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84c.87-2.6 3.3-4.52 6.16-4.52z"/></svg>',
        'tag' => 'HEALTH AI',
        'type' => 'Specialization',
        'rating' => '4.7',
        'image' => 'assets/img/courses/course-18.jpg'
      ],
      [
        'course_id' => 16,
        'title' => 'ACE Health Coach',
        'org' => 'American Council on Exercise',
        'org_icon_html' => '<span class="ts-org-tag ace-tag">➔</span>',
        'tag' => 'ACE',
        'type' => 'Professional Certificate',
        'rating' => '4.4',
        'image' => 'assets/img/courses/course-16.jpg'
      ],
      [
        'course_id' => 1,
        'title' => 'NASM Personal Fitness Trainer',
        'org' => 'National Academy of Sports Medicine',
        'org_icon_html' => '<span class="ts-org-tag nasm-tag">NASM</span>',
        'tag' => 'FITNESS',
        'type' => 'Professional Certificate',
        'rating' => '4.7',
        'image' => 'assets/img/courses/course-1.jpg'
      ]
    ]
  ]
];

$studentReviews = [
  [
    'quote' => 'When I was a child, I used to fear coding. But now, I am in love with building web apps and Python because of LearnLoop.',
    'name' => 'ANJALI',
    'location' => 'GURUGRAM, HARYANA',
    'course' => 'Full-Stack Development',
    'image' => 'assets/img/student-anjali.png',
    'badge_theme' => 'sr-badge-orange',
  ],
  [
    'quote' => 'The structured learning paths and hands-on projects helped me transition from non-tech into software engineering within 6 months.',
    'name' => 'RAHUL SHARMA',
    'location' => 'BENGALURU, KARNATAKA',
    'course' => 'Python & Backend Systems',
    'image' => 'assets/img/student-rahul.png',
    'badge_theme' => 'sr-badge-teal',
  ],
  [
    'quote' => 'Learning at my own pace with bite-sized, practical lessons made all the difference. Complex concepts finally made total sense.',
    'name' => 'PRIYA PATEL',
    'location' => 'MUMBAI, MAHARASHTRA',
    'course' => 'Data Analytics & SQL',
    'image' => 'assets/img/student-priya.png',
    'badge_theme' => 'sr-badge-purple',
  ],
];

$pageTitle = 'Courses'; require __DIR__ . '/includes/header.php'; ?>
<section class="hero">
  <div class="hero-content">
    <span class="hero-badge">✦ Interactive E-Learning</span>
    <h1>Learn <span class="hero-highlight">one lesson</span> at a time, and see your progress add up.</h1>
    <p>Short courses on web development, databases and programming. Enroll, finish lessons, and track your percentage live.</p>
  </div>
  <div class="hero-media">
    <div class="hero-image-card">
      <img src="assets/img/hero-student.jpg" alt="Student learning on LearnLoop" class="hero-student-img" loading="eager">
      <div class="hero-floating-pill">
        <span class="floating-icon">🎓</span>
        <div class="floating-text">
          <strong>10,000+</strong>
          <small>Active Learners</small>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="new-popular-section">
  <h2 class="np-heading">New and popular</h2>
  <div class="np-grid">
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

<section class="promo-banners-row" aria-label="Learning Pathways">
  <a href="javascript:void(0)" class="promo-banner-card" onclick="document.getElementById('level').value='Beginner'; document.getElementById('level').dispatchEvent(new Event('change')); const r = document.getElementById('results'); if(r) r.scrollIntoView({behavior:'smooth'});">
    <span class="promo-banner-text">Launch a new career</span>
    <div class="promo-banner-icon">
      <svg viewBox="0 0 80 60" width="70" height="52" fill="none">
        <rect x="15" y="5" width="55" height="48" rx="10" transform="rotate(-6 15 5)" fill="#ede9fe" opacity="0.85"/>
        <rect x="18" y="10" width="46" height="36" rx="4" stroke="#1d4ed8" stroke-width="2.5" fill="#ffffff"/>
        <line x1="24" y1="18" x2="48" y2="18" stroke="#93c5fd" stroke-width="2" stroke-linecap="round"/>
        <line x1="24" y1="24" x2="40" y2="24" stroke="#93c5fd" stroke-width="2" stroke-linecap="round"/>
        <circle cx="50" cy="30" r="5" stroke="#1d4ed8" stroke-width="2" fill="#dbeafe"/>
        <path d="M48 34l2 6 2-6" stroke="#1d4ed8" stroke-width="1.8" fill="none"/>
      </svg>
    </div>
  </a>

  <a href="course.php?id=3" class="promo-banner-card">
    <span class="promo-banner-text">Try LearnLoop for Business</span>
    <div class="promo-banner-icon">
      <svg viewBox="0 0 80 60" width="70" height="52" fill="none">
        <rect x="15" y="5" width="55" height="48" rx="10" transform="rotate(-6 15 5)" fill="#ede9fe" opacity="0.85"/>
        <path d="M22 44L38 22l14 22" stroke="#1d4ed8" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" fill="#ffffff"/>
        <path d="M38 44L48 30l12 14" stroke="#1d4ed8" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" fill="#eff6ff"/>
        <line x1="38" y1="22" x2="38" y2="10" stroke="#1d4ed8" stroke-width="2.2" stroke-linecap="round"/>
        <path d="M38 10h12l-3 4 3 4H38" fill="#dbeafe" stroke="#1d4ed8" stroke-width="1.8" stroke-linejoin="round"/>
      </svg>
    </div>
  </a>

  <a href="dashboard.php" class="promo-banner-card">
    <span class="promo-banner-text">Earn a degree</span>
    <div class="promo-banner-icon">
      <svg viewBox="0 0 80 60" width="70" height="52" fill="none">
        <rect x="15" y="5" width="55" height="48" rx="10" transform="rotate(-6 15 5)" fill="#ede9fe" opacity="0.85"/>
        <polygon points="42,14 66,24 42,34 18,24" stroke="#1d4ed8" stroke-width="2.5" fill="#ffffff" stroke-linejoin="round"/>
        <path d="M26 28v8c0 5 7 9 16 9s16-4 16-9v-8" stroke="#1d4ed8" stroke-width="2.2" fill="#eff6ff"/>
        <path d="M56 24v12" stroke="#1d4ed8" stroke-width="2" stroke-linecap="round"/>
        <circle cx="56" cy="38" r="2.5" fill="#1d4ed8"/>
      </svg>
    </div>
  </a>
</section>

<section class="explore-categories-section" aria-label="Explore Categories">
  <h2 class="explore-cat-heading">Explore categories</h2>
  <div class="explore-cat-chips-wrap">
    <button type="button" class="cat-chip" data-query="Business">
      <span class="cat-chip-icon">💼</span> Business
    </button>
    <button type="button" class="cat-chip" data-query="Machine Learning">
      <span class="cat-chip-icon">✦</span> Artificial Intelligence
    </button>
    <button type="button" class="cat-chip" data-cat="Data Science">
      <span class="cat-chip-icon">📈</span> Data Science
    </button>
    <button type="button" class="cat-chip" data-cat="Programming">
      <span class="cat-chip-icon">&lt;&gt;</span> Computer Science
    </button>
    <button type="button" class="cat-chip" data-cat="Web Development">
      <span class="cat-chip-icon">💻</span> Information Technology
    </button>
    <button type="button" class="cat-chip" data-query="Workflow">
      <span class="cat-chip-icon">🚀</span> Personal Development
    </button>
    <button type="button" class="cat-chip" data-cat="Cybersecurity">
      <span class="cat-chip-icon">🛡️</span> Healthcare &amp; Security
    </button>
    <button type="button" class="cat-chip" data-query="Python">
      <span class="cat-chip-icon">🌐</span> Language Learning
    </button>
    <button type="button" class="cat-chip" data-cat="Databases">
      <span class="cat-chip-icon">👥</span> Databases &amp; Systems
    </button>
    <button type="button" class="cat-chip" data-cat="Design">
      <span class="cat-chip-icon">✏️</span> Arts and Humanities
    </button>
    <button type="button" class="cat-chip" data-cat="DevOps">
      <span class="cat-chip-icon">🧪</span> Physical Science &amp; Engineering
    </button>
    <button type="button" class="cat-chip" data-query="Algorithms">
      <span class="cat-chip-icon">⊞</span> Math and Logic
    </button>
  </div>
</section>

<section class="trending-searches-section" aria-label="Trending searches">
  <h2 class="ts-heading">Trending searches</h2>
  <div class="ts-grid">
    <?php foreach ($trendingSearches as $groupKey => $group): ?>
    <div class="ts-column">
      <a href="javascript:void(0)" class="ts-col-header" onclick="document.getElementById('q').value='<?= e($group['query']) ?>'; document.getElementById('q').dispatchEvent(new Event('input')); const r = document.getElementById('results'); if(r) r.scrollIntoView({behavior:'smooth'});">
        <span class="ts-col-title"><?= e($group['title']) ?></span>
        <span class="ts-arrow" aria-hidden="true">→</span>
      </a>
      <div class="ts-cards-list">
        <?php foreach ($group['cards'] as $card): ?>
        <a href="course.php?id=<?= (int)$card['course_id'] ?>" class="ts-card">
          <div class="ts-thumb-box">
            <img src="<?= e($card['image']) ?>" alt="<?= e($card['title']) ?>" class="ts-thumb-img" loading="lazy">
            <span class="ts-thumb-tag"><?= e($card['tag']) ?></span>
          </div>
          <div class="ts-card-content">
            <div class="ts-partner-row">
              <span class="ts-partner-icon"><?= $card['org_icon_html'] ?></span>
              <span class="ts-partner-name"><?= e($card['org']) ?></span>
            </div>
            <h3 class="ts-title" title="<?= e($card['title']) ?>"><?= e($card['title']) ?></h3>
            <div class="ts-meta-row">
              <span class="ts-cert"><?= e($card['type']) ?></span>
              <span class="ts-dot">·</span>
              <span class="ts-star">★ <?= e($card['rating']) ?></span>
            </div>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="student-reviews-section" aria-label="Student reviews">
  <div class="sr-header">
    <span class="sr-badge">💬 Learner Stories</span>
    <h2 class="ts-heading" style="margin-bottom:.35rem;">What our learners say</h2>
    <p class="sr-subheading">Real stories from students transforming their careers and discovering their passions with LearnLoop.</p>
  </div>
  <div class="sr-grid">
    <?php foreach ($studentReviews as $review): ?>
    <article class="sr-card">
      <div class="sr-quote-icon" aria-hidden="true">“</div>
      <blockquote class="sr-quote-text">
        “<?= e($review['quote']) ?>”
      </blockquote>
      <div class="sr-footer">
        <div class="sr-student-meta">
          <span class="sr-student-name"><?= e($review['name']) ?></span>
          <span class="sr-student-location"><?= e($review['location']) ?></span>
          <span class="sr-course-tag"><?= e($review['course']) ?></span>
        </div>
        <div class="sr-avatar-wrapper <?= e($review['badge_theme']) ?>">
          <div class="sr-avatar-decor" aria-hidden="true"></div>
          <img src="<?= e($review['image']) ?>" alt="<?= e($review['name']) ?>" class="sr-avatar-img" loading="lazy">
        </div>
      </div>
    </article>
    <?php endforeach; ?>
  </div>
</section>

<div class="courses-section-header">
  <div style="display:flex;align-items:center;gap:.8rem;flex-wrap:wrap;">
    <h2 class="np-heading" style="margin-bottom:0;">Explore all courses</h2>
    <span id="count" class="courses-count-text" aria-live="polite"></span>
  </div>
  <a href="courses.php" class="btn btn-small" style="text-decoration:none;">Go to Courses Page →</a>
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
