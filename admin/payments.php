<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$stats = db()->query('SELECT 
  COUNT(*) as total_orders,
  COALESCE(SUM(amount), 0) as total_revenue,
  COALESCE(AVG(amount), 0) as avg_order_value
  FROM payments WHERE payment_status="completed"')->fetch();

$payments = db()->query('SELECT p.*, c.title as course_title, u.name as user_name, u.email as user_email
  FROM payments p
  JOIN courses c ON c.id=p.course_id
  JOIN users u ON u.id=p.user_id
  ORDER BY p.id DESC LIMIT 100')->fetchAll();

$pageTitle = 'Orders & Revenue';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar">
  <h1>Orders &amp; Revenue</h1>
</div>

<div class="stats">
  <div class="card stat">
    <strong>₹<?= number_format((float)$stats['total_revenue']) ?></strong>
    <span>Total Revenue Collected</span>
  </div>
  <div class="card stat">
    <strong><?= (int)$stats['total_orders'] ?></strong>
    <span>Paid Course Orders</span>
  </div>
  <div class="card stat">
    <strong>₹<?= number_format((float)$stats['avg_order_value']) ?></strong>
    <span>Average Order Value</span>
  </div>
</div>

<div class="table-wrap">
  <table>
    <thead>
      <tr>
        <th>Order #</th>
        <th>Student</th>
        <th>Course</th>
        <th>Method</th>
        <th>Amount</th>
        <th>Date</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($payments as $p): ?>
        <tr>
          <td><code><?= e($p['order_number']) ?></code></td>
          <td><strong><?= e($p['user_name']) ?></strong><br><small><?= e($p['user_email']) ?></small></td>
          <td><?= e($p['course_title']) ?></td>
          <td><span class="pill-payment-method"><?= e($p['payment_method']) ?></span></td>
          <td><strong>₹<?= number_format((float)$p['amount']) ?></strong></td>
          <td><?= date('d M Y, h:i A', strtotime($p['created_at'])) ?></td>
          <td><span class="badge-status-completed">✓ <?= ucfirst(e($p['payment_status'])) ?></span></td>
        </tr>
      <?php endforeach; ?>
      <?php if (!$payments): ?>
        <tr>
          <td colspan="7" class="empty">No paid transactions recorded yet.</td>
        </tr>
      <?php endif; ?>
    </tbody>
  </table>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>
