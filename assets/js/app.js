(function () {
  if ('scrollRestoration' in history) {
    history.scrollRestoration = 'manual';
  }
  if (window.location.hash) {
    history.replaceState(null, '', window.location.pathname + window.location.search);
  }

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

    if (!toggleWrap || out.dataset.expanded === 'true') {
      allCards.forEach(c => c.classList.remove('is-hidden'));
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
      const card = el('div', 'card course-card udemy-style-card');
      const courseUrl = 'course.php?id=' + encodeURIComponent(c.id);
      const checkoutUrl = 'checkout.php?course_id=' + encodeURIComponent(c.id);
      const imgPath = c.image || ('assets/img/courses/course-' + c.id + '.jpg');

      // 1. Thumbnail top banner
      const thumb = el('div', 'udemy-thumb-wrap');
      const img = document.createElement('img');
      img.src = imgPath;
      img.alt = c.title;
      img.className = 'udemy-thumb-img';
      img.loading = 'lazy';
      img.onerror = function() { this.src = 'assets/img/courses/course-1.jpg'; };

      const premiumBadge = el('span', 'udemy-badge-premium', '👑 Premium');
      const wishBtn = el('button', 'udemy-wishlist-btn', '♡');
      wishBtn.type = 'button';
      wishBtn.title = 'Add to wishlist';
      wishBtn.setAttribute('aria-label', 'Wishlist');
      wishBtn.addEventListener('click', (e) => {
        e.preventDefault();
        e.stopPropagation();
        wishBtn.classList.toggle('active');
        wishBtn.textContent = wishBtn.classList.contains('active') ? '♥' : '♡';
      });

      thumb.append(img, premiumBadge, wishBtn);

      // Clicking thumb navigates to course
      thumb.addEventListener('click', () => { window.location.href = courseUrl; });

      // 2. Card Content Body
      const body = el('div', 'udemy-card-body');

      const titleLink = el('a', 'udemy-title-link');
      titleLink.href = courseUrl;
      const title = el('h3', 'udemy-card-title', c.title);
      titleLink.append(title);

      const instructor = el('p', 'udemy-card-instructor', (c.instructor && c.instructor !== 'Admin' ? c.instructor : (c.category + ' Academy')));

      // Rating & Badges row
      const ratingRow = el('div', 'udemy-rating-row');
      const badgeText = c.badge || (Number(c.rating || 4.7) >= 4.8 ? 'Bestseller' : 'Highest Rated');
      const badgeCls = badgeText.toLowerCase().replace(/[^a-z0-9]/g, '-');
      const badge = el('span', 'udemy-tag-badge udemy-tag-' + badgeCls, badgeText);
      const courseType = el('span', 'udemy-tag-type', 'Course');
      const stars = el('span', 'udemy-rating-val', '★ ' + Number(c.rating || 4.7).toFixed(1));
      const ratingsCount = el('span', 'udemy-ratings-count', Number(c.rating_count || 2400).toLocaleString('en-IN') + ' ratings');
      ratingRow.append(badge, courseType, stars, ratingsCount);

      // Footer Pricing & Add to Cart button
      const footerRow = el('div', 'udemy-footer-row');
      const priceCol = el('div', 'udemy-price-col');
      const curPrice = el('div', 'udemy-price-current', '₹' + Number(c.price || 499).toFixed(2));
      const origPrice = el('div', 'udemy-price-orig', '₹' + Number(c.original_price || 1999).toFixed(2));
      priceCol.append(curPrice, origPrice);

      const cartBtn = el('a', 'udemy-btn-cart', 'Add to cart');
      cartBtn.href = checkoutUrl;

      footerRow.append(priceCol, cartBtn);

      body.append(titleLink, instructor, ratingRow, footerRow);
      card.append(thumb, body);

      out.append(card);
      allCards.push(card);
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

  // Read URL query params (from header search bar or category sub-strip)
  const urlParams = new URLSearchParams(window.location.search);
  const headerSearchInput = document.getElementById('headerSearchInput');

  if (urlParams.has('q')) {
    const initialQ = urlParams.get('q');
    if (q) q.value = initialQ;
    if (headerSearchInput) headerSearchInput.value = initialQ;
  }
  if (urlParams.has('cat') && cat) cat.value = urlParams.get('cat');
  if (urlParams.has('level') && level) level.value = urlParams.get('level');

  // Synchronize header search bar with homepage courses filter
  if (headerSearchInput && q) {
    headerSearchInput.addEventListener('input', () => {
      q.value = headerSearchInput.value;
      clearTimeout(timer);
      timer = setTimeout(load, 250);
    });

    q.addEventListener('input', () => {
      headerSearchInput.value = q.value;
    });

    const headerForm = headerSearchInput.closest('form');
    if (headerForm) {
      headerForm.addEventListener('submit', (e) => {
        if (window.location.pathname.endsWith('courses.php')) {
          e.preventDefault();
          q.value = headerSearchInput.value;
          load();
          const target = document.getElementById('results');
          if (target) {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        }
      });
    }
  }

  // Handle Explore categories chip clicks
  const chips = document.querySelectorAll('.cat-chip');
  chips.forEach(chip => {
    chip.addEventListener('click', () => {
      const isAlreadyActive = chip.classList.contains('active');
      chips.forEach(c => c.classList.remove('active'));

      if (isAlreadyActive) {
        if (cat) cat.value = '0';
        if (q) q.value = '';
        load();
        return;
      }

      chip.classList.add('active');
      const targetCat = chip.dataset.cat;
      const targetQuery = chip.dataset.query;

      if (targetCat && cat) {
        for (let opt of cat.options) {
          if (opt.text.toLowerCase().includes(targetCat.toLowerCase())) {
            cat.value = opt.value;
            break;
          }
        }
        if (q) q.value = '';
      } else if (targetQuery && q) {
        if (cat) cat.value = '0';
        q.value = targetQuery;
      } else {
        if (cat) cat.value = '0';
        if (q) q.value = '';
      }

      load();
      const targetElem = document.getElementById('results');
      if (targetElem) {
        targetElem.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  q.addEventListener('input', () => { clearTimeout(timer); timer = setTimeout(load, 250); }); // debounce
  cat.addEventListener('change', () => {
    chips.forEach(c => c.classList.remove('active'));
    load();
  });
  level.addEventListener('change', load);
  load();
})();
