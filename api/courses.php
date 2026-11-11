<?php
require_once __DIR__ . '/../includes/functions.php';
header('Content-Type: application/json; charset=utf-8');
$q = trim($_GET['q'] ?? ''); $cat = (int)($_GET['cat'] ?? 0); $level = $_GET['level'] ?? '';
$sql = 'SELECT c.id,c.title,c.description,c.level,c.instructor,c.duration_hours,cat.name AS category,
        (SELECT COUNT(*) FROM lessons l WHERE l.course_id=c.id) AS lessons,
        (SELECT COUNT(*) FROM enrollments e WHERE e.course_id=c.id) AS learners
        FROM courses c JOIN categories cat ON cat.id=c.category_id WHERE 1=1';
$args = [];
if ($q !== '') { $sql .= ' AND (c.title LIKE ? OR c.description LIKE ?)'; $like = '%' . addcslashes($q, '%_\\') . '%'; array_push($args, $like, $like); }
if ($cat > 0) { $sql .= ' AND c.category_id=?'; $args[] = $cat; }
if (in_array($level, ['Beginner', 'Intermediate', 'Advanced'], true)) { $sql .= ' AND c.level=?'; $args[] = $level; }
$sql .= ' ORDER BY c.created_at DESC, c.id DESC LIMIT 60';
$s = db()->prepare($sql); $s->execute($args);
$rows = $s->fetchAll();
foreach ($rows as &$r) { $r['description'] = mb_strimwidth($r['description'], 0, 120, '...'); }
echo json_encode($rows);
