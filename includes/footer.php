</main>
<footer class="site-footer"><?= e(SITE_NAME) ?> · Capstone project, ApexPlanet internship</footer>
<script>
(function () {
  const toggle = document.getElementById('navToggle');
  const nav = document.getElementById('nav');
  const backdrop = document.getElementById('navBackdrop');
  const closeBtn = document.getElementById('navClose');

  function openNav() {
    document.body.classList.add('nav-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'true');
  }

  function closeNav() {
    document.body.classList.remove('nav-open');
    if (toggle) toggle.setAttribute('aria-expanded', 'false');
  }

  if (toggle) {
    toggle.addEventListener('click', function (e) {
      e.stopPropagation();
      if (document.body.classList.contains('nav-open')) {
        closeNav();
      } else {
        openNav();
      }
    });
  }

  if (closeBtn) closeBtn.addEventListener('click', closeNav);
  if (backdrop) backdrop.addEventListener('click', closeNav);

  document.addEventListener('keydown', function (e) {
    if (e.key === 'Escape' && document.body.classList.contains('nav-open')) {
      closeNav();
    }
  });

  if (nav) {
    nav.querySelectorAll('a').forEach(function (a) {
      a.addEventListener('click', function () {
        if (window.innerWidth <= 768) closeNav();
      });
    });
  }
})();
</script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
