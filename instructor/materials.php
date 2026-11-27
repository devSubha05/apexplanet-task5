<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
$me = require_instructor();
$db = db();
$isSuperAdmin = ($me['role'] === 'admin');
$instrId = (int)$me['id'];

if ($isSuperAdmin) {
    $myCourses = $db->query('SELECT id, title FROM courses ORDER BY id DESC')->fetchAll();
} else {
    $stmt = $db->prepare('SELECT id, title FROM courses WHERE instructor_id=? OR instructor=? ORDER BY id DESC');
    $stmt->execute([$instrId, $me['name']]);
    $myCourses = $stmt->fetchAll();
}

$cid = (int)($_GET['cid'] ?? 0);
$isAjax = isset($_GET['ajax']);

$selectedCourse = null;
if ($cid > 0) {
    if ($isSuperAdmin) {
        $s = $db->prepare('SELECT * FROM courses WHERE id=?');
        $s->execute([$cid]);
    } else {
        $s = $db->prepare('SELECT * FROM courses WHERE id=? AND (instructor_id=? OR instructor=?)');
        $s->execute([$cid, $instrId, $me['name']]);
    }
    $selectedCourse = $s->fetch() ?: null;
    if (!$selectedCourse) {
        $cid = 0;
    }
}

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
        redirect("materials.php?cid=$cid#tab-materials");
    }

    if (isset($_POST['upload_material'])) {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');

        if ($title === '') {
            $errors[] = 'Please enter a title for the study material.';
        } elseif (empty($_FILES['material_file']['tmp_name'])) {
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
                    redirect("materials.php?cid=$cid#tab-materials");
                } else {
                    $errors[] = 'Could not save the uploaded file. Check folder permissions.';
                }
            }
        }
    }

    if (isset($_POST['create_assignment'])) {
        $title = trim($_POST['title'] ?? '');
        $desc = trim($_POST['description'] ?? '');
        $type = in_array($_POST['type'] ?? '', ['assignment', 'exam'], true) ? $_POST['type'] : 'assignment';
        $maxScore = max(1, (int)($_POST['max_score'] ?? 100));
        $dueDate = !empty($_POST['due_date']) ? $_POST['due_date'] : null;

        if ($title === '' || $desc === '') {
            $errors[] = 'Task title and instructions are required.';
        } else {
            $stmt = $db->prepare('INSERT INTO assignments (course_id, title, description, type, max_score, due_date) VALUES (?,?,?,?,?,?)');
            $stmt->execute([$cid, $title, $desc, $type, $maxScore, $dueDate]);
            flash('success', ucfirst($type) . ' created successfully.');
            redirect("materials.php?cid=$cid#tab-assignments");
        }
    }

    if (isset($_POST['delete_assignment'])) {
        $assignId = (int)$_POST['delete_assignment'];
        $db->prepare('DELETE FROM assignments WHERE id=? AND course_id=?')->execute([$assignId, $cid]);
        flash('success', 'Task removed.');
        redirect("materials.php?cid=$cid#tab-assignments");
    }

    if (isset($_POST['grade_submission'])) {
        $subId = (int)$_POST['submission_id'];
        $score = (int)$_POST['score'];
        $feedback = trim($_POST['feedback'] ?? '');

        $stmt = $db->prepare('UPDATE assignment_submissions SET score=?, feedback=?, status="graded", graded_at=NOW() WHERE id=?');
        $stmt->execute([$score, $feedback, $subId]);
        flash('success', 'Submission graded successfully.');
        redirect("materials.php?cid=$cid#tab-submissions");
    }
}

$materials = [];
$assignments = [];
$submissions = [];

if ($selectedCourse) {
    $stmt = $db->prepare('SELECT * FROM materials WHERE course_id=? ORDER BY id DESC');
    $stmt->execute([$cid]);
    $materials = $stmt->fetchAll();
    $stmt = $db->prepare('SELECT a.*, 
                                 (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id=a.id) AS submission_count,
                                 (SELECT COUNT(*) FROM assignment_submissions s WHERE s.assignment_id=a.id AND s.status="submitted") AS pending_count
                          FROM assignments a 
                          WHERE a.course_id=? 
                          ORDER BY a.id DESC');
    $stmt->execute([$cid]);
    $assignments = $stmt->fetchAll();
    $stmt = $db->prepare('SELECT s.*, u.name AS student_name, u.email AS student_email,
                                 a.title AS assignment_title, a.type AS assignment_type, a.max_score
                          FROM assignment_submissions s
                          JOIN users u ON u.id = s.user_id
                          JOIN assignments a ON a.id = s.assignment_id
                          WHERE a.course_id=?
                          ORDER BY (s.status="submitted") DESC, s.submitted_at DESC');
    $stmt->execute([$cid]);
    $submissions = $stmt->fetchAll();
}

if ($isAjax) {
    require __DIR__ . '/materials_partial.php';
    exit;
}

$pageTitle = 'Study Materials & Assignments · Instructor Panel';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/nav.php';
?>

<div class="bar" style="margin-bottom:1.5rem;align-items:center;">
  <div>
    <h1 style="margin:0 0 .3rem;">Study Materials &amp; Assignments</h1>
    <p style="margin:0;color:var(--muted);font-size:1rem;">
      Upload study materials, set assignments or exams, and evaluate student work without page reloads.
    </p>
  </div>

  <?php if (!empty($myCourses)): ?>
    <div style="display:flex;align-items:center;gap:.6rem;">
      <label for="cidSelect" style="margin:0;font-weight:700;">Select Course:</label>
      <select name="cid" id="cidSelect" style="margin:0;width:auto;min-width:260px;height:42px;">
        <option value="0" <?= $cid === 0 ? 'selected' : '' ?>>-- Select a Course --</option>
        <?php foreach ($myCourses as $mc): ?>
          <option value="<?= (int)$mc['id'] ?>" <?= $mc['id'] == $cid ? 'selected' : '' ?>><?= e($mc['title']) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
  <?php endif; ?>
</div>

<?php foreach ($errors as $er): ?><div class="flash flash-error"><?= e($er) ?></div><?php endforeach; ?>

<div id="courseWorkspace" style="transition:opacity .2s ease;">
  <?php require __DIR__ . '/materials_partial.php'; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const cidSelect = document.getElementById('cidSelect');
  const workspace = document.getElementById('courseWorkspace');

  function initTabs() {
    const tabBtns = document.querySelectorAll('.course-tab-btn');
    const tabPanels = document.querySelectorAll('.course-tab-panel');

    function switchTab(targetId) {
      tabBtns.forEach(btn => {
        const isTarget = (btn.dataset.tab === targetId);
        btn.classList.toggle('active', isTarget);
        if (isTarget) {
          btn.style.color = 'var(--teal)';
          btn.style.borderBottom = '3px solid var(--teal)';
          btn.style.fontWeight = '700';
        } else {
          btn.style.color = 'var(--muted)';
          btn.style.borderBottom = '3px solid transparent';
          btn.style.fontWeight = '600';
        }
      });

      tabPanels.forEach(panel => {
        panel.style.display = (panel.id === targetId) ? 'block' : 'none';
      });
    }

    tabBtns.forEach(btn => {
      btn.addEventListener('click', function (e) {
        e.preventDefault();
        const target = this.dataset.tab;
        switchTab(target);
        if (history.replaceState) {
          const currentUrl = new URL(window.location.href);
          currentUrl.hash = target;
          history.replaceState(null, '', currentUrl.toString());
        }
      });
    });

    if (window.location.hash) {
      const h = window.location.hash.substring(1);
      if (document.getElementById(h)) {
        switchTab(h);
        return;
      }
    }
    switchTab('tab-materials');
  }

  if (cidSelect && workspace) {
    cidSelect.addEventListener('change', function () {
      const selectedId = this.value;
      const currentHash = window.location.hash || '';

      if (selectedId === '0') {
        history.pushState(null, '', 'materials.php' + currentHash);
        workspace.style.opacity = '0.5';
        fetch('materials.php?cid=0&ajax=1')
          .then(r => r.text())
          .then(html => {
            workspace.innerHTML = html;
            workspace.style.opacity = '1';
            initTabs();
          });
        return;
      }

      history.pushState(null, '', '?cid=' + encodeURIComponent(selectedId) + currentHash);
      workspace.style.opacity = '0.5';

      fetch('materials.php?cid=' + encodeURIComponent(selectedId) + '&ajax=1')
        .then(r => r.text())
        .then(html => {
          workspace.innerHTML = html;
          workspace.style.opacity = '1';
          initTabs();
        })
        .catch(() => {
      
          window.location.href = 'materials.php?cid=' + encodeURIComponent(selectedId) + currentHash;
        });
    });

    window.addEventListener('popstate', function () {
      const params = new URLSearchParams(window.location.search);
      const urlCid = params.get('cid') || '0';
      if (cidSelect) cidSelect.value = urlCid;
      workspace.style.opacity = '0.5';
      fetch('materials.php?cid=' + encodeURIComponent(urlCid) + '&ajax=1')
        .then(r => r.text())
        .then(html => {
          workspace.innerHTML = html;
          workspace.style.opacity = '1';
          initTabs();
        });
    });
  }

  initTabs();
});
</script>

<?php require __DIR__ . '/../includes/footer.php'; ?>
