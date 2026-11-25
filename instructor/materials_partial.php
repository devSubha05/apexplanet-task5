<?php
/**
 * Partial file rendered inside #courseWorkspace
 * Reusable for initial page load and seamless AJAX updates without page reload
 */
?>
<?php if (!$selectedCourse): ?>
  <div class="card" style="text-align:center;padding:3.5rem 1.5rem;border-radius:14px;background:#ffffff;border:1.5px dashed #cbd5e1;">
    <div style="font-size:3.4rem;margin-bottom:1rem;">📁</div>
    <h2 style="margin:0 0 .5rem;color:var(--ink);">Please Select a Course</h2>
    <p style="color:var(--muted);max-width:55ch;margin:0 auto 1.8rem;font-size:1.05rem;line-height:1.6;">
      Choose one of your courses from the dropdown above to manage its downloadable study materials, set assignments or tests, and evaluate student work.
    </p>
    <?php if (!empty($myCourses)): ?>
      <div style="display:flex;justify-content:center;gap:.6rem;flex-wrap:wrap;max-width:700px;margin:0 auto;">
        <?php foreach ($myCourses as $mc): ?>
          <button type="button" class="btn btn-small" onclick="document.getElementById('cidSelect').value='<?= (int)$mc['id'] ?>'; document.getElementById('cidSelect').dispatchEvent(new Event('change'));" style="background:#f1f5f9;color:var(--ink);border:1px solid #cbd5e1;font-weight:600;">
            <?= e($mc['title']) ?>
          </button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

<?php else: ?>

  <!-- Selected Course Action Header -->
  <div style="background:linear-gradient(135deg,#f0faf7 0%,#fdfbf7 100%);border:1px solid #bce6dc;border-radius:12px;padding:1rem 1.4rem;margin-bottom:1.6rem;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;">
    <div>
      <span style="font-size:.78rem;font-weight:700;color:var(--teal);text-transform:uppercase;letter-spacing:.04em;">Selected Course</span>
      <h3 style="margin:.1rem 0 0;color:var(--ink);font-size:1.3rem;"><?= e($selectedCourse['title']) ?></h3>
    </div>
    <div style="display:flex;gap:.5rem;flex-wrap:wrap;">
      <a href="lessons.php?cid=<?= $cid ?>" class="btn btn-small" style="background:#475569;">🎬 Lessons &amp; Videos</a>
      <a href="../course.php?id=<?= $cid ?>" target="_blank" class="btn btn-small" style="background:#0e6b63;">👁️ Student View ↗</a>
    </div>
  </div>

  <!-- Seamless Interactive Tab Buttons (NO PAGE RELOAD) -->
  <div class="course-tabs" style="display:flex;gap:.5rem;margin:1.2rem 0 1.6rem;border-bottom:2px solid var(--line);overflow-x:auto;padding-bottom:.4rem;">
    <button type="button" class="course-tab-btn active" data-tab="tab-materials" style="color:var(--teal);border-bottom:3px solid var(--teal);font-weight:700;">
      📁 Study Materials (<?= count($materials) ?>)
    </button>
    <button type="button" class="course-tab-btn" data-tab="tab-assignments" style="color:var(--muted);border-bottom:3px solid transparent;font-weight:600;">
      📝 Assignments &amp; Tests (<?= count($assignments) ?>)
    </button>
    <button type="button" class="course-tab-btn" data-tab="tab-submissions" style="color:var(--muted);border-bottom:3px solid transparent;font-weight:600;">
      📥 Student Submissions (<?= count($submissions) ?>)
    </button>
  </div>

  <!-- ================= TAB 1: STUDY MATERIALS ================= -->
  <div id="tab-materials" class="course-tab-panel active">
    <div class="two-col" style="gap:1.6rem;align-items:start;">
      
      <!-- Materials List -->
      <div>
        <h2 style="margin:0 0 1rem;font-size:1.25rem;">Available Materials (<?= count($materials) ?>)</h2>

        <?php if (empty($materials)): ?>
          <div class="card" style="text-align:center;padding:2.5rem 1rem;">
            <p class="empty">No study materials uploaded for this course yet. Use the upload form on the right.</p>
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
                <form method="post" onsubmit="return confirm('Delete this study material?');" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="delete_material" value="<?= (int)$m['id'] ?>">
                  <button class="btn btn-small btn-danger" type="submit">Delete</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Upload Form -->
      <div class="card" style="padding:1.6rem;position:sticky;top:90px;">
        <h2 style="margin:0 0 1.2rem;font-size:1.2rem;">+ Upload New Study Material</h2>
        
        <form method="post" enctype="multipart/form-data">
          <?= csrf_field() ?>
          <input type="hidden" name="upload_material" value="1">

          <label>Document / Resource Title
            <input name="title" placeholder="e.g. Complete JavaScript Cheat Sheet &amp; Code Starter" required maxlength="150">
          </label>

          <label>Brief Description (Optional)
            <textarea name="description" rows="3" placeholder="Explain what students will find in this document..."></textarea>
          </label>

          <label>Select File
            <input type="file" name="material_file" required accept=".pdf,.zip,.doc,.docx,.ppt,.pptx,.txt,.csv,.py,.js,.html,.css,.sql">
            <small style="color:var(--muted);margin-top:4px;">Accepted: PDF, Word, PowerPoint, ZIP, source code (Max 25MB).</small>
          </label>

          <div style="margin-top:1.4rem;">
            <button class="btn" type="submit" style="background:#059669;width:100%;">Upload Study Material</button>
          </div>
        </form>
      </div>

    </div>
  </div>

  <!-- ================= TAB 2: ASSIGNMENTS & TESTS ================= -->
  <div id="tab-assignments" class="course-tab-panel" style="display:none;">
    <div class="two-col" style="gap:1.6rem;align-items:start;">
      
      <!-- Assignments List -->
      <div>
        <h2 style="margin:0 0 1rem;font-size:1.25rem;">Active Assignments &amp; Tests (<?= count($assignments) ?>)</h2>

        <?php if (empty($assignments)): ?>
          <div class="card" style="text-align:center;padding:2.5rem 1rem;">
            <p class="empty">No assignments or tests set for this course yet. Use the form on the right.</p>
          </div>
        <?php endif; ?>

        <?php foreach ($assignments as $a): ?>
          <div class="card" style="margin-bottom:1rem;padding:1.2rem;border-left:4px solid <?= $a['type'] === 'exam' ? '#dc2626' : '#7c3aed' ?>;">
            <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;">
              <div>
                <div style="display:flex;align-items:center;gap:.6rem;flex-wrap:wrap;">
                  <span class="tag" style="background:<?= $a['type'] === 'exam' ? '#fee2e2;color:#991b1b' : '#ede9fe;color:#5b21b6' ?>;font-weight:800;font-size:.75rem;padding:2px 8px;border-radius:4px;text-transform:uppercase;">
                    <?= e($a['type']) ?>
                  </span>
                  <strong style="font-size:1.1rem;color:var(--ink);"><?= e($a['title']) ?></strong>
                </div>

                <p style="margin:.5rem 0 .6rem;color:#334155;font-size:.9rem;line-height:1.5;">
                  <?= nl2br(e($a['description'])) ?>
                </p>

                <div style="display:flex;gap:1.2rem;font-size:.82rem;color:var(--muted);flex-wrap:wrap;">
                  <span>🎯 Max Score: <strong><?= (int)$a['max_score'] ?> pts</strong></span>
                  <span>📅 Due: <?= $a['due_date'] ? date('M j, Y g:i A', strtotime($a['due_date'])) : 'No deadline' ?></span>
                  <span>📥 Submissions: <strong><?= (int)$a['submission_count'] ?></strong></span>
                  <?php if ((int)$a['pending_count'] > 0): ?>
                    <span style="color:#d97706;font-weight:700;">⏳ <?= (int)$a['pending_count'] ?> pending grading</span>
                  <?php endif; ?>
                </div>
              </div>

              <div class="actions" style="flex-shrink:0;">
                <form method="post" onsubmit="return confirm('Delete this assignment/test and all student submissions?');" style="display:inline;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="delete_assignment" value="<?= (int)$a['id'] ?>">
                  <button class="btn btn-small btn-danger" type="submit">Delete</button>
                </form>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- Set New Assignment / Test Form -->
      <div class="card" style="padding:1.6rem;position:sticky;top:90px;">
        <h2 style="margin:0 0 1.2rem;font-size:1.2rem;">+ Set New Assignment or Test</h2>

        <form method="post">
          <?= csrf_field() ?>
          <input type="hidden" name="create_assignment" value="1">

          <label>Type of Task
            <select name="type">
              <option value="assignment">Homework Assignment / Project</option>
              <option value="exam">Quiz / Test / Exam</option>
            </select>
          </label>

          <label>Task Title
            <input name="title" placeholder="e.g. Build a Responsive Landing Page" required maxlength="150">
          </label>

          <label>Instructions &amp; Requirements
            <textarea name="description" rows="5" placeholder="Detail the questions, specifications, or submission instructions..." required></textarea>
          </label>

          <div class="two-col" style="gap:1rem;">
            <label>Max Score (Points)
              <input type="number" name="max_score" min="10" max="1000" value="100" required>
            </label>

            <label>Due Date (Optional)
              <input type="datetime-local" name="due_date">
            </label>
          </div>

          <div style="margin-top:1.4rem;">
            <button class="btn" type="submit" style="background:#7c3aed;width:100%;">Create Task</button>
          </div>
        </form>
      </div>

    </div>
  </div>

  <!-- ================= TAB 3: STUDENT SUBMISSIONS & GRADING ================= -->
  <div id="tab-submissions" class="course-tab-panel" style="display:none;">
    <h2 style="margin:0 0 .5rem;font-size:1.3rem;">📥 Student Submissions &amp; Grading Evaluation</h2>
    <p style="color:var(--muted);margin:0 0 1.5rem;">Review student submissions, evaluate code and answers, provide written feedback, and award points.</p>

    <?php if (empty($submissions)): ?>
      <div class="card" style="text-align:center;padding:2.5rem 1rem;">
        <p class="empty">No student submissions received for this course yet.</p>
      </div>
    <?php endif; ?>

    <?php foreach ($submissions as $sub): ?>
      <div class="card" style="margin-bottom:1.5rem;padding:1.5rem;border-left:4px solid <?= $sub['status'] === 'graded' ? '#059669' : '#d97706' ?>;">
        <div style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:1rem;padding-bottom:.8rem;border-bottom:1px solid var(--line);">
          <div>
            <div style="display:flex;align-items:center;gap:.6rem;">
              <strong style="font-size:1.15rem;color:var(--ink);"><?= e($sub['student_name']) ?></strong>
              <span style="font-size:.85rem;color:var(--muted);">(<?= e($sub['student_email']) ?>)</span>
              <span class="tag" style="background:<?= $sub['status'] === 'graded' ? '#e6f4ea;color:#0e6b63' : '#fef3c7;color:#92400e' ?>;font-weight:700;padding:2px 8px;border-radius:99px;font-size:.78rem;text-transform:uppercase;">
                <?= $sub['status'] === 'graded' ? '✓ Graded' : '⏳ Awaiting Grade' ?>
              </span>
            </div>
            <div style="margin-top:4px;font-size:.9rem;color:var(--teal);font-weight:600;">
              Task: <?= e($sub['assignment_title']) ?> <span style="color:var(--muted);">(<?= e($sub['assignment_type']) ?>)</span>
            </div>
          </div>

          <div style="text-align:right;">
            <small style="color:var(--muted);display:block;">Submitted: <?= date('M j, Y g:i A', strtotime($sub['submitted_at'])) ?></small>
            <?php if ($sub['status'] === 'graded'): ?>
              <span style="font-size:1.1rem;font-weight:800;color:#059669;display:block;margin-top:4px;">
                Score: <?= (int)$sub['score'] ?> / <?= (int)$sub['max_score'] ?>
              </span>
            <?php endif; ?>
          </div>
        </div>

        <!-- Student Answer Text -->
        <?php if (!empty($sub['submission_text'])): ?>
          <div style="margin-bottom:1.2rem;background:#f8fafc;border:1px solid #e2e8f0;border-radius:8px;padding:1rem;">
            <strong style="font-size:.82rem;text-transform:uppercase;color:var(--muted);display:block;margin-bottom:.4rem;">Student Response / Code Submission:</strong>
            <pre style="margin:0;white-space:pre-wrap;font-family:inherit;font-size:.92rem;line-height:1.5;color:#1e293b;"><?= e($sub['submission_text']) ?></pre>
          </div>
        <?php endif; ?>

        <!-- Student Attached File -->
        <?php if (!empty($sub['file_path'])): ?>
          <div style="margin-bottom:1.2rem;">
            <a href="../uploads/submissions/<?= e($sub['file_path']) ?>" download class="btn btn-small" style="background:#2563eb;">
              📥 Download Student Attached File (<?= e($sub['file_path']) ?>)
            </a>
          </div>
        <?php endif; ?>

        <!-- Teacher Grading Form -->
        <div style="background:#fafafa;border:1px solid #e5e5e5;border-radius:10px;padding:1.2rem;margin-top:1rem;">
          <h4 style="margin:0 0 .8rem;font-size:1rem;color:var(--ink);">👨‍🏫 Teacher Evaluation &amp; Grading</h4>
          <form method="post" style="display:flex;flex-direction:column;gap:.8rem;">
            <?= csrf_field() ?>
            <input type="hidden" name="grade_submission" value="1">
            <input type="hidden" name="submission_id" value="<?= (int)$sub['id'] ?>">

            <div class="two-col" style="gap:1rem;">
              <label style="margin:0;">Award Score (Max: <?= (int)$sub['max_score'] ?> pts)
                <input type="number" name="score" min="0" max="<?= (int)$sub['max_score'] ?>" value="<?= $sub['score'] !== null ? (int)$sub['score'] : '' ?>" placeholder="e.g. 95" required>
              </label>

              <label style="margin:0;">Teacher Feedback &amp; Suggestions
                <input name="feedback" value="<?= e($sub['feedback'] ?? '') ?>" placeholder="e.g. Great code architecture, remember to validate edge cases!">
              </label>
            </div>

            <div>
              <button class="btn btn-small" type="submit" style="background:#0e6b63;">
                <?= $sub['status'] === 'graded' ? 'Update Grade & Feedback' : 'Submit Grade & Notify Student' ?>
              </button>
            </div>
          </form>
        </div>

      </div>
    <?php endforeach; ?>
  </div>

<?php endif; ?>
