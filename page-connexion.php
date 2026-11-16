<?php
/**
 * WordPress utilise ce gabarit automatiquement pour une Page dont le slug est "connexion".
 * Créer une Page intitulée "Connexion" dans l'admin suffit.
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/' ) );
    exit;
}

$mda_error = '';
$mda_redirect_to = mda_url_interne_ou_accueil( $_REQUEST['redirect_to'] ?? '' );

if ( isset( $_POST['mda_login_submit'] ) ) {
    if ( ! isset( $_POST['mda_login_nonce'] ) || ! wp_verify_nonce( $_POST['mda_login_nonce'], 'mda_login_client' ) ) {
        $mda_error = "Erreur de sécurité, merci de réessayer.";
    } else {
        $creds = array(
            'user_login'    => sanitize_text_field( $_POST['mda_log'] ?? '' ),
            'user_password' => $_POST['mda_pwd'] ?? '',
            'remember'      => true,
        );
        $user = wp_signon( $creds, false );

        if ( is_wp_error( $user ) ) {
            $mda_error = "Email ou mot de passe incorrect.";
        } elseif ( in_array( 'artisan', (array) $user->roles, true ) ) {
            wp_logout();
            $mda_error = "Ce compte est un compte artisan. Utilisez la connexion artisan.";
        } else {
            wp_redirect( mda_url_interne_ou_accueil( $_POST['mda_redirect_to'] ?? '' ) );
            exit;
        }
    }
}

get_header();
?>

<section class="contact-hero">
  <span class="eyebrow">Bienvenue</span>
  <h1>Connexion</h1>
  <p>Connectez-vous pour laisser des avis et suivre vos boutiques favorites.</p>
</section>

<section class="contact-main mda-auth-main">
  <div class="contact-form-card mda-auth-card">
    <h2>Se connecter</h2>
    <p class="contact-form-intro">Accédez à votre compte client.</p>

    <?php if ( $mda_error ) : ?>
      <div class="contact-error"><?php echo esc_html( $mda_error ); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <?php wp_nonce_field( 'mda_login_client', 'mda_login_nonce' ); ?>
      <input type="hidden" name="mda_redirect_to" value="<?php echo esc_attr( $mda_redirect_to ); ?>">

      <label for="mda_log">Email</label>
      <input type="text" id="mda_log" name="mda_log" placeholder="votre@email.com" required>

      <label for="mda_pwd">Mot de passe</label>
      <input type="password" id="mda_pwd" name="mda_pwd" placeholder="••••••••" required>

      <button type="submit" name="mda_login_submit" value="1">Se connecter</button>
    </form>

    <p class="mda-auth-links">
      Pas encore de compte ? <a href="<?php echo esc_url( home_url( '/inscription/' ) ); ?>">Créer un compte</a>
    </p>
    <p class="mda-auth-links">
      Vous êtes artisan ? <a href="<?php echo esc_url( home_url( '/connexion-artisan/' ) ); ?>">Connexion artisan</a>
    </p>
  </div>
</section>

<?php get_footer(); ?>
