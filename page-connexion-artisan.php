<?php
/**
 * WordPress utilise ce gabarit automatiquement pour une Page dont le slug est "connexion-artisan".
 * Créer une Page intitulée "Connexion Artisan" dans l'admin suffit.
 */

if ( is_user_logged_in() ) {
    wp_redirect( in_array( 'artisan', (array) wp_get_current_user()->roles, true ) ? home_url( '/espace-artisan/' ) : home_url( '/' ) );
    exit;
}

$mda_error = '';

if ( isset( $_POST['mda_login_submit'] ) ) {
    if ( ! isset( $_POST['mda_login_nonce'] ) || ! wp_verify_nonce( $_POST['mda_login_nonce'], 'mda_login_artisan' ) ) {
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
        } elseif ( ! in_array( 'artisan', (array) $user->roles, true ) ) {
            wp_logout();
            $mda_error = "Ce compte n'est pas un compte artisan.";
        } else {
            wp_redirect( home_url( '/espace-artisan/' ) );
            exit;
        }
    }
}

get_header();
?>

<section class="contact-hero">
  <span class="eyebrow">Espace professionnel</span>
  <h1>Connexion Artisan</h1>
  <p>Accédez à votre espace de gestion pour mettre à jour votre boutique et vos articles.</p>
</section>

<section class="contact-main mda-auth-main">
  <div class="contact-form-card mda-auth-card">
    <h2>Se connecter</h2>
    <p class="contact-form-intro">Réservé aux artisans du Marché des Arts.</p>

    <?php if ( $mda_error ) : ?>
      <div class="contact-error"><?php echo esc_html( $mda_error ); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <?php wp_nonce_field( 'mda_login_artisan', 'mda_login_nonce' ); ?>

      <label for="mda_log">Email</label>
      <input type="text" id="mda_log" name="mda_log" placeholder="votre@email.com" required>

      <label for="mda_pwd">Mot de passe</label>
      <input type="password" id="mda_pwd" name="mda_pwd" placeholder="••••••••" required>

      <button type="submit" name="mda_login_submit" value="1">Se connecter</button>
    </form>

    <p class="mda-auth-links">
      Pas encore de boutique ? <a href="<?php echo esc_url( home_url( '/inscription-artisan/' ) ); ?>">Créer mon compte artisan</a>
    </p>
    <p class="mda-auth-links">
      Vous êtes un client ? <a href="<?php echo esc_url( home_url( '/connexion/' ) ); ?>">Connexion client</a>
    </p>
  </div>
</section>

<?php get_footer(); ?>
