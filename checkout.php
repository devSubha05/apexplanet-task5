<?php
require_once __DIR__ . '/includes/functions.php';
$courseId = (int)($_GET['course_id'] ?? 0);
if (!$courseId) redirect('index.php');

$s = db()->prepare('SELECT c.*, cat.name AS category,
  (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lessons
  FROM courses c JOIN categories cat ON cat.id=c.category_id WHERE c.id=?');
$s->execute([$courseId]);
$course = $s->fetch();
if (!$course) redirect('index.php');

$me = current_user();
if (!$me) {
    flash('info', 'Please log in or create an account to purchase this course.');
    redirect('login.php?redirect=' . urlencode('checkout.php?course_id=' . $courseId));
}

$s = db()->prepare('SELECT 1 FROM enrollments WHERE user_id=? AND course_id=?');
$s->execute([$me['id'], $courseId]);
if ($s->fetchColumn()) {
    flash('info', 'You already own this course. Continue learning below!');
    redirect('course.php?id=' . $courseId);
}

$price = (float)$course['price'];
$origPrice = (float)$course['original_price'];
$discountAmount = max(0, $origPrice - $price);
$discountPct = $origPrice > 0 ? round(($discountAmount / $origPrice) * 100) : 0;

$pageTitle = 'Checkout · ' . $course['title'];
require __DIR__ . '/includes/header.php';
?>

<div class="checkout-wrapper">
  <div class="checkout-main">
    <div class="checkout-header">
      <a href="course.php?id=<?= $courseId ?>" class="checkout-back-link">← Back to Course</a>
      <h1 class="checkout-title">Secure Checkout</h1>
      <p class="checkout-subtitle">Complete your payment to get instant lifetime access to this course.</p>
    </div>

    <div class="card checkout-card">
      <h2 class="card-heading">1. Student Details</h2>
      <div class="student-info-grid">
        <div>
          <span class="info-label">Student Name</span>
          <span class="info-value"><?= e($me['name']) ?></span>
        </div>
        <div>
          <span class="info-label">Email Address</span>
          <span class="info-value"><?= e($me['email']) ?></span>
        </div>
        <div>
          <span class="info-label">Course Access</span>
          <span class="info-value">Instant Activation</span>
        </div>
      </div>
    </div>

    <form id="paymentForm" action="process_payment.php" method="post" class="card checkout-card">
      <?= csrf_field() ?>
      <input type="hidden" name="course_id" value="<?= $courseId ?>">
      <input type="hidden" name="applied_coupon" id="appliedCouponInput" value="">
      <input type="hidden" name="final_amount" id="finalAmountInput" value="<?= $price ?>">

      <h2 class="card-heading">2. Select Payment Method</h2>

      <div class="payment-tabs" role="tablist">
        <label class="pay-tab active" data-target="tab-upi">
          <input type="radio" name="payment_method" value="UPI" checked>
          <span class="pay-tab-icon">📱</span>
          <span class="pay-tab-text">
            <strong>UPI / QR Code</strong>
            <small>GPay, PhonePe, Paytm, BHIM</small>
          </span>
        </label>

        <label class="pay-tab" data-target="tab-card">
          <input type="radio" name="payment_method" value="Card">
          <span class="pay-tab-icon">💳</span>
          <span class="pay-tab-text">
            <strong>Credit / Debit Card</strong>
            <small>Visa, Mastercard, RuPay</small>
          </span>
        </label>

        <label class="pay-tab" data-target="tab-netbanking">
          <input type="radio" name="payment_method" value="NetBanking">
          <span class="pay-tab-icon">🏦</span>
          <span class="pay-tab-text">
            <strong>Net Banking</strong>
            <small>All Indian Banks</small>
          </span>
        </label>

        <label class="pay-tab" data-target="tab-wallet">
          <input type="radio" name="payment_method" value="Wallet">
          <span class="pay-tab-icon">👛</span>
          <span class="pay-tab-text">
            <strong>Wallets</strong>
            <small>Paytm, Amazon Pay</small>
          </span>
        </label>
      </div>

      <div id="tab-upi" class="tab-content active">
        <div class="upi-box">
          <div class="upi-qr-section">
            <div class="qr-code-box">
              <svg viewBox="0 0 160 160" width="140" height="140" style="background:#fff; padding:6px; border-radius:8px;">
                <rect width="160" height="160" fill="#ffffff"/>
                <rect x="10" y="10" width="40" height="40" fill="#0e6b63" rx="4"/>
                <rect x="18" y="18" width="24" height="24" fill="#ffffff" rx="2"/>
                <rect x="24" y="24" width="12" height="12" fill="#0e6b63" rx="1"/>
                <rect x="110" y="10" width="40" height="40" fill="#0e6b63" rx="4"/>
                <rect x="118" y="18" width="24" height="24" fill="#ffffff" rx="2"/>
                <rect x="124" y="24" width="12" height="12" fill="#0e6b63" rx="1"/>
                <rect x="10" y="110" width="40" height="40" fill="#0e6b63" rx="4"/>
                <rect x="18" y="118" width="24" height="24" fill="#ffffff" rx="2"/>
                <rect x="24" y="124" width="12" height="12" fill="#0e6b63" rx="1"/>
                <rect x="60" y="15" width="8" height="8" fill="#14212b"/>
                <rect x="75" y="15" width="15" height="8" fill="#14212b"/>
                <rect x="60" y="30" width="12" height="12" fill="#0e6b63"/>
                <rect x="80" y="32" width="15" height="8" fill="#14212b"/>
                <rect x="15" y="65" width="12" height="8" fill="#14212b"/>
                <rect x="35" y="60" width="15" height="12" fill="#0e6b63"/>
                <rect x="60" y="55" width="40" height="40" fill="#f2b632" rx="4"/>
                <circle cx="80" cy="75" r="12" fill="#0e6b63"/>
                <rect x="110" y="60" width="15" height="10" fill="#14212b"/>
                <rect x="135" y="65" width="12" height="12" fill="#14212b"/>
                <rect x="60" y="110" width="10" height="10" fill="#14212b"/>
                <rect x="80" y="115" width="15" height="10" fill="#0e6b63"/>
                <rect x="110" y="110" width="20" height="10" fill="#14212b"/>
                <rect x="135" y="125" width="15" height="15" fill="#0e6b63"/>
              </svg>
              <div class="qr-caption">Scan to pay with any UPI App</div>
            </div>
            <div class="upi-or"><span>OR</span></div>
            <div class="upi-id-input-wrap">
              <label for="upiId">Enter UPI ID (VPA)
                <div class="upi-field-row">
                  <input type="text" id="upiId" name="upi_id" placeholder="e.g. mobile@okaxis / user@paytm" value="student@okhdfcbank">
                  <button type="button" class="btn btn-small" id="verifyUpiBtn">Verify</button>
                </div>
              </label>
              <div class="upi-verified-badge" id="upiBadge">✓ Verified: <?= e($me['name']) ?></div>
              <div class="upi-supported-apps">
                <span class="upi-pill">Google Pay</span>
                <span class="upi-pill">PhonePe</span>
                <span class="upi-pill">Paytm</span>
                <span class="upi-pill">BHIM UPI</span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div id="tab-card" class="tab-content">
        <div class="card-form-grid">
          <label>Cardholder Name
            <input type="text" name="card_name" placeholder="Name on card" value="<?= e($me['name']) ?>">
          </label>
          <label>Card Number
            <div class="card-number-wrapper">
              <input type="text" name="card_number" id="cardNumberInput" placeholder="4532 •••• •••• 8892" maxlength="19" value="4532 8901 2345 6789">
              <span class="card-brand-badge" id="cardBrand">VISA</span>
            </div>
          </label>
          <div class="card-expiry-row">
            <label>Expiry Date
              <input type="text" name="card_expiry" placeholder="MM / YY" maxlength="5" value="12/28">
            </label>
            <label>CVV / CVC
              <input type="password" name="card_cvv" placeholder="•••" maxlength="4" value="892">
            </label>
          </div>
        </div>
      </div>

      <div id="tab-netbanking" class="tab-content">
        <p class="section-desc">Select your preferred bank to pay via Net Banking:</p>
        <div class="bank-grid">
          <label class="bank-pill">
            <input type="radio" name="bank_name" value="HDFC Bank" checked>
            <span>HDFC Bank</span>
          </label>
          <label class="bank-pill">
            <input type="radio" name="bank_name" value="State Bank of India">
            <span>SBI</span>
          </label>
          <label class="bank-pill">
            <input type="radio" name="bank_name" value="ICICI Bank">
            <span>ICICI Bank</span>
          </label>
          <label class="bank-pill">
            <input type="radio" name="bank_name" value="Axis Bank">
            <span>Axis Bank</span>
          </label>
          <label class="bank-pill">
            <input type="radio" name="bank_name" value="Kotak Mahindra Bank">
            <span>Kotak</span>
          </label>
          <label class="bank-pill">
            <input type="radio" name="bank_name" value="Punjab National Bank">
            <span>PNB</span>
          </label>
        </div>
        <label style="margin-top:1rem;">Or choose other bank:
          <select name="other_bank">
            <option value="">-- Choose other bank --</option>
            <option>Bank of Baroda</option>
            <option>Canara Bank</option>
            <option>Union Bank of India</option>
            <option>IndusInd Bank</option>
            <option>Federal Bank</option>
            <option>IDFC First Bank</option>
          </select>
        </label>
      </div>

      <div id="tab-wallet" class="tab-content">
        <p class="section-desc">Choose your wallet provider:</p>
        <div class="wallet-options">
          <label class="wallet-pill">
            <input type="radio" name="wallet_provider" value="Paytm Wallet" checked>
            <span>Paytm Wallet</span>
          </label>
          <label class="wallet-pill">
            <input type="radio" name="wallet_provider" value="PhonePe Wallet">
            <span>PhonePe</span>
          </label>
          <label class="wallet-pill">
            <input type="radio" name="wallet_provider" value="Amazon Pay">
            <span>Amazon Pay</span>
          </label>
          <label class="wallet-pill">
            <input type="radio" name="wallet_provider" value="MobiKwik">
            <span>MobiKwik</span>
          </label>
        </div>
      </div>

      <div class="security-badges">
        <span>🔒 256-Bit SSL Encrypted</span>
        <span>🛡️ 100% Secure Checkout</span>
        <span>⚡ Instant Course Access</span>
      </div>

      <button type="submit" id="paySubmitBtn" class="btn btn-checkout-submit">
        <span id="btnPayText">Pay ₹<?= number_format($price) ?> &amp; Complete Enrollment</span>
        <span id="btnPaySpinner" class="spinner" style="display:none;">⏳ Processing...</span>
      </button>

      <p class="terms-note">By clicking the button above, you agree to LearnLoop Terms of Service and Privacy Policy. All transactions are secure and encrypted.</p>
    </form>
  </div>

  <aside class="checkout-sidebar">
    <div class="card summary-card">
      <h2 class="card-heading">Order Summary</h2>

      <div class="summary-course-item">
        <img src="<?= e($course['image'] ?: 'assets/img/courses/course-' . $courseId . '.jpg') ?>" alt="<?= e($course['title']) ?>" class="summary-course-img">
        <div class="summary-course-details">
          <span class="summary-category"><?= e($course['category']) ?> · <?= e($course['level']) ?></span>
          <h3 class="summary-course-title"><?= e($course['title']) ?></h3>
          <span class="summary-instructor">By <?= e($course['instructor']) ?> · <?= e($course['duration_hours']) ?>h</span>
        </div>
      </div>

      <div class="coupon-box">
        <label for="couponInput">Have a Coupon?</label>
        <div class="coupon-input-row">
          <input type="text" id="couponInput" placeholder="Enter coupon (e.g. APEX100)" style="text-transform:uppercase;">
          <button type="button" id="applyCouponBtn" class="btn btn-small">Apply</button>
        </div>
        <div id="couponMsg" class="coupon-msg" style="display:none;"></div>
      </div>

      <div class="price-breakdown">
        <div class="price-row">
          <span>Original Price</span>
          <span class="strike">₹<?= number_format($origPrice) ?></span>
        </div>
        <div class="price-row discount-row">
          <span>Course Discount (<?= $discountPct ?>% OFF)</span>
          <span>-₹<?= number_format($discountAmount) ?></span>
        </div>
        <div class="price-row discount-row" id="couponRow" style="display:none;">
          <span>Coupon Discount</span>
          <span id="couponDiscountVal">-₹0</span>
        </div>
        <div class="price-row">
          <span>Platform &amp; Tech Fee</span>
          <span class="free-badge">FREE</span>
        </div>
        <div class="price-row">
          <span>GST / Taxes</span>
          <span>Included (0%)</span>
        </div>
        <div class="price-divider"></div>
        <div class="price-row total-row">
          <span>Total Amount</span>
          <span class="total-price" id="displayTotalPrice">₹<?= number_format($price) ?></span>
        </div>
      </div>

      <div class="guarantee-card">
        <div class="guarantee-icon">🛡️</div>
        <div>
          <strong>30-Day Money-Back Guarantee</strong>
          <p>If you're not satisfied, get a full refund within 30 days. No questions asked.</p>
        </div>
      </div>
    </div>
  </aside>
</div>

<script>
(function() {

  const tabs = document.querySelectorAll('.pay-tab');
  const tabContents = document.querySelectorAll('.tab-content');
  tabs.forEach(tab => {
    tab.addEventListener('click', () => {
      tabs.forEach(t => t.classList.remove('active'));
      tabContents.forEach(c => c.classList.remove('active'));
      tab.classList.add('active');
      const radio = tab.querySelector('input[type="radio"]');
      if (radio) radio.checked = true;
      const target = tab.dataset.target;
      const content = document.getElementById(target);
      if (content) content.classList.add('active');
    });
  });

  const cardInput = document.getElementById('cardNumberInput');
  const cardBrand = document.getElementById('cardBrand');
  if (cardInput) {
    cardInput.addEventListener('input', (e) => {
      let val = e.target.value.replace(/\D/g, '').substring(0, 16);
      let formatted = val.match(/.{1,4}/g)?.join(' ') || val;
      e.target.value = formatted;
      if (val.startsWith('4')) cardBrand.textContent = 'VISA';
      else if (val.startsWith('5')) cardBrand.textContent = 'MC';
      else if (val.startsWith('6')) cardBrand.textContent = 'RUPAY';
      else cardBrand.textContent = 'CARD';
    });
  }

  const verifyUpiBtn = document.getElementById('verifyUpiBtn');
  const upiBadge = document.getElementById('upiBadge');
  if (verifyUpiBtn && upiBadge) {
    verifyUpiBtn.addEventListener('click', () => {
      verifyUpiBtn.textContent = 'Verifying...';
      setTimeout(() => {
        verifyUpiBtn.textContent = 'Verified';
        upiBadge.style.display = 'block';
      }, 400);
    });
  }

  const couponInput = document.getElementById('couponInput');
  const applyCouponBtn = document.getElementById('applyCouponBtn');
  const couponMsg = document.getElementById('couponMsg');
  const couponRow = document.getElementById('couponRow');
  const couponDiscountVal = document.getElementById('couponDiscountVal');
  const displayTotalPrice = document.getElementById('displayTotalPrice');
  const appliedCouponInput = document.getElementById('appliedCouponInput');
  const finalAmountInput = document.getElementById('finalAmountInput');
  const btnPayText = document.getElementById('btnPayText');

  const basePrice = <?= (float)$price ?>;
  let activeCoupon = null;

  if (applyCouponBtn && couponInput) {
    applyCouponBtn.addEventListener('click', () => {
      const code = couponInput.value.trim().toUpperCase();
      if (!code) return;

      let discount = 0;
      if (code === 'APEX100' || code === 'LEARN100' || code === 'SPECIAL100') {
        discount = 100;
      } else if (code === 'APEX50' || code === 'SAVE50') {
        discount = 50;
      } else if (code === 'LEARNLOOP' || code === 'WELCOME20') {
        discount = Math.round(basePrice * 0.20);
      } else {
        couponMsg.style.display = 'block';
        couponMsg.className = 'coupon-msg error';
        couponMsg.textContent = 'Invalid coupon code. Try APEX100 or LEARNLOOP';
        return;
      }

      activeCoupon = code;
      const finalPrice = Math.max(0, basePrice - discount);
      couponRow.style.display = 'flex';
      couponDiscountVal.textContent = '-₹' + discount.toLocaleString('en-IN');
      displayTotalPrice.textContent = '₹' + finalPrice.toLocaleString('en-IN');
      btnPayText.textContent = 'Pay ₹' + finalPrice.toLocaleString('en-IN') + ' & Complete Enrollment';
      appliedCouponInput.value = code;
      finalAmountInput.value = finalPrice;

      couponMsg.style.display = 'block';
      couponMsg.className = 'coupon-msg success';
      couponMsg.textContent = 'Coupon ' + code + ' applied successfully! You saved ₹' + discount;
      couponInput.disabled = true;
      applyCouponBtn.disabled = true;
    });
  }

  const form = document.getElementById('paymentForm');
  const submitBtn = document.getElementById('paySubmitBtn');
  const btnSpinner = document.getElementById('btnPaySpinner');
  if (form && submitBtn) {
    form.addEventListener('submit', () => {
      submitBtn.disabled = true;
      btnPayText.style.display = 'none';
      btnSpinner.style.display = 'inline-block';
    });
  }
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
