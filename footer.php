<footer class="site-footer">
  <div class="footer-grid">
    <div class="footer-brand">
      <div class="title">MARCHÉ DES ARTS</div>
      <div class="subtitle">DU BÉNIN</div>
      <p>Votre destination pour l'artisanat authentique béninois. La SImAU valorise les talents locaux et connecte le monde à l'art du Bénin.</p>
    </div>

    <div>
      <h4>CONTACTEZ-NOUS</h4>
      <ul>
        <li>contact@marche-des-arts.bj</li>
        <li>+229 00 00 00 00</li>
        <li>Cotonou, Bénin</li>
      </ul>
    </div>

    <?php if ( ! mda_current_user_is_artisan() ) : ?>
    <div>
      <h4>NAVIGATION</h4>
      <ul>
        <li><a href="<?php echo home_url('/'); ?>">Accueil</a></li>
        <li><a href="<?php echo home_url('/boutiques'); ?>">Boutiques</a></li>
        <li><a href="<?php echo home_url('/contact'); ?>">Contact</a></li>
      </ul>
    </div>
    <?php endif; ?>

    <div>
      <h4>SUPPORT</h4>
      <ul>
        <li><a href="<?php echo home_url('/contact/#faq'); ?>">Aide & FAQ</a></li>
        <li><a href="<?php echo home_url('/confidentialite'); ?>">Confidentialité</a></li>
        <li><a href="<?php echo home_url('/mentions-legales'); ?>">Mentions légales & Cookies</a></li>
      </ul>
    </div>
  </div>

  <div class="footer-social">
    <a href="#" aria-label="Instagram">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1F2E22" stroke-width="2"><rect x="2" y="2" width="20" height="20" rx="5"></rect><circle cx="12" cy="12" r="4"></circle><circle cx="17.5" cy="6.5" r="0.8" fill="#1F2E22"></circle></svg>
    </a>
    <a href="#" aria-label="Facebook">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1F2E22" stroke-width="2"><path d="M18 2h-3a5 5 0 0 0-5 5v3H7v4h3v8h4v-8h3l1-4h-4V7a1 1 0 0 1 1-1h3z"></path></svg>
    </a>
    <a href="#" aria-label="Twitter">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1F2E22" stroke-width="2"><path d="M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3z"></path></svg>
    </a>
    <a href="#" aria-label="Youtube">
      <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#1F2E22" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="4"></rect><polygon points="10,9 15,12 10,15"></polygon></svg>
    </a>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>