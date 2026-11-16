<?php
/**
 * WordPress utilise ce gabarit automatiquement pour une Page dont le slug est "contact".
 * Créer une Page intitulée "Contact" dans l'admin suffit — aucun choix de modèle requis.
 */

$mda_contact_sent  = false;
$mda_contact_error = '';

// La page Contact est réservée aux visiteurs connectés (client ou artisan) ;
// les autres onglets (Accueil, Boutiques, Plan du site) restent librement
// accessibles sans compte.
if ( isset( $_POST['mda_contact_submit'] ) && is_user_logged_in() ) {
    if ( ! isset( $_POST['mda_contact_nonce'] ) || ! wp_verify_nonce( $_POST['mda_contact_nonce'], 'mda_contact_form' ) ) {
        $mda_contact_error = "Erreur de sécurité, merci de réessayer.";
    } elseif ( ! empty( $_POST['mda_hp'] ) ) {
        // Piège à robots (honeypot) rempli : on ignore silencieusement.
        $mda_contact_sent = true;
    } else {
        $nom     = sanitize_text_field( $_POST['mda_nom'] ?? '' );
        $email   = sanitize_email( $_POST['mda_email'] ?? '' );
        $sujet   = sanitize_text_field( $_POST['mda_sujet'] ?? '' );
        $message = sanitize_textarea_field( $_POST['mda_message'] ?? '' );

        if ( ! $nom || ! is_email( $email ) || ! $message ) {
            $mda_contact_error = "Merci de renseigner votre nom, un email valide et un message.";
        } else {
            $to      = get_option( 'admin_email' );
            $subject = '[Marché des Arts] ' . ( $sujet ?: 'Nouveau message de contact' );
            $body    = "Nom : $nom\nEmail : $email\nSujet : $sujet\n\nMessage :\n$message";
            $headers = array( 'Reply-To: ' . $nom . ' <' . $email . '>' );

            if ( wp_mail( $to, $subject, $body, $headers ) ) {
                $mda_contact_sent = true;
            } else {
                $mda_contact_error = "L'envoi a échoué. Réessayez ou écrivez directement à " . esc_html( $to ) . ".";
            }
        }
    }
}

get_header();
?>

<?php if ( ! is_user_logged_in() ) : ?>

  <section class="contact-hero">
    <span class="eyebrow">Accès réservé</span>
    <h1>Contactez-nous</h1>
    <p>La page de contact est réservée aux visiteurs disposant d'un compte. Connectez-vous ou créez un compte pour joindre l'équipe SImAU.</p>
  </section>

  <section class="contact-main mda-auth-main">
    <div class="contact-form-card mda-auth-card">
      <h2>Connexion requise</h2>
      <p class="contact-form-intro">Un compte (client ou artisan) est nécessaire pour envoyer un message à notre équipe.</p>

      <div class="mda-gate-actions">
        <a href="<?php echo esc_url( home_url( '/connexion/?redirect_to=' . urlencode( home_url( '/contact/' ) ) ) ); ?>" class="btn-contact-artisan">Se connecter</a>
        <a href="<?php echo esc_url( home_url( '/inscription/' ) ); ?>" class="btn-avis-outline">Créer un compte</a>
      </div>

      <p class="mda-auth-links">
        Vous êtes artisan ? <a href="<?php echo esc_url( home_url( '/connexion-artisan/' ) ); ?>">Connexion artisan</a>
      </p>
    </div>
  </section>

<?php else : ?>

<section class="contact-hero">
  <span class="eyebrow">À votre écoute</span>
  <h1>Contactez-nous</h1>
  <p>Une question sur une œuvre, un artisan ou une commande&nbsp;? L'équipe SImAU est là pour vous accompagner dans votre découverte de l'artisanat béninois.</p>
</section>

<section class="contact-main">
  <div class="contact-info">
    <div class="contact-card">
      <span class="contact-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D4681A" stroke-width="2"><path d="M12 21s-7-6.5-7-11a7 7 0 0 1 14 0c0 4.5-7 11-7 11z"></path><circle cx="12" cy="10" r="2.5"></circle></svg>
      </span>
      <div>
        <h3>Adresse</h3>
        <p>Esplanade de l'Artisanat, Lot 14<br>Zone Commerciale, Cotonou, Bénin</p>
      </div>
    </div>

    <div class="contact-card">
      <span class="contact-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D4681A" stroke-width="2"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="M3 7l9 6 9-6"></path></svg>
      </span>
      <div>
        <h3>Email</h3>
        <p>contact@marche-des-arts.bj</p>
      </div>
    </div>

    <div class="contact-card">
      <span class="contact-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D4681A" stroke-width="2"><path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4 2h3a2 2 0 0 1 2 1.7c.1.9.3 1.8.6 2.7a2 2 0 0 1-.5 2.1L7.9 9.7a16 16 0 0 0 6 6l1.2-1.2a2 2 0 0 1 2.1-.5c.9.3 1.8.5 2.7.6A2 2 0 0 1 22 16.9z"></path></svg>
      </span>
      <div>
        <h3>Téléphone</h3>
        <p>+229 21 00 00 00</p>
      </div>
    </div>

    <div class="contact-card">
      <span class="contact-icon">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#D4681A" stroke-width="2"><circle cx="12" cy="12" r="9"></circle><path d="M12 7v5l3 3"></path></svg>
      </span>
      <div>
        <h3>Horaires</h3>
        <p>Lun - Ven : 09:00 - 18:00<br>Samedi : 10:00 - 16:00</p>
      </div>
    </div>
  </div>

  <div class="contact-form-card">
    <h2>Envoyez-nous un message</h2>
    <p class="contact-form-intro">Remplissez le formulaire ci-dessous. Nous vous répondrons rapidement.</p>

    <?php if ( $mda_contact_sent ) : ?>
      <div class="contact-success">Message envoyé. Merci, nous revenons vers vous rapidement.</div>
    <?php else : ?>
      <?php if ( $mda_contact_error ) : ?>
        <div class="contact-error"><?php echo esc_html( $mda_contact_error ); ?></div>
      <?php endif; ?>

      <form method="post" action="">
        <?php wp_nonce_field( 'mda_contact_form', 'mda_contact_nonce' ); ?>
        <input type="text" name="mda_hp" value="" style="position:absolute;left:-9999px;" tabindex="-1" autocomplete="off">

        <label for="mda_nom">Nom complet</label>
        <input type="text" id="mda_nom" name="mda_nom" placeholder="Votre nom" required
               value="<?php echo esc_attr( wp_get_current_user()->display_name ); ?>">

        <label for="mda_email">Email</label>
        <input type="email" id="mda_email" name="mda_email" placeholder="votre@email.com" required
               value="<?php echo esc_attr( wp_get_current_user()->user_email ); ?>">

        <label for="mda_sujet">Sujet</label>
        <input type="text" id="mda_sujet" name="mda_sujet" placeholder="Comment pouvons-nous vous aider ?">

        <label for="mda_message">Message</label>
        <textarea id="mda_message" name="mda_message" placeholder="Racontez-nous tout..." required></textarea>

        <button type="submit" name="mda_contact_submit" value="1">Envoyer le message</button>
      </form>
    <?php endif; ?>
  </div>
</section>

<section class="contact-map">
  <iframe
    src="https://www.openstreetmap.org/export/embed.html?bbox=2.395%2C6.345%2C2.445%2C6.385&layer=mapnik&marker=6.365%2C2.42"
    loading="lazy"
    referrerpolicy="no-referrer-when-downgrade"
    title="Localisation du Marché des Arts de Cotonou"></iframe>
  <a class="map-external" href="https://www.openstreetmap.org/?mlat=6.365&mlon=2.42#map=15/6.365/2.42" target="_blank" rel="noopener" aria-label="Ouvrir la carte en plein écran">
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="#101828" stroke-width="2"><path d="M7 17L17 7M9 7h8v8"></path></svg>
  </a>
  <div class="map-pin-card">
    <strong>Marché des Arts de Cotonou</strong>
    <span>Cotonou, Bénin</span>
  </div>
</section>

<section class="contact-faq" id="faq">
  <h2>Foire aux questions</h2>
  <p class="faq-intro">Retrouvez les réponses aux questions les plus fréquentes de la communauté SImAU.</p>

  <div class="faq-list">
    <details class="faq-item">
      <summary>Comment acquérir un kiosque ?<span class="faq-toggle">+</span></summary>
      <div class="faq-answer">Contactez la SImAU via ce formulaire ou par téléphone pour connaître les emplacements disponibles et les conditions d'attribution au Marché des Arts de Cotonou.</div>
    </details>
    <details class="faq-item">
      <summary>Puis-je commander une œuvre sur mesure auprès de la SImAU ?<span class="faq-toggle">+</span></summary>
      <div class="faq-answer">Oui. Décrivez votre projet dans le formulaire ci-dessus en précisant la boutique ou l'artisan concerné, nous transmettrons votre demande directement.</div>
    </details>
    <details class="faq-item">
      <summary>Que fait la SImAU ?<span class="faq-toggle">+</span></summary>
      <div class="faq-answer">La Société Immobilière et d'Aménagement Urbain valorise les talents artisanaux locaux et gère l'espace du Marché des Arts, connectant les artisans béninois à un public élargi.</div>
    </details>
  </div>
</section>

<?php endif; ?>

<?php get_footer(); ?>