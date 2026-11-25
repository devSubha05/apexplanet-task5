<?php
$cur = basename($_SERVER['SCRIPT_NAME'] ?? '');
?>
<nav class="subnav" style="overflow-x:auto;white-space:nowrap;gap:1.5rem;padding-bottom:.8rem;margin-bottom:1.8rem;border-bottom:2px solid var(--line);">
  <a href="index.php" <?= $cur === 'index.php' ? 'style="color:var(--teal);border-bottom:3px solid var(--teal);padding-bottom:6px;font-weight:700;"' : '' ?>>📊 Dashboard</a>
  <a href="courses.php" <?= $cur === 'courses.php' || $cur === 'course_form.php' ? 'style="color:var(--teal);border-bottom:3px solid var(--teal);padding-bottom:6px;font-weight:700;"' : '' ?>>📚 My Courses</a>
  <a href="lessons.php" <?= $cur === 'lessons.php' ? 'style="color:var(--teal);border-bottom:3px solid var(--teal);padding-bottom:6px;font-weight:700;"' : '' ?>>🎬 Lessons &amp; Videos</a>
  <a href="materials.php" <?= $cur === 'materials.php' ? 'style="color:var(--teal);border-bottom:3px solid var(--teal);padding-bottom:6px;font-weight:700;"' : '' ?>>📁 Study Materials</a>
  <a href="assignments.php" <?= $cur === 'assignments.php' ? 'style="color:var(--teal);border-bottom:3px solid var(--teal);padding-bottom:6px;font-weight:700;"' : '' ?>>📝 Assignments &amp; Exams</a>
  <a href="students.php" <?= $cur === 'students.php' ? 'style="color:var(--teal);border-bottom:3px solid var(--teal);padding-bottom:6px;font-weight:700;"' : '' ?>>👥 Enrolled Students</a>
  <a href="discussions.php" <?= $cur === 'discussions.php' ? 'style="color:var(--teal);border-bottom:3px solid var(--teal);padding-bottom:6px;font-weight:700;"' : '' ?>>💬 Student Q&amp;A</a>
</nav>
