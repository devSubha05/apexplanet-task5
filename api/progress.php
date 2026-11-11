<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
$me = current_user();
if (!$me || $_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(403); echo json_encode(['error' => 'forbidden']); exit; }
csrf_check();
$lesson = (int)($_POST['lesson_id'] ?? 0);
// the lesson must belong to a course this user is enrolled in
$s = db()->prepare('SELECT l.course_id FROM lessons l JOIN enrollments e ON e.course_id=l.course_id AND e.user_id=? WHERE l.id=?');
$s->execute([$me['id'], $lesson]); $cid = $s->fetchColumn();
if (!$cid) { http_response_code(403); echo json_encode(['error' => 'not enrolled']); exit; }
$s = db()->prepare('SELECT 1 FROM lesson_progress WHERE user_id=? AND lesson_id=?'); $s->execute([$me['id'], $lesson]);
if ($s->fetchColumn()) { db()->prepare('DELETE FROM lesson_progress WHERE user_id=? AND lesson_id=?')->execute([$me['id'], $lesson]); $completed = false; }
else { db()->prepare('INSERT INTO lesson_progress (user_id,lesson_id) VALUES (?,?)')->execute([$me['id'], $lesson]); $completed = true; }
echo json_encode(['completed' => $completed] + course_progress($me['id'], (int)$cid));
