<?php
$base = '../'; require_once __DIR__ . '/../includes/functions.php'; require_admin();
$cid = (int)($_GET['course_id'] ?? 0);
$s = db()->prepare('SELECT id,title FROM courses WHERE id=?'); $s->execute([$cid]); $course = $s->fetch() ?: redirect('courses.php');
$edit = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete'])) {
        db()->prepare('DELETE FROM lessons WHERE id=? AND course_id=?')->execute([(int)$_POST['delete'], $cid]); flash('success', 'Lesson deleted.');
    } else {
        $lid = (int)($_POST['lesson_id'] ?? 0); $t = trim($_POST['title'] ?? ''); $body = trim($_POST['content'] ?? '');
        $vid = trim($_POST['video_url'] ?? ''); $pos = max(1, (int)($_POST['position'] ?? 1));
        if ($t === '' || $body === '') flash('error', 'Lesson needs a title and content.');
        elseif ($vid !== '' && !preg_match('~^https://(www\.)?(youtube\.com|youtu\.be)/~', $vid)) flash('error', 'Video link must be a YouTube https link.');
        else {
            if ($lid) db()->prepare('UPDATE lessons SET title=?,content=?,video_url=?,position=? WHERE id=? AND course_id=?')->execute([$t, $body, $vid ?: null, $pos, $lid, $cid]);
            else db()->prepare('INSERT INTO lessons (course_id,title,content,video_url,position) VALUES (?,?,?,?,?)')->execute([$cid, $t, $body, $vid ?: null, $pos]);
            flash('success', 'Lesson saved.');
        }
    }
    redirect("lessons.php?course_id=$cid");
}
if (isset($_GET['edit'])) { $s = db()->prepare('SELECT * FROM lessons WHERE id=? AND course_id=?'); $s->execute([(int)$_GET['edit'], $cid]); $edit = $s->fetch() ?: null; }
$rows = db()->prepare('SELECT * FROM lessons WHERE course_id=? ORDER BY position,id'); $rows->execute([$cid]); $rows = $rows->fetchAll();
$pageTitle = 'Lessons'; require __DIR__ . '/../includes/header.php'; require __DIR__ . '/nav.php'; ?>
<h1>Lessons: <?= e($course['title']) ?></h1>
<div class="two-col">
<section>
<?php foreach ($rows as $r): ?>
  <div class="card"><strong><?= (int)$r['position'] ?>. <?= e($r['title']) ?></strong>
    <p class="meta"><?= e(mb_strimwidth($r['content'], 0, 100, '...')) ?></p>
    <div class="actions"><a href="?course_id=<?= $cid ?>&edit=<?= (int)$r['id'] ?>">Edit</a>
    <form method="post" onsubmit="return confirm('Delete this lesson?')"><?= csrf_field() ?><button class="link-btn danger-text" name="delete" value="<?= (int)$r['id'] ?>">Delete</button></form></div></div>
<?php endforeach; if (!$rows): ?><p class="empty">No lessons yet. Add the first one.</p><?php endif; ?>
</section>
<section class="card"><h2><?= $edit ? 'Edit lesson' : 'Add a lesson' ?></h2>
<form method="post"><?= csrf_field() ?><input type="hidden" name="lesson_id" value="<?= (int)($edit['id'] ?? 0) ?>">
  <label>Title <input name="title" value="<?= e($edit['title'] ?? '') ?>" required maxlength="120"></label>
  <label>Content <textarea name="content" rows="6" required><?= e($edit['content'] ?? '') ?></textarea></label>
  <label>YouTube link (optional) <input name="video_url" value="<?= e($edit['video_url'] ?? '') ?>" placeholder="https://www.youtube.com/watch?v=..."></label>
  <label>Position <input type="number" name="position" min="1" value="<?= (int)($edit['position'] ?? count($rows) + 1) ?>"></label>
  <button class="btn">Save lesson</button> <?php if ($edit): ?><a href="?course_id=<?= $cid ?>">Cancel</a><?php endif; ?>
</form></section>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
