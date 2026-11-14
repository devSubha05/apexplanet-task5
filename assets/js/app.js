(function () {
  const q = document.getElementById('q'), cat = document.getElementById('cat'),
        level = document.getElementById('level'), out = document.getElementById('results'),
        count = document.getElementById('count'),
        toggleWrap = document.getElementById('toggleWrap'),
        seeMoreBtn = document.getElementById('seeMoreBtn'),
        seeMoreText = document.getElementById('seeMoreText'),
        seeMoreIcon = document.getElementById('seeMoreIcon');
  let timer, ctrl;
  let isExpanded = false;
  let allCards = [];

  function el(tag, cls, text) {
    const n = document.createElement(tag);
    if (cls) n.className = cls;
    if (text !== undefined) n.textContent = text;
    return n;
  }

  function getTwoRowsLimit() {
    try {
      const template = window.getComputedStyle(out).gridTemplateColumns;
      if (template && template !== 'none') {
        const cols = template.trim().split(/\s+/).length;
        if (cols > 0) return cols * 2;
      }
    } catch (e) {}
    const width = out.clientWidth || window.innerWidth;
    const cols = Math.max(1, Math.floor(width / 270));
    return cols * 2;
  }

  function updateCardsDisplay() {
    if (!allCards.length) {
      if (toggleWrap) toggleWrap.style.display = 'none';
      return;
    }

    const limit = getTwoRowsLimit();

    if (allCards.length <= limit) {
      allCards.forEach(c => c.classList.remove('is-hidden'));
      if (toggleWrap) toggleWrap.style.display = 'none';
      return;
    }

    if (toggleWrap) toggleWrap.style.display = 'flex';

    if (isExpanded) {
      allCards.forEach(c => c.classList.remove('is-hidden'));
      if (seeMoreText) seeMoreText.textContent = 'See Less';
      if (seeMoreIcon) seeMoreIcon.textContent = '↑';
      if (seeMoreBtn) seeMoreBtn.setAttribute('aria-expanded', 'true');
    } else {
      allCards.forEach((c, idx) => {
        if (idx < limit) {
          c.classList.remove('is-hidden');
        } else {
          c.classList.add('is-hidden');
        }
      });
      const hiddenCount = allCards.length - limit;
      if (seeMoreText) seeMoreText.textContent = 'See More (' + hiddenCount + ' more)';
      if (seeMoreIcon) seeMoreIcon.textContent = '↓';
      if (seeMoreBtn) seeMoreBtn.setAttribute('aria-expanded', 'false');
    }
  }

  function render(rows) {
    out.replaceChildren();
    allCards = [];
    isExpanded = false;
    count.textContent = rows.length ? rows.length + (rows.length === 1 ? ' course' : ' courses') : '';
    if (!rows.length) {
      if (toggleWrap) toggleWrap.style.display = 'none';
      out.append(el('p', 'empty', 'No courses match. Try a different word or clear a filter.'));
      return;
    }
    rows.forEach(c => {
      const a = el('a', 'card course-card');
      a.href = 'course.php?id=' + encodeURIComponent(c.id);
      a.append(el('span', 'tag', c.category + ' · ' + c.level),
               el('h2', '', c.title),
               el('p', '', c.description),
               el('p', 'meta', c.lessons + ' lessons · ' + c.duration_hours + ' h · ' + c.learners + ' enrolled'));
      out.append(a);
      allCards.push(a);
    });

    updateCardsDisplay();
  }

  if (seeMoreBtn) {
    seeMoreBtn.addEventListener('click', () => {
      isExpanded = !isExpanded;
      updateCardsDisplay();
      if (!isExpanded) {
        out.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  }

  window.addEventListener('resize', () => {
    if (!isExpanded && allCards.length > 0) {
      updateCardsDisplay();
    }
  });

  function load() {
    if (ctrl) ctrl.abort();
    ctrl = new AbortController();
    const p = new URLSearchParams({ q: q.value.trim(), cat: cat.value, level: level.value });
    fetch('api/courses.php?' + p, { signal: ctrl.signal })
      .then(r => { if (!r.ok) throw new Error(); return r.json(); })
      .then(render)
      .catch(err => {
        if (err.name !== 'AbortError') {
          if (toggleWrap) toggleWrap.style.display = 'none';
          out.replaceChildren(el('p', 'empty', 'Could not load courses. Refresh and try again.'));
        }
      });
  }

  q.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 250); }); // debounce
  cat.addEventListener('change', load);
  level.addEventListener('change', load);
  load();
})();
