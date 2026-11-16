<?php
/**
 * WordPress utilise ce gabarit automatiquement pour une Page dont le slug est "espace-artisan".
 * Créer une Page intitulée "Espace Artisan" dans l'admin suffit.
 *
 * Deux onglets :
 *  - Ma Boutique : profil, description, position (plan schématique = kiosque,
 *    la carte réelle est calculée automatiquement à partir du même numéro),
 *    photo principale, + section "Mon compte" en pied d'onglet.
 *  - Mes Articles : galerie photos / articles exposés (CPT "oeuvre").
 */

if ( ! is_user_logged_in() ) {
    wp_redirect( home_url( '/connexion-artisan/' ) );
    exit;
}
if ( ! in_array( 'artisan', (array) wp_get_current_user()->roles, true ) ) {
    wp_redirect( home_url( '/' ) );
    exit;
}

$mda_user  = wp_get_current_user();
$mda_shop  = mda_get_current_artisan_shop();
$mda_error = '';
$mda_ok    = '';

// ---------- Enregistrement "Ma Boutique" ----------
if ( isset( $_POST['mda_save_boutique'] ) ) {
    if ( ! isset( $_POST['mda_boutique_nonce'] ) || ! wp_verify_nonce( $_POST['mda_boutique_nonce'], 'mda_espace_boutique' ) ) {
        $mda_error = "Erreur de sécurité, merci de réessayer.";
    } else {
        $kiosque = isset( $_POST['mda_kiosque'] ) ? absint( $_POST['mda_kiosque'] ) : 0;
        $shop_id_existant = $mda_shop ? $mda_shop->ID : 0;

        if ( $kiosque && ( $kiosque < 1 || $kiosque > 225 ) ) {
            $mda_error = "Le numéro de kiosque doit être compris entre 1 et 225.";
        } elseif ( $kiosque && mda_kiosque_deja_pris( $kiosque, $shop_id_existant ) ) {
            $mda_error = "Ce numéro de kiosque est déjà attribué à une autre boutique.";
        } else {
            $postarr = array(
                'post_type'      => 'boutique',
                'post_title'     => sanitize_text_field( $_POST['mda_titre'] ?? '' ),
                'post_content'   => wp_kses_post( $_POST['mda_histoire'] ?? '' ),
                'post_author'    => $mda_user->ID,
                'post_status'    => 'publish',
                'comment_status' => 'open',
                'ping_status'    => 'closed',
            );
            if ( $shop_id_existant ) {
                $postarr['ID'] = $shop_id_existant;
            }
            $shop_id = wp_insert_post( $postarr );

            if ( is_wp_error( $shop_id ) || ! $shop_id ) {
                $mda_error = "Impossible d'enregistrer la boutique.";
            } else {
                update_post_meta( $shop_id, '_mda_description', sanitize_textarea_field( $_POST['mda_description'] ?? '' ) );
                update_post_meta( $shop_id, '_mda_metier', sanitize_text_field( $_POST['mda_metier'] ?? '' ) );
                update_post_meta( $shop_id, '_mda_experience', sanitize_text_field( $_POST['mda_experience'] ?? '' ) );
                update_post_meta( $shop_id, '_mda_statut', sanitize_text_field( $_POST['mda_statut'] ?? '' ) );
                update_post_meta( $shop_id, '_mda_whatsapp', sanitize_text_field( $_POST['mda_whatsapp'] ?? '' ) );
                update_post_meta( $shop_id, '_mda_emplacement', sanitize_text_field( $_POST['mda_emplacement'] ?? '' ) );
                if ( $kiosque ) {
                    update_post_meta( $shop_id, '_mda_kiosque', $kiosque );
                }
                if ( '' === get_post_meta( $shop_id, '_mda_visites', true ) ) {
                    update_post_meta( $shop_id, '_mda_visites', 0 );
                }

                if ( ! empty( $_POST['mda_type_article'] ) ) {
                    wp_set_post_terms( $shop_id, array( (int) $_POST['mda_type_article'] ), 'type_article' );
                }

                $thumb_id = mda_handle_single_upload( 'mda_photo_principale', $shop_id );
                if ( $thumb_id ) {
                    set_post_thumbnail( $shop_id, $thumb_id );
                }
                $img1_id = mda_handle_single_upload( 'mda_histoire_img1', $shop_id );
                if ( $img1_id ) {
                    update_post_meta( $shop_id, '_mda_histoire_img1', wp_get_attachment_url( $img1_id ) );
                }
                $img2_id = mda_handle_single_upload( 'mda_histoire_img2', $shop_id );
                if ( $img2_id ) {
                    update_post_meta( $shop_id, '_mda_histoire_img2', wp_get_attachment_url( $img2_id ) );
                }

                wp_redirect( home_url( '/espace-artisan/?tab=boutique&enregistre=1' ) );
                exit;
            }
        }
    }
}

// ---------- Enregistrement "Mon compte" ----------
if ( isset( $_POST['mda_save_compte'] ) ) {
    if ( ! isset( $_POST['mda_compte_nonce'] ) || ! wp_verify_nonce( $_POST['mda_compte_nonce'], 'mda_espace_compte' ) ) {
        $mda_error = "Erreur de sécurité, merci de réessayer.";
    } else {
        $update = array(
            'ID'           => $mda_user->ID,
            'display_name' => sanitize_text_field( $_POST['mda_display_name'] ?? $mda_user->display_name ),
            'user_email'   => sanitize_email( $_POST['mda_compte_email'] ?? $mda_user->user_email ),
        );
        if ( ! empty( $_POST['mda_compte_password'] ) ) {
            if ( strlen( $_POST['mda_compte_password'] ) < 8 ) {
                $mda_error = "Le nouveau mot de passe doit contenir au moins 8 caractères.";
            } else {
                $update['user_pass'] = $_POST['mda_compte_password'];
            }
        }
        if ( ! $mda_error ) {
            wp_update_user( $update );
            wp_redirect( home_url( '/espace-artisan/?tab=boutique&enregistre=1' ) );
            exit;
        }
    }
}

// ---------- Ajout d'un article exposé ----------
if ( isset( $_POST['mda_add_oeuvre'] ) && $mda_shop ) {
    if ( ! isset( $_POST['mda_oeuvre_nonce'] ) || ! wp_verify_nonce( $_POST['mda_oeuvre_nonce'], 'mda_espace_oeuvre' ) ) {
        $mda_error = "Erreur de sécurité, merci de réessayer.";
    } else {
        $titre_oeuvre = sanitize_text_field( $_POST['mda_oeuvre_titre'] ?? '' );
        if ( ! $titre_oeuvre ) {
            $mda_error = "Le nom de l'article est obligatoire.";
        } else {
            $oeuvre_id = wp_insert_post( array(
                'post_type'   => 'oeuvre',
                'post_title'  => $titre_oeuvre,
                'post_status' => 'publish',
                'post_author' => $mda_user->ID,
            ) );
            if ( ! is_wp_error( $oeuvre_id ) && $oeuvre_id ) {
                update_post_meta( $oeuvre_id, '_mda_boutique_id', $mda_shop->ID );
                if ( ! empty( $_POST['mda_oeuvre_materiau'] ) ) {
                    wp_set_post_terms( $oeuvre_id, array( (int) $_POST['mda_oeuvre_materiau'] ), 'materiau' );
                }
                $photo_id = mda_handle_single_upload( 'mda_oeuvre_photo', $oeuvre_id );
                if ( $photo_id ) {
                    set_post_thumbnail( $oeuvre_id, $photo_id );
                }
                $mda_ok = "Article ajouté.";
            }
        }
        wp_redirect( home_url( '/espace-artisan/?tab=articles&ajoute=1' ) );
        exit;
    }
}

// ---------- Suppression d'un article exposé ----------
if ( isset( $_GET['supprimer_oeuvre'], $_GET['_wpnonce'] ) && $mda_shop ) {
    $oeuvre_id = absint( $_GET['supprimer_oeuvre'] );
    if ( wp_verify_nonce( $_GET['_wpnonce'], 'mda_delete_oeuvre_' . $oeuvre_id )
         && (int) get_post_meta( $oeuvre_id, '_mda_boutique_id', true ) === $mda_shop->ID ) {
        wp_delete_post( $oeuvre_id, true );
    }
    wp_redirect( home_url( '/espace-artisan/?tab=articles&supprime=1' ) );
    exit;
}

$mda_tab = isset( $_GET['tab'] ) && 'articles' === $_GET['tab'] ? 'articles' : 'boutique';

$mda_types    = get_terms( array( 'taxonomy' => 'type_article', 'hide_empty' => false ) );
$mda_materiaux = get_terms( array( 'taxonomy' => 'materiau', 'hide_empty' => false ) );

$mda_current_type = $mda_shop ? wp_get_post_terms( $mda_shop->ID, 'type_article', array( 'fields' => 'ids' ) ) : array();
$mda_stats = $mda_shop ? mda_boutique_rating_stats( $mda_shop->ID ) : array( 'average' => '0.0', 'total' => 0 );

$mda_oeuvres = $mda_shop ? get_posts( array(
    'post_type'      => 'oeuvre',
    'posts_per_page' => -1,
    'meta_key'       => '_mda_boutique_id',
    'meta_value'     => $mda_shop->ID,
    'orderby'        => 'date',
    'order'          => 'DESC',
) ) : array();

get_header();
?>

<div class="breadcrumb breadcrumb-fiche breadcrumb-espace">
  <span>Espace Artisan</span> &rsaquo;
  <span><?php echo 'articles' === $mda_tab ? 'Mes Articles' : 'Ma Boutique'; ?></span>
</div>

<section class="contact-hero mda-espace-hero">
  <span class="eyebrow">Espace Artisan</span>
  <h1>Bonjour <?php echo esc_html( $mda_user->display_name ); ?></h1>
  <p><?php echo $mda_shop ? esc_html( 'Vous gérez la boutique "' . $mda_shop->post_title . '".' ) : 'Créez votre boutique pour commencer.'; ?></p>
</section>

<section class="mda-espace-wrap">

  <?php if ( isset( $_GET['bienvenue'] ) ) : ?>
    <div class="contact-success">Compte créé. Complétez maintenant les informations de votre boutique.</div>
  <?php elseif ( isset( $_GET['enregistre'] ) ) : ?>
    <div class="contact-success">Modifications enregistrées.</div>
  <?php elseif ( isset( $_GET['ajoute'] ) ) : ?>
    <div class="contact-success">Article ajouté à votre galerie.</div>
  <?php elseif ( isset( $_GET['supprime'] ) ) : ?>
    <div class="contact-success">Article supprimé.</div>
  <?php endif; ?>
  <?php if ( $mda_error ) : ?>
    <div class="contact-error"><?php echo esc_html( $mda_error ); ?></div>
  <?php endif; ?>

  <?php // La navigation entre "Ma Boutique" et "Mes Articles" se fait désormais
  // uniquement via le menu du header (plus de doublon d'onglets ici). ?>

  <?php if ( 'boutique' === $mda_tab ) : ?>

    <div class="contact-form-card mda-espace-card">
      <h2>Informations de la boutique</h2>
      <?php if ( $mda_shop ) : ?>
        <p class="contact-form-intro">Note actuelle : <strong><?php echo esc_html( $mda_stats['average'] ); ?>/5</strong> (<?php echo esc_html( $mda_stats['total'] ); ?> avis) — calculée automatiquement à partir des avis laissés par vos clients.</p>
      <?php endif; ?>

      <form method="post" enctype="multipart/form-data">
        <?php wp_nonce_field( 'mda_espace_boutique', 'mda_boutique_nonce' ); ?>

        <label for="mda_titre">Nom de la boutique</label>
        <input type="text" id="mda_titre" name="mda_titre" required
               value="<?php echo esc_attr( $mda_shop ? $mda_shop->post_title : '' ); ?>">

        <label for="mda_description">Description courte (2-3 phrases, affichée en haut de votre fiche)</label>
        <textarea id="mda_description" name="mda_description"><?php echo esc_textarea( $mda_shop ? get_post_meta( $mda_shop->ID, '_mda_description', true ) : '' ); ?></textarea>

        <label for="mda_histoire">Votre histoire (texte complet affiché sur la fiche)</label>
        <textarea id="mda_histoire" name="mda_histoire" style="min-height:160px;"><?php echo esc_textarea( $mda_shop ? $mda_shop->post_content : '' ); ?></textarea>

        <div class="mda-field-grid">
          <div>
            <label for="mda_metier">Métier</label>
            <input type="text" id="mda_metier" name="mda_metier" placeholder="Ex : Maître Sculpteur sur Bois"
                   value="<?php echo esc_attr( $mda_shop ? get_post_meta( $mda_shop->ID, '_mda_metier', true ) : '' ); ?>">
          </div>
          <div>
            <label for="mda_experience">Expérience</label>
            <input type="text" id="mda_experience" name="mda_experience" placeholder="Ex : 8 ans"
                   value="<?php echo esc_attr( $mda_shop ? get_post_meta( $mda_shop->ID, '_mda_experience', true ) : '' ); ?>">
          </div>
        </div>

        <div class="mda-field-grid">
          <div>
            <label for="mda_statut">Statut</label>
            <select id="mda_statut" name="mda_statut">
              <?php $statut_actuel = $mda_shop ? get_post_meta( $mda_shop->ID, '_mda_statut', true ) : ''; ?>
              <?php foreach ( array( 'Nouveau membre', 'Certifié' ) as $opt ) : ?>
                <option value="<?php echo esc_attr( $opt ); ?>" <?php selected( $statut_actuel, $opt ); ?>><?php echo esc_html( $opt ); ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label for="mda_type_article">Catégorie principale</label>
            <select id="mda_type_article" name="mda_type_article">
              <option value="">— Choisir —</option>
              <?php if ( ! is_wp_error( $mda_types ) ) : foreach ( $mda_types as $t ) : ?>
                <option value="<?php echo esc_attr( $t->term_id ); ?>" <?php selected( in_array( $t->term_id, (array) $mda_current_type, true ) ); ?>><?php echo esc_html( $t->name ); ?></option>
              <?php endforeach; endif; ?>
            </select>
          </div>
        </div>

        <label for="mda_whatsapp">Numéro WhatsApp (les clients vous contactent directement)</label>
        <input type="text" id="mda_whatsapp" name="mda_whatsapp" placeholder="+229XXXXXXXX"
               value="<?php echo esc_attr( $mda_shop ? get_post_meta( $mda_shop->ID, '_mda_whatsapp', true ) : '' ); ?>">

        <fieldset class="mda-fieldset">
          <legend>Position au marché</legend>
          <div class="mda-field-grid">
            <div>
              <label for="mda_emplacement">Code affiché (ex : B1-01)</label>
              <input type="text" id="mda_emplacement" name="mda_emplacement"
                     value="<?php echo esc_attr( $mda_shop ? get_post_meta( $mda_shop->ID, '_mda_emplacement', true ) : '' ); ?>">
            </div>
            <div>
              <label for="mda_kiosque">Numéro de kiosque sur le plan (1-225)</label>
              <input type="number" id="mda_kiosque" name="mda_kiosque" min="1" max="225"
                     value="<?php echo esc_attr( $mda_shop ? get_post_meta( $mda_shop->ID, '_mda_kiosque', true ) : '' ); ?>">
            </div>
          </div>
          <p class="mda-hint">Ce numéro positionne automatiquement votre boutique à la fois sur le plan schématique et sur la carte réelle du marché — aucune saisie de coordonnées GPS n'est nécessaire.</p>
        </fieldset>

        <label for="mda_photo_principale">Photo principale de la boutique</label>
        <?php if ( $mda_shop && has_post_thumbnail( $mda_shop->ID ) ) : ?>
          <div class="mda-current-photo"><?php echo get_the_post_thumbnail( $mda_shop->ID, 'thumbnail' ); ?></div>
        <?php endif; ?>
        <input type="file" id="mda_photo_principale" name="mda_photo_principale" accept="image/*">

        <div class="mda-field-grid">
          <div>
            <label for="mda_histoire_img1">Image "L'histoire" — 1</label>
            <input type="file" id="mda_histoire_img1" name="mda_histoire_img1" accept="image/*">
          </div>
          <div>
            <label for="mda_histoire_img2">Image "L'histoire" — 2</label>
            <input type="file" id="mda_histoire_img2" name="mda_histoire_img2" accept="image/*">
          </div>
        </div>

        <button type="submit" name="mda_save_boutique" value="1">Enregistrer ma boutique</button>
      </form>
    </div>

    <div class="contact-form-card mda-espace-card mda-compte-card">
      <h2>Mon compte</h2>
      <form method="post" action="">
        <?php wp_nonce_field( 'mda_espace_compte', 'mda_compte_nonce' ); ?>

        <label for="mda_display_name">Nom</label>
        <input type="text" id="mda_display_name" name="mda_display_name" value="<?php echo esc_attr( $mda_user->display_name ); ?>">

        <label for="mda_compte_email">Email</label>
        <input type="email" id="mda_compte_email" name="mda_compte_email" value="<?php echo esc_attr( $mda_user->user_email ); ?>">

        <label for="mda_compte_password">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
        <input type="password" id="mda_compte_password" name="mda_compte_password" minlength="8">

        <button type="submit" name="mda_save_compte" value="1">Mettre à jour mon compte</button>
      </form>
      <p class="mda-auth-links"><a href="<?php echo esc_url( wp_logout_url( home_url( '/' ) ) ); ?>">Se déconnecter</a></p>
    </div>

  <?php else : /* Onglet Mes Articles */ ?>

    <?php if ( ! $mda_shop ) : ?>
      <div class="contact-form-card mda-espace-card">
        <p>Créez d'abord votre boutique dans l'onglet "Ma Boutique" avant d'ajouter des articles.</p>
      </div>
    <?php else : ?>

      <div class="contact-form-card mda-espace-card">
        <h2>Ajouter un article</h2>
        <form method="post" enctype="multipart/form-data">
          <?php wp_nonce_field( 'mda_espace_oeuvre', 'mda_oeuvre_nonce' ); ?>

          <label for="mda_oeuvre_titre">Nom de l'article</label>
          <input type="text" id="mda_oeuvre_titre" name="mda_oeuvre_titre" required>

          <label for="mda_oeuvre_materiau">Matériau</label>
          <select id="mda_oeuvre_materiau" name="mda_oeuvre_materiau">
            <option value="">— Choisir —</option>
            <?php if ( ! is_wp_error( $mda_materiaux ) ) : foreach ( $mda_materiaux as $m ) : ?>
              <option value="<?php echo esc_attr( $m->term_id ); ?>"><?php echo esc_html( $m->name ); ?></option>
            <?php endforeach; endif; ?>
          </select>

          <label for="mda_oeuvre_photo">Photo</label>
          <input type="file" id="mda_oeuvre_photo" name="mda_oeuvre_photo" accept="image/*">

          <button type="submit" name="mda_add_oeuvre" value="1">Ajouter l'article</button>
        </form>
      </div>

      <div class="mda-oeuvres-liste">
        <?php if ( $mda_oeuvres ) : foreach ( $mda_oeuvres as $o ) :
            $o_materiau = get_the_terms( $o->ID, 'materiau' );
            $del_url = wp_nonce_url( home_url( '/espace-artisan/?tab=articles&supprimer_oeuvre=' . $o->ID ), 'mda_delete_oeuvre_' . $o->ID );
            ?>
            <div class="mda-oeuvre-row">
              <div class="mda-oeuvre-thumb">
                <?php echo has_post_thumbnail( $o->ID ) ? get_the_post_thumbnail( $o->ID, 'thumbnail' ) : '<span class="mda-no-photo">Sans photo</span>'; ?>
              </div>
              <div class="mda-oeuvre-info">
                <strong><?php echo esc_html( $o->post_title ); ?></strong>
                <?php if ( $o_materiau && ! is_wp_error( $o_materiau ) ) : ?>
                  <span class="oeuvre-tag"><?php echo esc_html( implode( ' · ', wp_list_pluck( $o_materiau, 'name' ) ) ); ?></span>
                <?php endif; ?>
              </div>
              <a class="mda-oeuvre-delete" href="<?php echo esc_url( $del_url ); ?>"
                 onclick="return confirm('Supprimer définitivement cet article ?');">Supprimer</a>
            </div>
        <?php endforeach; else : ?>
          <p class="mda-hint">Aucun article ajouté pour le moment.</p>
        <?php endif; ?>
      </div>

    <?php endif; ?>

  <?php endif; ?>

</section>

<?php get_footer(); ?>
