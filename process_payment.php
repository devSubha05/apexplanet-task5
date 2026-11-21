<?php
require_once __DIR__ . '/includes/functions.php';
$me = require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('index.php');
}

csrf_check();

$courseId = (int)($_POST['course_id'] ?? 0);
$s = db()->prepare('SELECT id, title, price, original_price FROM courses WHERE id=?');
$s->execute([$courseId]);
$course = $s->fetch();

if (!$course) {
    flash('error', 'Course not found.');
    redirect('index.php');
}

// Check if already enrolled
$s = db()->prepare('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?');
$s->execute([$me['id'], $courseId]);
if ($s->fetchColumn()) {
    flash('info', 'You already own this course.');
    redirect('course.php?id=' . $courseId);
}

// Calculate amounts
$basePrice = (float)$course['price'];
$origPrice = (float)$course['original_price'];
$coupon = trim(strtoupper($_POST['applied_coupon'] ?? ''));
$discountAmount = 0.0;

if ($coupon === 'APEX100' || $coupon === 'LEARN100' || $coupon === 'SPECIAL100') {
    $discountAmount = 100.0;
} elseif ($coupon === 'APEX50' || $coupon === 'SAVE50') {
    $discountAmount = 50.0;
} elseif ($coupon === 'LEARNLOOP' || $coupon === 'WELCOME20') {
    $discountAmount = round($basePrice * 0.20, 2);
}

$finalAmount = max(0.0, $basePrice - $discountAmount);

// Payment method details
$method = trim($_POST['payment_method'] ?? 'UPI');
if (!in_array($method, ['UPI', 'Card', 'NetBanking', 'Wallet'], true)) {
    $method = 'UPI';
}

$cardLast4 = null;
$paymentDetails = [];

if ($method === 'UPI') {
    $upiId = trim($_POST['upi_id'] ?? '');
    $paymentDetails['vpa'] = $upiId ?: 'UPI QR Scan';
    $paymentDetails['channel'] = 'UPI 2.0 Instant Payment';
} elseif ($method === 'Card') {
    $cardRaw = preg_replace('/\D/', '', $_POST['card_number'] ?? '');
    $cardLast4 = substr($cardRaw, -4) ?: '4242';
    $cardName = trim($_POST['card_name'] ?? $me['name']);
    $paymentDetails['card_last4'] = $cardLast4;
    $paymentDetails['card_holder'] = $cardName;
    $paymentDetails['channel'] = 'Debit / Credit Card';
} elseif ($method === 'NetBanking') {
    $bank = trim($_POST['other_bank'] ?? '') ?: trim($_POST['bank_name'] ?? 'HDFC Bank');
    $paymentDetails['bank'] = $bank;
    $paymentDetails['channel'] = 'Internet Banking';
} elseif ($method === 'Wallet') {
    $wallet = trim($_POST['wallet_provider'] ?? 'Paytm Wallet');
    $paymentDetails['wallet'] = $wallet;
    $paymentDetails['channel'] = 'Digital Wallet';
}

// Generate Order ID & Transaction ID
$orderNumber = 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(3)));
$txnId = 'TXN-' . strtoupper(bin2hex(random_bytes(6)));

try {
    $pdo = db();
    $pdo->beginTransaction();

    // 1. Insert into payments table
    $stmt = $pdo->prepare('INSERT INTO payments 
      (order_number, user_id, course_id, amount, original_amount, discount_amount, payment_method, transaction_id, payment_status, card_last4, payment_details) 
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    
    $stmt->execute([
        $orderNumber,
        $me['id'],
        $courseId,
        $finalAmount,
        $origPrice,
        $discountAmount,
        $method,
        $txnId,
        'completed',
        $cardLast4,
        json_encode($paymentDetails, JSON_UNESCAPED_UNICODE)
    ]);

    // 2. Insert into enrollments table
    $pdo->prepare('INSERT IGNORE INTO enrollments (user_id, course_id) VALUES (?, ?)')
        ->execute([$me['id'], $courseId]);

    $pdo->commit();

    flash('success', 'Payment successful! You are now enrolled in ' . $course['title']);
    redirect('payment_success.php?order=' . urlencode($orderNumber));

} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    flash('error', 'Payment failed: ' . $e->getMessage());
    redirect('checkout.php?course_id=' . $courseId);
}
