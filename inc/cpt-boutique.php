<?php
/**
 * Custom Post Type Boutique : enregistrement, meta boxes (note, emplacement, profil artisan, kiosque).
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

// Custom Post Type: Boutiques
function mda_register_boutique_cpt() {
    register_post_type( 'boutique', array(
        'labels' => array(
            'name' => 'Boutiques',
            'singular_name' => 'Boutique',
            'add_new_item' => 'Ajouter une boutique',
        ),
        'public' => true,
        'has_archive' => 'boutiques',
        'rewrite' => array( 'slug' => 'boutiques' ),
        'menu_icon' => 'dashicons-store',
        'show_in_menu' => 'mda-dashboard',
        'supports' => array( 'title', 'editor', 'thumbnail', 'excerpt', 'comments' ),
        'show_in_rest' => true,
    ) );
}
add_action( 'init', 'mda_register_boutique_cpt' );


// Meta fields: note, emplacement, catégorie
function mda_boutique_meta_boxes() {
    add_meta_box( 'mda_boutique_details', 'Détails boutique', 'mda_boutique_meta_callback', 'boutique', 'side' );
}
add_action( 'add_meta_boxes', 'mda_boutique_meta_boxes' );

function mda_boutique_meta_callback( $post ) {
    $emplacement = get_post_meta( $post->ID, '_mda_emplacement', true );
    $categorie   = get_post_meta( $post->ID, '_mda_categorie', true );
    $stats       = mda_boutique_rating_stats( $post->ID );
    wp_nonce_field( 'mda_boutique_details_save', 'mda_boutique_details_nonce' );
    ?>
    <p><em>Note : <?php echo esc_html( $stats['average'] ); ?>/5 sur <?php echo esc_html( $stats['total'] ); ?> avis — calculée automatiquement à partir des avis laissés sur la fiche, non modifiable ici.</em></p>
    <p><label>Emplacement / Code (ex: B1-01)</label><br>
    <input type="text" name="mda_emplacement" value="<?php echo esc_attr( $emplacement ); ?>" style="width:100%"></p>
    <p><label>Profession - Spécialité (ex: Bijoutière - Laiton & Pierres)</label><br>
    <input type="text" name="mda_categorie" value="<?php echo esc_attr( $categorie ); ?>" style="width:100%"></p>
    <?php
}

function mda_save_boutique_meta( $post_id ) {
    // Filet de sécurité standard : nonce valide, pas un autosave/une révision,
    // et l'utilisateur doit avoir le droit de modifier CETTE fiche précise.
    if ( ! isset( $_POST['mda_boutique_details_nonce'] )
         || ! wp_verify_nonce( $_POST['mda_boutique_details_nonce'], 'mda_boutique_details_save' ) ) {
        return;
    }
    if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    if ( isset( $_POST['mda_emplacement'] ) ) {
        update_post_meta( $post_id, '_mda_emplacement', sanitize_text_field( $_POST['mda_emplacement'] ) );
    }
    if ( isset( $_POST['mda_categorie'] ) ) {
        update_post_meta( $post_id, '_mda_categorie', sanitize_text_field( $_POST['mda_categorie'] ) );
    }
}
add_action( 'save_post', 'mda_save_boutique_meta' );

// ---------- Champs profil complet de la boutique (page fiche artisan) ----------
function mda_boutique_profile_meta_box() {
    add_meta_box( 'mda_boutique_profile', 'Profil artisan (page fiche)', 'mda_boutique_profile_callback', 'boutique', 'normal', 'high' );
}
add_action( 'add_meta_boxes', 'mda_boutique_profile_meta_box' );

function mda_boutique_profile_callback( $post ) {
    $metier      = get_post_meta( $post->ID, '_mda_metier', true );
    $experience  = get_post_meta( $post->ID, '_mda_experience', true );
    $statut      = get_post_meta( $post->ID, '_mda_statut', true );
    $whatsapp    = get_post_meta( $post->ID, '_mda_whatsapp', true );
    $description = get_post_meta( $post->ID, '_mda_description', true );
    $img1        = get_post_meta( $post->ID, '_mda_histoire_img1', true );
    $img2        = get_post_meta( $post->ID, '_mda_histoire_img2', true );
    $kiosque     = get_post_meta( $post->ID, '_mda_kiosque', true );
    wp_nonce_field( 'mda_profile_save', 'mda_profile_nonce' );
    ?>
    <p><label><strong>Métier</strong> (ex: Maître Fondeur Bronze)</label><br>
    <input type="text" name="mda_metier" value="<?php echo esc_attr( $metier ); ?>" style="width:100%"></p>
    <p><label><strong>Expérience</strong> (ex: 5 ans)</label><br>
    <input type="text" name="mda_experience" value="<?php echo esc_attr( $experience ); ?>" style="width:100%"></p>
    <p><label><strong>Statut</strong> (ex: Certifié)</label><br>
    <input type="text" name="mda_statut" value="<?php echo esc_attr( $statut ); ?>" style="width:100%"></p>
    <p><label><strong>Numéro WhatsApp</strong> (format international, ex: +22990000000)</label><br>
    <input type="text" name="mda_whatsapp" value="<?php echo esc_attr( $whatsapp ); ?>" style="width:100%" placeholder="+229XXXXXXXX"></p>
    <p><label><strong>Numéro de kiosque sur le plan</strong> (1 à 225)</label><br>
    <input type="number" name="mda_kiosque" min="1" max="225" value="<?php echo esc_attr( $kiosque ); ?>" style="width:100%">
    <?php if ( $kiosque ) :
        $occupant = mda_kiosque_deja_pris( $kiosque, $post->ID );
        if ( $occupant ) : ?>
        <span style="color:#B42318;">⚠ Kiosque déjà attribué à "<?php echo esc_html( $occupant ); ?>"</span>
    <?php endif; endif; ?>
    </p>
    <p><label><strong>Description courte</strong> (2-3 phrases affichées dans le bandeau marron — distincte du texte "L'histoire" ci-dessous)</label><br>
    <textarea name="mda_description" style="width:100%;min-height:70px;"><?php echo esc_textarea( $description ); ?></textarea></p>
    <p><em>Note et nombre d'avis : calculés automatiquement à partir des avis laissés par les clients sur la fiche boutique. Aucune saisie manuelle possible.</em></p>
    <p><em>Le contenu de l'article (éditeur principal ci-dessus) sert exclusivement au texte de la section "L'histoire" plus bas sur la page.</em></p>
    <p><label><strong>Image histoire 1</strong> — URL</label><br>
    <input type="text" name="mda_histoire_img1" value="<?php echo esc_attr( $img1 ); ?>" style="width:100%" placeholder="Coller une URL d'image de la médiathèque"></p>
    <p><label><strong>Image histoire 2</strong> — URL</label><br>
    <input type="text" name="mda_histoire_img2" value="<?php echo esc_attr( $img2 ); ?>" style="width:100%" placeholder="Coller une URL d'image de la médiathèque"></p>
    <?php
}

function mda_kiosque_deja_pris( $numero, $exclude_id ) {
    $existing = get_posts( array(
        'post_type'      => 'boutique',
        'posts_per_page' => 1,
        'post__not_in'   => array( $exclude_id ),
        'meta_key'       => '_mda_kiosque',
        'meta_value'     => $numero,
    ) );
    return $existing ? $existing[0]->post_title : false;
}

function mda_save_boutique_profile( $post_id ) {
    if ( ! isset( $_POST['mda_profile_nonce'] ) || ! wp_verify_nonce( $_POST['mda_profile_nonce'], 'mda_profile_save' ) ) {
        return;
    }
    $fields = array( 'mda_metier', 'mda_experience', 'mda_statut', 'mda_whatsapp', 'mda_kiosque', 'mda_histoire_img1', 'mda_histoire_img2' );
    foreach ( $fields as $f ) {
        if ( isset( $_POST[ $f ] ) ) {
            update_post_meta( $post_id, '_' . $f, sanitize_text_field( $_POST[ $f ] ) );
        }
    }
    if ( isset( $_POST['mda_description'] ) ) {
        update_post_meta( $post_id, '_mda_description', sanitize_textarea_field( $_POST['mda_description'] ) );
    }
}
add_action( 'save_post', 'mda_save_boutique_profile' );
