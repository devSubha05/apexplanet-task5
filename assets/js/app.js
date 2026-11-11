(function () {
  const q = document.getElementById('q'), cat = document.getElementById('cat'),
        level = document.getElementById('level'), out = document.getElementById('results'),
        count = document.getElementById('count');
  let timer, ctrl;

  function el(tag, cls, text) {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== undefined) n.textContent = text;   // textContent keeps user data from becoming HTML
    return n;
  }

  function render(rows) {
    out.replaceChildren();
    count.textContent = rows.length ? rows.length + (rows.length === 1 ? ' course' : ' courses') : '';
    if (!rows.length) { out.append(el('p', 'empty', 'No courses match. Try a different word or clear a filter.')); return; }
    rows.forEach(c => {
      const a = el('a', 'card course-card'); a.href = 'course.php?id=' + encodeURIComponent(c.id);
      a.append(el('span', 'tag', c.category + ' · ' + c.level),
               el('h2', '', c.title),
               el('p', '', c.description),
               el('p', 'meta', c.lessons + ' lessons · ' + c.duration_hours + ' h · ' + c.learners + ' enrolled'));
      out.append(a);
    });
  }

  function load() {
    if (ctrl) ctrl.abort();
    ctrl = new AbortController();
    const p = new URLSearchParams({ q: q.value.trim(), cat: cat.value, level: level.value });
    fetch('api/courses.php?' + p, { signal: ctrl.signal })
      .then(r => { if (!r.ok) throw new Error(); return r.json(); })
      .then(render)
      .catch(err => { if (err.name !== 'AbortError') { out.replaceChildren(el('p', 'empty', 'Could not load courses. Refresh and try again.')); } });
  }

  q.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 250); }); // debounce
  cat.addEventListener('change', load);
  level.addEventListener('change', load);
  load();
})();
