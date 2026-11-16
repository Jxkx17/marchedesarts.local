<?php
/**
 * Avis : note en étoiles sur les commentaires, statistiques, restrictions (compte requis, avis toujours ouverts).
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

// ---------- Avis (commentaires WordPress + note en étoiles) ----------
// Le HTML est exposé via une fonction (et pas seulement via le filtre
// comment_form_fields ci-dessous) car single-boutique.php fournit son propre
// tableau 'fields' à comment_form() — ce qui remplace entièrement les champs
// par défaut et empêche le filtre de s'appliquer. Le template appelle donc
// directement mda_rating_input_html().
function mda_rating_input_html() {
    $stars = '';
    for ( $s = 5; $s >= 1; $s-- ) {
        $stars .= sprintf(
            '<input type="radio" id="rating-%1$d" name="rating" value="%1$d" required><label for="rating-%1$d" title="%1$d étoile%2$s">&#9733;</label>',
            $s,
            $s > 1 ? 's' : ''
        );
    }
    return '<p class="comment-form-rating"><label>Votre note</label><div class="star-rating-input">' . $stars . '</div></p>';
}

function mda_add_rating_field( $fields ) {
    $fields['rating'] = mda_rating_input_html();
    return $fields;
}
add_filter( 'comment_form_fields', 'mda_add_rating_field' );

function mda_save_rating_meta( $comment_id ) {
    if ( isset( $_POST['rating'] ) && is_numeric( $_POST['rating'] ) ) {
        add_comment_meta( $comment_id, 'rating', absint( $_POST['rating'] ), true );
    }
}
add_action( 'comment_post', 'mda_save_rating_meta' );

// Une note est obligatoire pour tout avis déposé sur une boutique — c'est la
// seule source de notation : aucune saisie manuelle n'existe ailleurs.
function mda_valider_note_avis( $commentdata ) {
    if ( 'boutique' === get_post_type( $commentdata['comment_post_ID'] )
         && ( empty( $_POST['rating'] ) || ! is_numeric( $_POST['rating'] ) || $_POST['rating'] < 1 || $_POST['rating'] > 5 ) ) {
        wp_die(
            '<p>Merci de sélectionner une note de 1 à 5 étoiles avant d\'envoyer votre avis.</p>',
            'Note manquante',
            array( 'response' => 400, 'back_link' => true )
        );
    }
    return $commentdata;
}
add_filter( 'preprocess_comment', 'mda_valider_note_avis' );

// Statistiques d'avis d'une boutique : moyenne + répartition par étoile
function mda_boutique_rating_stats( $post_id ) {
    $comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve' ) );
    $dist = array( 5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0 );
    $total = 0;
    $sum   = 0;
    foreach ( $comments as $c ) {
        $r = (int) get_comment_meta( $c->comment_ID, 'rating', true );
        if ( $r >= 1 && $r <= 5 ) {
            $dist[ $r ]++;
            $total++;
            $sum += $r;
        }
    }
    return array(
        'average' => number_format( $total ? $sum / $total : 0, 1, '.', '' ),
        'total'   => $total,
        'dist'    => $dist,
    );
}

// Raccourcis d'affichage : note toujours formatée "0.0" si aucun avis.
function mda_get_shop_rating( $post_id ) {
    return mda_boutique_rating_stats( $post_id )['average'];
}
function mda_get_shop_rating_count( $post_id ) {
    return mda_boutique_rating_stats( $post_id )['total'];
}

// ---------- Avis/commentaires réservés aux visiteurs connectés ----------
// Le site reste consultable librement sans compte (accueil, boutiques, plan,
// contact...) : seule la dépose d'un avis/commentaire nécessite un compte.
// On force ce comportement indépendamment du réglage "Discussion" de
// l'admin, pour ne pas dépendre d'une case à cocher oubliée.
add_filter( 'option_comment_registration', '__return_true' );

// Filet de sécurité côté serveur : même si comment_registration était
// contourné (formulaire modifié, appel direct à wp-comments-post.php, API…),
// aucun avis n'est enregistré sans utilisateur connecté.
function mda_bloquer_avis_sans_compte( $commentdata ) {
    if ( ! is_user_logged_in() ) {
        wp_die(
            sprintf(
                '<p>Vous devez être connecté pour laisser un avis. <a href="%1$s">Se connecter</a> ou <a href="%2$s">créer un compte</a>.</p>',
                esc_url( home_url( '/connexion/' ) ),
                esc_url( home_url( '/inscription/' ) )
            ),
            'Connexion requise',
            array( 'response' => 403, 'back_link' => true )
        );
    }
    return $commentdata;
}
add_filter( 'preprocess_comment', 'mda_bloquer_avis_sans_compte' );


// Filet de rattrapage : une boutique doit toujours accepter les avis. Si
// comment_status vaut "closed" (option globale "default_comment_status" du
// site à closed, boutique créée avant ce correctif, etc.), on le rouvre
// automatiquement à la prochaine visite de la fiche — même logique de
// self-healing que mda_garantir_pages_publiques() ci-dessus, plutôt que de
// dépendre d'une correction manuelle en base pour chaque boutique existante.
function mda_garantir_avis_ouverts() {
    if ( ! is_singular( 'boutique' ) ) {
        return;
    }
    $post = get_queried_object();
    if ( $post instanceof WP_Post && 'open' !== $post->comment_status ) {
        wp_update_post( array( 'ID' => $post->ID, 'comment_status' => 'open' ) );
    }
}
add_action( 'template_redirect', 'mda_garantir_avis_ouverts', 1 );

