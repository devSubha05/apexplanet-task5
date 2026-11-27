<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
$me = require_instructor();
$db = db();
$isSuperAdmin = ($me['role'] === 'admin');
$id = (int)($_GET['id'] ?? 0);

$c = [
    'title' => '',
    'description' => '',
    'category_id' => 0,
    'level' => 'Beginner',
    'instructor' => $me['name'],
    'duration_hours' => '4.0',
    'price' => '499.00',
    'original_price' => '1999.00',
    'image' => '',
    'badge' => 'Bestseller'
];

if ($id) {
    if ($isSuperAdmin) {
        $s = $db->prepare('SELECT * FROM courses WHERE id=?');
        $s->execute([$id]);
    } else {
        $s = $db->prepare('SELECT * FROM courses WHERE id=? AND (instructor_id=? OR instructor=?)');
        $s->execute([$id, $me['id'], $me['name']]);
    }
    $c = $s->fetch() ?: redirect('courses.php');
}

$cats = $db->query('SELECT id,name FROM categories ORDER BY name')->fetchAll();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');
    $catId = (int)($_POST['category_id'] ?? 0);
    $level = $_POST['level'] ?? 'Beginner';
    $instructorName = trim($_POST['instructor'] ?? $me['name']);
    $duration = $_POST['duration_hours'] ?? '1.0';
    $price = $_POST['price'] ?? '499.00';
    $origPrice = $_POST['original_price'] ?? '1999.00';
    $badge = trim($_POST['badge'] ?? 'Bestseller');
    $imagePath = $c['image'] ?? '';

    if ($title === '' || mb_strlen($title) > 120) $errors[] = 'Title is required (up to 120 characters).';
    if ($desc === '') $errors[] = 'Please add a comprehensive description for the course.';
    if (!in_array($catId, array_column($cats, 'id'))) $errors[] = 'Pick a valid category.';
    if (!in_array($level, ['Beginner', 'Intermediate', 'Advanced'], true)) $errors[] = 'Pick a valid difficulty level.';
    if (!is_numeric($duration) || $duration <= 0) $errors[] = 'Duration must be a positive number of hours.';
    if (!is_numeric($price) || $price < 0) $errors[] = 'Price must be a valid number.';
    if (!is_numeric($origPrice) || $origPrice < 0) $errors[] = 'Original price must be a valid number.';

    if (!empty($_FILES['cover_image']['tmp_name'])) {
        $file = $_FILES['cover_image'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $errors[] = 'Cover image must be a JPG, PNG, or WebP file.';
        } elseif ($file['size'] > 4 * 1024 * 1024) {
            $errors[] = 'Cover image file is too large (max 4MB).';
        } else {
            $newFileName = 'course-custom-' . time() . '-' . random_int(100, 999) . '.' . $ext;
            $dest = __DIR__ . '/../assets/img/courses/' . $newFileName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $imagePath = 'assets/img/courses/' . $newFileName;
            } else {
                $errors[] = 'Failed to upload image. Please try again.';
            }
        }
    }

    if (!$errors) {
        if ($id) {
            $stmt = $db->prepare('UPDATE courses SET title=?, description=?, category_id=?, level=?, instructor=?, duration_hours=?, price=?, original_price=?, image=?, badge=? WHERE id=?');
            $stmt->execute([$title, $desc, $catId, $level, $instructorName, $duration, $price, $origPrice, $imagePath, $badge, $id]);
            flash('success', 'Course updated successfully.');
        } else {
            $stmt = $db->prepare('INSERT INTO courses (title, description, category_id, instructor_id, level, instructor, duration_hours, price, original_price, image, badge) VALUES (?,?,?,?,?,?,?,?,?,?,?)');
            $stmt->execute([$title, $desc, $catId, (int)$me['id'], $level, $instructorName, $duration, $price, $origPrice, $imagePath, $badge]);
            $id = (int)$db->lastInsertId();
            flash('success', 'Course created! Now you can upload lesson videos and attach study materials.');
        }
        redirect("lessons.php?cid=$id");
    }
}

$pageTitle = ($id ? 'Edit Course' : 'Create Course') . ' · Instructor Panel';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.2rem;">
  <div>
    <h1 style="margin:0 0 .2rem;"><?= $id ? 'Edit Course: ' . e($c['title']) : 'Create New Course' ?></h1>
    <p style="margin:0;color:var(--muted);">Set course details, syllabus overview, and pricing.</p>
  </div>
  <a href="courses.php" class="btn btn-small" style="background:#64748b;">← Back to Courses</a>
</div>

<?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

<section class="card" style="max-width:760px;margin:0 auto 2.5rem;padding:2rem;">
  <form method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <label>Course Title
      <input name="title" value="<?= e($c['title']) ?>" placeholder="e.g. Master Full-Stack Web Development with React & Node" required maxlength="120">
    </label>

    <label>Description &amp; Learning Outcomes
      <textarea name="description" rows="5" placeholder="Detail what students will learn, prerequisites, and skills acquired..." required><?= e($c['description']) ?></textarea>
    </label>

    <div class="two-col" style="gap:1.2rem;">
      <label>Category
        <select name="category_id">
          <?php foreach ($cats as $k): ?>
            <option value="<?= (int)$k['id'] ?>" <?= $k['id'] == $c['category_id'] ? 'selected' : '' ?>><?= e($k['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Skill Level
        <select name="level">
          <?php foreach (['Beginner', 'Intermediate', 'Advanced'] as $lv): ?>
            <option value="<?= $lv ?>" <?= $lv === $c['level'] ? 'selected' : '' ?>><?= $lv ?></option>
          <?php endforeach; ?>
        </select>
      </label>
    </div>

    <div class="two-col" style="gap:1.2rem;">
      <label>Instructor Display Name
        <input name="instructor" value="<?= e($c['instructor']) ?>" required maxlength="80">
      </label>

      <label>Estimated Total Duration (Hours)
        <input type="number" name="duration_hours" step="0.5" min="0.5" value="<?= e($c['duration_hours']) ?>" required>
      </label>
    </div>

    <div class="two-col" style="gap:1.2rem;">
      <label>Selling Price (₹)
        <input type="number" name="price" step="1" min="0" value="<?= e($c['price']) ?>" required>
      </label>

      <label>Original / Strikethrough Price (₹)
        <input type="number" name="original_price" step="1" min="0" value="<?= e($c['original_price']) ?>" required>
      </label>
    </div>

    <div class="two-col" style="gap:1.2rem;">
      <label>Promotional Badge
        <select name="badge">
          <?php foreach (['Bestseller', 'Highest Rated', 'Hot & New', 'Popular', 'Specialization'] as $b): ?>
            <option value="<?= $b ?>" <?= ($c['badge'] ?? '') === $b ? 'selected' : '' ?>><?= $b ?></option>
          <?php endforeach; ?>
        </select>
      </label>

      <label>Course Cover Image
        <input type="file" name="cover_image" accept="image/jpeg,image/png,image/webp">
        <?php if (!empty($c['image'])): ?>
          <small style="color:var(--muted);margin-top:4px;">Current image: <code><?= e($c['image']) ?></code></small>
        <?php endif; ?>
      </label>
    </div>

    <div style="margin-top:1.5rem;display:flex;gap:1rem;align-items:center;">
      <button class="btn" type="submit" style="font-size:1.05rem;padding:.75rem 1.8rem;"><?= $id ? 'Save Changes' : 'Create Course & Continue' ?></button>
      <a href="courses.php" style="color:var(--muted);text-decoration:none;">Cancel</a>
    </div>
  </form>
</section>

<?php require __DIR__ . '/../includes/footer.php'; ?>
