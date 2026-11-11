</main>
<footer class="site-footer"><?= e(SITE_NAME) ?> · Capstone project, ApexPlanet internship</footer>
<script>
document.querySelector('.nav-toggle').addEventListener('click', function () {
  const open = document.body.classList.toggle('nav-open');
  this.setAttribute('aria-expanded', open);
});
</script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>
