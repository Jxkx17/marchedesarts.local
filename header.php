<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="viewport" content="width=device-width, initial-scale=1">
<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<?php $mda_is_artisan = mda_current_user_is_artisan(); ?>

<header class="site-header">
  <?php
  // Un artisan connecté n'a rien à faire sur la page d'accueil publique :
  // le logo le ramène vers son espace de gestion plutôt que vers l'accueil.
  $mda_logo_href = $mda_is_artisan ? home_url( '/espace-artisan/' ) : home_url( '/' );
  ?>
  <a href="<?php echo esc_url( $mda_logo_href ); ?>" class="site-logo">
    <div class="title">MARCHÉ DES ARTS</div>
    <div class="subtitle">DU BÉNIN</div>
  </a>

  <nav class="main-nav">
    <ul>
      <?php if ( $mda_is_artisan ) : ?>
        <?php $mda_tab_actif = isset( $_GET['tab'] ) && 'articles' === $_GET['tab'] ? 'articles' : 'boutique'; ?>
        <li><a href="<?php echo esc_url( home_url( '/espace-artisan/?tab=boutique' ) ); ?>" class="<?php echo is_page( 'espace-artisan' ) && 'boutique' === $mda_tab_actif ? 'active' : ''; ?>">Ma Boutique</a></li>
        <li><a href="<?php echo esc_url( home_url( '/espace-artisan/?tab=articles' ) ); ?>" class="<?php echo is_page( 'espace-artisan' ) && 'articles' === $mda_tab_actif ? 'active' : ''; ?>">Mes Articles</a></li>
      <?php else : ?>
        <li><a href="<?php echo home_url('/'); ?>" class="<?php echo is_front_page() ? 'active' : ''; ?>">Accueil</a></li>
        <li><a href="<?php echo esc_url( get_post_type_archive_link('boutique') ?: home_url('/boutiques') ); ?>" class="<?php echo is_post_type_archive('boutique') || is_singular('boutique') ? 'active' : ''; ?>">Boutiques</a></li>
        <li><a href="<?php echo home_url('/plan'); ?>" class="<?php echo is_page('plan') ? 'active' : ''; ?>">Plan du site</a></li>
        <li><a href="<?php echo home_url('/contact'); ?>" class="<?php echo is_page('contact') ? 'active' : ''; ?>">Contact</a></li>
        <?php if ( ! is_user_logged_in() ) : // "Espace Artisan" n'a pas lieu d'être pour un client déjà connecté. ?>
        <li>
          <a href="<?php echo esc_url( home_url( '/connexion-artisan/' ) ); ?>"
             class="<?php echo is_page( array( 'connexion-artisan', 'inscription-artisan' ) ) ? 'active' : ''; ?>">Espace Artisan</a>
        </li>
        <?php endif; ?>
      <?php endif; ?>
    </ul>
  </nav>

  <div class="header-actions">
    <?php if ( ! $mda_is_artisan && ! is_post_type_archive( 'boutique' ) && ! is_search() ) : ?>
    <form class="header-search" role="search" method="get" action="<?php echo home_url('/'); ?>">
      <input type="text" name="s" placeholder="Entrez un mot clé">
      <button type="submit" class="header-search-btn" aria-label="Rechercher">
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <circle cx="11" cy="11" r="7"></circle>
          <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
        </svg>
      </button>
    </form>
    <?php endif; ?>

    <?php if ( is_user_logged_in() ) :
        $mda_u    = wp_get_current_user();
        $mda_shop = $mda_is_artisan ? mda_get_current_artisan_shop() : null;
        ?>
        <div class="header-account-wrap">
          <button type="button" class="header-account" id="mda-account-toggle" aria-haspopup="true" aria-expanded="false"
                  title="<?php echo esc_attr( 'Bonjour ' . $mda_u->display_name ); ?>">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1F2E22" stroke-width="2">
              <circle cx="12" cy="8" r="4"></circle>
              <path d="M4 21c0-4 4-7 8-7s8 3 8 7"></path>
            </svg>
          </button>

          <div class="mda-account-panel" id="mda-account-panel" hidden>
            <div class="mda-account-panel-head">
              <span class="mda-account-avatar"><?php echo esc_html( mb_strtoupper( mb_substr( $mda_u->display_name, 0, 1 ) ) ); ?></span>
              <div>
                <strong><?php echo esc_html( $mda_u->display_name ); ?></strong>
                <span class="mda-account-role"><?php echo $mda_is_artisan ? 'Compte Artisan' : 'Compte Client'; ?></span>
              </div>
            </div>

            <ul class="mda-account-panel-info">
              <li><?php echo esc_html( $mda_u->user_email ); ?></li>
              <?php if ( $mda_is_artisan && $mda_shop ) :
                  $mda_note = mda_get_shop_rating( $mda_shop->ID );
                  ?>
                <li><strong><?php echo esc_html( $mda_shop->post_title ); ?></strong></li>
                <li>Note boutique : <?php echo esc_html( $mda_note ); ?>/5</li>
              <?php elseif ( $mda_is_artisan ) : ?>
                <li>Boutique non encore créée</li>
              <?php endif; ?>
            </ul>

            <?php if ( $mda_is_artisan ) : ?>
              <a href="<?php echo esc_url( home_url( '/espace-artisan/' ) ); ?>" class="mda-account-panel-link">Gérer ma boutique</a>
            <?php endif; ?>

            <a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>" class="mda-account-panel-logout">Se déconnecter</a>
          </div>
        </div>
    <?php else : ?>
        <a href="<?php echo esc_url( mda_account_icon_link() ); ?>" class="header-account" aria-label="Connexion" title="Connexion">
          <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#1F2E22" stroke-width="2">
            <circle cx="12" cy="8" r="4"></circle>
            <path d="M4 21c0-4 4-7 8-7s8 3 8 7"></path>
          </svg>
        </a>
    <?php endif; ?>
  </div>
</header>

<?php if ( is_user_logged_in() ) : ?>
<script>
(function(){
  var toggle = document.getElementById('mda-account-toggle');
  var panel  = document.getElementById('mda-account-panel');
  if (!toggle || !panel) return;

  function close(){
    panel.hidden = true;
    toggle.setAttribute('aria-expanded', 'false');
  }
  function open(){
    panel.hidden = false;
    toggle.setAttribute('aria-expanded', 'true');
  }

  toggle.addEventListener('click', function(e){
    e.stopPropagation();
    panel.hidden ? open() : close();
  });
  document.addEventListener('click', function(e){
    if (!panel.hidden && !panel.contains(e.target) && e.target !== toggle) {
      close();
    }
  });
  document.addEventListener('keydown', function(e){
    if (e.key === 'Escape') close();
  });
})();
</script>
<?php endif; ?>
