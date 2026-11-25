<?php
$base = '../';
require_once __DIR__ . '/../includes/functions.php';
$me = require_instructor();

$cid = (int)($_GET['cid'] ?? 0);
$gradeId = (int)($_GET['grade_id'] ?? 0);

if ($gradeId > 0) {
    $db = db();
    $stmt = $db->prepare('SELECT a.course_id FROM assignment_submissions s JOIN assignments a ON a.id = s.assignment_id WHERE s.id=?');
    $stmt->execute([$gradeId]);
    $subCid = (int)$stmt->fetchColumn();
    if ($subCid > 0) {
        header("Location: materials.php?cid={$subCid}#tab-submissions");
        exit;
    }
}

$targetUrl = 'materials.php' . ($cid > 0 ? '?cid=' . $cid : '') . '#tab-assignments';
header('Location: ' . $targetUrl);
exit;
