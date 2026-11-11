document.querySelectorAll('.done-btn').forEach(btn => {
  btn.addEventListener('click', () => {
    btn.disabled = true;
    fetch('api/progress.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-CSRF-Token': window.CSRF },
      body: new URLSearchParams({ lesson_id: btn.dataset.id })
    })
      .then(r => { if (!r.ok) throw new Error(); return r.json(); })
      .then(d => {
        btn.textContent = d.completed ? 'Completed' : 'Mark as complete';
        btn.setAttribute('aria-pressed', d.completed);
        btn.closest('.lesson').classList.toggle('is-done', d.completed);
        document.getElementById('bar').style.width = d.pct + '%';
        document.querySelector('.progress').setAttribute('aria-valuenow', d.pct);
        document.getElementById('progText').textContent = d.done + ' of ' + d.total + ' lessons done (' + d.pct + '%)';
      })
      .catch(() => alert('Could not save your progress. Check your connection and try again.'))
      .finally(() => { btn.disabled = false; });
  });
});
