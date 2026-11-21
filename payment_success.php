<?php
require_once __DIR__ . '/includes/functions.php';
$me = require_login();

$orderNum = trim($_GET['order'] ?? '');
$courseId = (int)($_GET['course_id'] ?? 0);

if ($orderNum !== '') {
    $s = db()->prepare('SELECT p.*, c.title AS course_title, c.level, c.duration_hours, c.instructor, cat.name AS category_name, u.name AS user_name, u.email AS user_email
      FROM payments p
      JOIN courses c ON c.id=p.course_id
      JOIN categories cat ON cat.id=c.category_id
      JOIN users u ON u.id=p.user_id
      WHERE p.order_number=? AND p.user_id=?');
    $s->execute([$orderNum, $me['id']]);
    $payment = $s->fetch();
} elseif ($courseId > 0) {
    $s = db()->prepare('SELECT p.*, c.title AS course_title, c.level, c.duration_hours, c.instructor, cat.name AS category_name, u.name AS user_name, u.email AS user_email
      FROM payments p
      JOIN courses c ON c.id=p.course_id
      JOIN categories cat ON cat.id=c.category_id
      JOIN users u ON u.id=p.user_id
      WHERE p.course_id=? AND p.user_id=?
      ORDER BY p.id DESC LIMIT 1');
    $s->execute([$courseId, $me['id']]);
    $payment = $s->fetch();
} else {
    $payment = null;
}

if (!$payment) {
    flash('info', 'No matching payment receipt found.');
    redirect('dashboard.php');
}

$details = json_decode($payment['payment_details'] ?? '{}', true) ?: [];
$pageTitle = 'Payment Confirmation & Invoice · ' . $payment['order_number'];
require __DIR__ . '/includes/header.php';
?>

<div class="receipt-container">
  <!-- Celebration Top Header -->
  <div class="receipt-success-banner">
    <div class="success-icon-bubble">✓</div>
    <h1>Payment Successful!</h1>
    <p>Thank you, <strong><?= e($payment['user_name']) ?></strong>! You are now fully enrolled in <strong><?= e($payment['course_title']) ?></strong>.</p>
    <div class="receipt-cta-row">
      <a href="course.php?id=<?= (int)$payment['course_id'] ?>" class="btn btn-primary-cta">
        ▶ Start Learning Course Now
      </a>
      <button type="button" onclick="window.print()" class="btn btn-secondary-cta">
        🖨️ Print / Download Receipt
      </button>
      <a href="dashboard.php" class="btn btn-outline-cta">
        My Dashboard
      </a>
    </div>
  </div>

  <!-- Printable Tax Invoice / Receipt Card -->
  <div class="card invoice-card" id="printableInvoice">
    <div class="invoice-header">
      <div>
        <div class="invoice-brand"><?= e(SITE_NAME) ?></div>
        <p class="invoice-company-sub">ApexPlanet Learning Systems · Official Tax Receipt</p>
      </div>
      <div class="invoice-badge-wrap">
        <span class="invoice-status-paid">PAID IN FULL</span>
        <span class="invoice-date"><?= date('d M Y, h:i A', strtotime($payment['created_at'])) ?></span>
      </div>
    </div>

    <div class="invoice-divider"></div>

    <div class="invoice-meta-grid">
      <div>
        <span class="meta-label">Billed To</span>
        <strong class="meta-val"><?= e($payment['user_name']) ?></strong>
        <span class="meta-sub"><?= e($payment['user_email']) ?></span>
      </div>
      <div>
        <span class="meta-label">Order Number</span>
        <strong class="meta-val"><?= e($payment['order_number']) ?></strong>
        <span class="meta-sub">Ref: <?= e($payment['transaction_id']) ?></span>
      </div>
      <div>
        <span class="meta-label">Payment Method</span>
        <strong class="meta-val"><?= e($payment['payment_method']) ?></strong>
        <span class="meta-sub">
          <?php
          if ($payment['payment_method'] === 'Card') echo 'Card ending in •••• ' . e($payment['card_last4'] ?? '4242');
          elseif ($payment['payment_method'] === 'UPI') echo e($details['vpa'] ?? 'UPI Transfer');
          elseif ($payment['payment_method'] === 'NetBanking') echo e($details['bank'] ?? 'Internet Banking');
          else echo e($details['wallet'] ?? 'Digital Wallet');
          ?>
        </span>
      </div>
    </div>

    <!-- Items Table -->
    <div class="invoice-table-wrap">
      <table class="invoice-table">
        <thead>
          <tr>
            <th>Item Description</th>
            <th>Level / Duration</th>
            <th style="text-align:right;">Amount</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>
              <strong><?= e($payment['course_title']) ?></strong>
              <div style="font-size:0.85rem; color:var(--muted);">Category: <?= e($payment['category_name']) ?> · Instructor: <?= e($payment['instructor']) ?></div>
            </td>
            <td><?= e($payment['level']) ?> · <?= e($payment['duration_hours']) ?> hrs</td>
            <td style="text-align:right; font-weight:700;">₹<?= number_format((float)$payment['original_amount']) ?></td>
          </tr>
        </tbody>
        <tfoot>
          <tr>
            <td colspan="2" style="text-align:right;">Original Course Price:</td>
            <td style="text-align:right;">₹<?= number_format((float)$payment['original_amount']) ?></td>
          </tr>
          <?php if ((float)$payment['discount_amount'] > 0 || (float)$payment['original_amount'] > (float)$payment['amount']): ?>
            <tr>
              <td colspan="2" style="text-align:right; color:#0e6b63;">Total Savings &amp; Discounts:</td>
              <td style="text-align:right; color:#0e6b63;">-₹<?= number_format((float)$payment['original_amount'] - (float)$payment['amount']) ?></td>
            </tr>
          <?php endif; ?>
          <tr>
            <td colspan="2" style="text-align:right;">Taxes &amp; Platform Fee:</td>
            <td style="text-align:right; color:var(--muted);">₹0.00 (Inclusive)</td>
          </tr>
          <tr class="invoice-total-row">
            <td colspan="2" style="text-align:right; font-size:1.15rem; font-weight:800;">Grand Total Paid:</td>
            <td style="text-align:right; font-size:1.25rem; font-weight:800; color:var(--teal);">₹<?= number_format((float)$payment['amount']) ?></td>
          </tr>
        </tfoot>
      </table>
    </div>

    <div class="invoice-footer">
      <div class="invoice-seal">
        <span class="seal-icon">🛡️</span>
        <span>Verified Digital Transaction · LearnLoop Secured Payments</span>
      </div>
      <p class="invoice-help">Need assistance with your purchase? Contact support at <a href="mailto:support@learnloop.com">support@learnloop.com</a></p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>
