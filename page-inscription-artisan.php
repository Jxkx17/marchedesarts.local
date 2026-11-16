<?php
/**
 * WordPress utilise ce gabarit automatiquement pour une Page dont le slug est "inscription-artisan".
 * Créer une Page intitulée "Inscription Artisan" dans l'admin suffit.
 */

if ( is_user_logged_in() ) {
    wp_redirect( home_url( '/' ) );
    exit;
}

$mda_error = '';

if ( isset( $_POST['mda_register_submit'] ) ) {
    if ( ! isset( $_POST['mda_register_nonce'] ) || ! wp_verify_nonce( $_POST['mda_register_nonce'], 'mda_register_artisan' ) ) {
        $mda_error = "Erreur de sécurité, merci de réessayer.";
    } else {
        $nom       = sanitize_text_field( $_POST['mda_nom'] ?? '' );
        $boutique  = sanitize_text_field( $_POST['mda_boutique'] ?? '' );
        $email     = sanitize_email( $_POST['mda_email'] ?? '' );
        $password  = $_POST['mda_password'] ?? '';

        if ( ! $nom || ! $boutique || ! is_email( $email ) ) {
            $mda_error = "Merci de renseigner votre nom, le nom de votre boutique et un email valide.";
        } elseif ( email_exists( $email ) ) {
            $mda_error = "Cet email est déjà associé à un compte.";
        } elseif ( strlen( $password ) < 8 ) {
            $mda_error = "Le mot de passe doit contenir au moins 8 caractères.";
        } else {
            $user_id = wp_insert_user( array(
                'user_login'   => $email,
                'user_email'   => $email,
                'user_pass'    => $password,
                'display_name'=> $nom,
                'role'         => 'artisan',
            ) );

            if ( is_wp_error( $user_id ) ) {
                $mda_error = $user_id->get_error_message();
            } else {
                $shop_id = wp_insert_post( array(
                    'post_type'      => 'boutique',
                    'post_title'     => $boutique,
                    'post_status'    => 'draft',
                    'post_author'    => $user_id,
                    'comment_status' => 'open',
                    'ping_status'    => 'closed',
                ) );

                wp_signon( array( 'user_login' => $email, 'user_password' => $password ), false );
                wp_redirect( home_url( '/espace-artisan/?bienvenue=1' ) );
                exit;
            }
        }
    }
}

get_header();
?>

<section class="contact-hero">
  <span class="eyebrow">Devenez artisan partenaire</span>
  <h1>Créer mon compte artisan</h1>
  <p>Ouvrez votre boutique en ligne : photos, articles, position sur le plan du marché.</p>
</section>

<section class="contact-main mda-auth-main">
  <div class="contact-form-card mda-auth-card">
    <h2>Inscription artisan</h2>
    <p class="contact-form-intro">Votre boutique sera modifiable à tout moment depuis votre espace.</p>

    <?php if ( $mda_error ) : ?>
      <div class="contact-error"><?php echo esc_html( $mda_error ); ?></div>
    <?php endif; ?>

    <form method="post" action="">
      <?php wp_nonce_field( 'mda_register_artisan', 'mda_register_nonce' ); ?>

      <label for="mda_nom">Votre nom</label>
      <input type="text" id="mda_nom" name="mda_nom" placeholder="Votre nom" required
             value="<?php echo isset( $_POST['mda_nom'] ) ? esc_attr( $_POST['mda_nom'] ) : ''; ?>">

      <label for="mda_boutique">Nom de la boutique</label>
      <input type="text" id="mda_boutique" name="mda_boutique" placeholder="Ex : Atelier Agbodjè" required
             value="<?php echo isset( $_POST['mda_boutique'] ) ? esc_attr( $_POST['mda_boutique'] ) : ''; ?>">

      <label for="mda_email">Email</label>
      <input type="email" id="mda_email" name="mda_email" placeholder="votre@email.com" required
             value="<?php echo isset( $_POST['mda_email'] ) ? esc_attr( $_POST['mda_email'] ) : ''; ?>">

      <label for="mda_password">Mot de passe</label>
      <input type="password" id="mda_password" name="mda_password" placeholder="8 caractères minimum" required minlength="8">

      <button type="submit" name="mda_register_submit" value="1">Créer mon compte artisan</button>
    </form>

    <p class="mda-auth-links">
      Déjà un compte ? <a href="<?php echo esc_url( home_url( '/connexion-artisan/' ) ); ?>">Se connecter</a>
    </p>
  </div>
</section>

<?php get_footer(); ?>
