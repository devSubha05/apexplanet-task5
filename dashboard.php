<?php
require_once __DIR__ . '/includes/functions.php';
$me = require_login();

// Fetch enrolled courses
$s = db()->prepare('SELECT c.id,c.title,c.level,cat.name AS category,
  COALESCE(c.image, CONCAT("assets/img/courses/course-", c.id, ".jpg")) AS image
  FROM enrollments e 
  JOIN courses c ON c.id=e.course_id 
  JOIN categories cat ON cat.id=c.category_id 
  WHERE e.user_id=? 
  ORDER BY e.enrolled_at DESC');
$s->execute([$me['id']]);
$mine = $s->fetchAll();

// Fetch payment transactions
$s = db()->prepare('SELECT p.*, c.title AS course_title 
  FROM payments p 
  JOIN courses c ON c.id=p.course_id 
  WHERE p.user_id=? 
  ORDER BY p.id DESC');
$s->execute([$me['id']]);
$myPayments = $s->fetchAll();

$pageTitle = 'My Learning';
require __DIR__ . '/includes/header.php';
?>

<div class="dashboard-header-bar">
  <div>
    <h1>Welcome back, <?= e($me['name']) ?> 👋</h1>
    <p class="lead" style="margin-bottom:0;">Track your learning progress and view your course invoices.</p>
  </div>
  <div>
    <a href="courses.php" class="btn btn-small">+ Explore More Courses</a>
  </div>
</div>

<h2 style="margin-top:2rem;">My Enrolled Courses (<?= count($mine) ?>)</h2>
<?php if (!$mine): ?>
  <div class="card" style="text-align:center; padding:3rem 1rem;">
    <p class="empty" style="font-size:1.15rem; margin-bottom:1.5rem;">You haven't enrolled in any paid courses yet.</p>
    <a href="courses.php" class="btn">Browse All Courses</a>
  </div>
<?php else: ?>
  <section class="grid">
    <?php foreach ($mine as $c): $p = course_progress($me['id'], $c['id']); ?>
      <a class="card course-card udemy-style-card" href="course.php?id=<?= (int)$c['id'] ?>">
        <div class="udemy-thumb-wrap" style="height:140px;">
          <img src="<?= e($c['image']) ?>" alt="<?= e($c['title']) ?>" class="udemy-thumb-img" loading="lazy">
        </div>
        <div class="udemy-card-body">
          <span class="tag"><?= e($c['category']) ?> · <?= e($c['level']) ?></span>
          <h2 style="font-size:1.05rem; margin-top:.2rem;"><?= e($c['title']) ?></h2>
          <div class="progress" style="margin:.6rem 0 .3rem;"><span style="width:<?= $p['pct'] ?>%"></span></div>
          <p class="meta" style="margin:0; font-size:.84rem;"><?= $p['done'] ?> of <?= $p['total'] ?> lessons · <?= $p['pct'] ?>% complete</p>
        </div>
      </a>
    <?php endforeach; ?>
  </section>
<?php endif; ?>

<!-- Payment & Invoices Section -->
<h2 style="margin-top:3rem;">Purchase History &amp; Invoices</h2>
<?php if (!$myPayments): ?>
  <p class="meta">No billing records found.</p>
<?php else: ?>
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Order #</th>
          <th>Course Title</th>
          <th>Date</th>
          <th>Method</th>
          <th>Amount Paid</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($myPayments as $pay): ?>
          <tr>
            <td><code><?= e($pay['order_number']) ?></code></td>
            <td><strong><?= e($pay['course_title']) ?></strong></td>
            <td><?= date('d M Y, h:i A', strtotime($pay['created_at'])) ?></td>
            <td><span class="pill-payment-method"><?= e($pay['payment_method']) ?></span></td>
            <td><strong>₹<?= number_format((float)$pay['amount']) ?></strong></td>
            <td><span class="badge-status-completed">✓ <?= ucfirst(e($pay['payment_status'])) ?></span></td>
            <td>
              <a href="payment_success.php?order=<?= urlencode($pay['order_number']) ?>" class="btn btn-small">
                📄 View Invoice
              </a>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>
