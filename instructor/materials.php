<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
$me = require_instructor();
$db = db();
$isSuperAdmin = ($me['role'] === 'admin');
$instrId = (int)$me['id'];

// Get instructor courses
if ($isSuperAdmin) {
    $myCourses = $db->query('SELECT id, title FROM courses ORDER BY id DESC')->fetchAll();
} else {
    $stmt = $db->prepare('SELECT id, title FROM courses WHERE instructor_id=? OR instructor=? ORDER BY id DESC');
    $stmt->execute([$instrId, $me['name']]);
    $myCourses = $stmt->fetchAll();
}

$cid = (int)($_GET['cid'] ?? ($myCourses[0]['id'] ?? 0));

$selectedCourse = null;
if ($cid) {
    if ($isSuperAdmin) {
        $s = $db->prepare('SELECT * FROM courses WHERE id=?');
        $s->execute([$cid]);
    } else {
        $s = $db->prepare('SELECT * FROM courses WHERE id=? AND (instructor_id=? OR instructor=?)');
        $s->execute([$cid, $instrId, $me['name']]);
    }
    $selectedCourse = $s->fetch() ?: null;
}

if (!$selectedCourse && !empty($myCourses)) {
    $cid = (int)$myCourses[0]['id'];
    $selectedCourse = $myCourses[0];
}

// Handle Study Material Upload / Delete
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $selectedCourse) {
    csrf_check();

    if (isset($_POST['delete_material'])) {
        $matId = (int)$_POST['delete_material'];
        $stmt = $db->prepare('SELECT file_path FROM materials WHERE id=? AND course_id=?');
        $stmt->execute([$matId, $cid]);
        $m = $stmt->fetch();
        if ($m) {
            $filePath = __DIR__ . '/../uploads/materials/' . $m['file_path'];
            if (file_exists($filePath)) @unlink($filePath);
            $db->prepare('DELETE FROM materials WHERE id=?')->execute([$matId]);
            flash('success', 'Study material removed.');
        }
        redirect("materials.php?cid=$cid");
    }

    $title = trim($_POST['title'] ?? '');
    $desc = trim($_POST['description'] ?? '');

    if ($title === '') {
        $errors[] = 'Please enter a title for the study material.';
    }

    if (empty($_FILES['material_file']['tmp_name'])) {
        $errors[] = 'Please choose a document or study material file to upload.';
    } else {
        $file = $_FILES['material_file'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'zip', 'doc', 'docx', 'ppt', 'pptx', 'txt', 'csv', 'py', 'js', 'html', 'css', 'sql'];

        if (!in_array($ext, $allowed, true)) {
            $errors[] = 'File format not supported. Allowed: PDF, Word, PowerPoint, ZIP, Code and Text files.';
        } elseif ($file['size'] > 25 * 1024 * 1024) {
            $errors[] = 'File exceeds maximum size limit (25MB).';
        } else {
            $safeName = 'mat-' . $cid . '-' . time() . '-' . random_int(100, 999) . '.' . $ext;
            $dest = __DIR__ . '/../uploads/materials/' . $safeName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $db->prepare('INSERT INTO materials (course_id, title, description, file_path, file_type, file_size) VALUES (?,?,?,?,?,?)')
                   ->execute([$cid, $title, $desc ?: null, $safeName, $ext, (int)$file['size']]);
                flash('success', 'Study material uploaded successfully.');
                redirect("materials.php?cid=$cid");
            } else {
                $errors[] = 'Could not save the uploaded file. Check folder permissions.';
            }
        }
    }
}

$materials = [];
if ($selectedCourse) {
    $stmt = $db->prepare('SELECT * FROM materials WHERE course_id=? ORDER BY id DESC');
    $stmt->execute([$cid]);
    $materials = $stmt->fetchAll();
}

$pageTitle = 'Upload Study Materials · Instructor Panel';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.5rem;">
  <div>
    <h1 style="margin:0 0 .3rem;">Study Materials &amp; Resources</h1>
    <p style="margin:0;color:var(--muted);">Upload downloadable lecture notes, cheat sheets, slide decks, and project templates for your students.</p>
  </div>
  <?php if (!empty($myCourses)): ?>
    <form method="get" style="display:flex;align-items:center;gap:.6rem;">
      <label style="margin:0;font-weight:700;">Select Course:</label>
      <select name="cid" onchange="this.form.submit()" style="margin:0;width:auto;height:42px;">
        <?php foreach ($myCourses as $mc): ?>
          <option value="<?= (int)$mc['id'] ?>" <?= $mc['id'] == $cid ? 'selected' : '' ?>><?= e($mc['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </form>
  <?php endif; ?>
</div>

<?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

<?php if (!$selectedCourse): ?>
  <div class="card" style="text-align:center;padding:3rem 1rem;">
    <p class="empty" style="font-size:1.15rem;margin-bottom:1.2rem;">You haven't created any courses yet.</p>
    <a href="course_form.php" class="btn">+ Create Course First</a>
  </div>
<?php else: ?>

  <div style="background:#f0faf7;border:1px solid #bce6dc;border-radius:10px;padding:.8rem 1.2rem;margin-bottom:1.6rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:.8rem;">
    <div>
      <span style="font-size:.78rem;font-weight:700;color:var(--teal);text-transform:uppercase;">Selected Course</span>
      <h3 style="margin:0;color:var(--ink);"><?= e($selectedCourse['title']) ?></h3>
    </div>
    <div style="display:flex;gap:.5rem;">
      <a href="lessons.php?cid=<?= $cid ?>" class="btn btn-small">Curriculum</a>
      <a href="assignments.php?cid=<?= $cid ?>" class="btn btn-small" style="background:#7c3aed;">Assignments</a>
    </div>
  </div>

  <div class="two-col" style="gap:1.6rem;align-items:start;">
    
    <!-- Left: Existing Materials List -->
    <div>
      <h2 style="margin:0 0 1rem;font-size:1.25rem;">Available Materials (<?= count($materials) ?>)</h2>

      <?php if (empty($materials)): ?>
        <div class="card" style="text-align:center;padding:2.5rem 1rem;">
          <p class="empty">No materials uploaded for this course yet. Use the upload form to add resources.</p>
        </div>
      <?php endif; ?>

      <?php foreach ($materials as $m): ?>
        <div class="card" style="margin-bottom:1rem;padding:1.2rem;border-left:4px solid #059669;">
          <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
            <div>
              <div style="display:flex;align-items:center;gap:.5rem;">
                <span style="font-size:1.3rem;">📄</span>
                <strong style="font-size:1.05rem;color:var(--ink);"><?= e($m['title']) ?></strong>
                <span class="tag" style="text-transform:uppercase;font-size:.72rem;background:#e6f4ea;padding:2px 6px;border-radius:4px;"><?= e($m['file_type']) ?></span>
              </div>
              <?php if (!empty($m['description'])): ?>
                <p style="margin:.4rem 0 .5rem;color:#475569;font-size:.88rem;"><?= e($m['description']) ?></p>
              <?php endif; ?>
              <small style="color:var(--muted);display:block;margin-top:4px;">
                Size: <?= round($m['file_size'] / 1024, 1) ?> KB · Uploaded: <?= date('M j, Y', strtotime($m['created_at'])) ?>
              </small>
            </div>

            <div class="actions" style="flex-shrink:0;">
              <a href="../uploads/materials/<?= e($m['file_path']) ?>" download class="btn btn-small" style="background:#059669;">Download</a>
              <form method="post" onsubmit="return confirm('Delete this study resource?');" style="display:inline;">
                <?= csrf_field() ?>
                <input type="hidden" name="delete_material" value="<?= (int)$m['id'] ?>">
                <button class="btn btn-small btn-danger" type="submit">Delete</button>
              </form>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <!-- Right: Upload Form -->
    <div class="card" style="padding:1.6rem;position:sticky;top:90px;">
      <h2 style="margin:0 0 1.2rem;font-size:1.2rem;">+ Upload New Study Material</h2>
      
      <form method="post" enctype="multipart/form-data">
        <?= csrf_field() ?>

        <label>Document / Resource Title
          <input name="title" placeholder="e.g. Complete JavaScript Cheat Sheet & Formulas" required maxlength="150">
        </label>

        <label>Brief Description (Optional)
          <textarea name="description" rows="3" placeholder="Explain what students will find in this document..."></textarea>
        </label>

        <label>Select File
          <input type="file" name="material_file" required accept=".pdf,.zip,.doc,.docx,.ppt,.pptx,.txt,.csv,.py,.js,.html,.css,.sql">
          <small style="color:var(--muted);margin-top:4px;">Accepted formats: PDF, Word, PowerPoint, ZIP, source code (Max 25MB).</small>
        </label>

        <div style="margin-top:1.4rem;">
          <button class="btn" type="submit" style="background:#059669;width:100%;">Upload Study Material</button>
        </div>
      </form>
    </div>

  </div>

<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>
