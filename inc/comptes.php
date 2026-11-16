<?php
/**
 * Comptes artisans/clients : rôles, accès wp-admin, boutique de l'artisan, politique d'accès, redirections sûres.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

// ===========================================================================
// COMPTES ARTISANS / CLIENTS — rôles distincts, connexion/inscription séparées
// ===========================================================================

// Deux rôles distincts. "client" = visiteur inscrit (peut laisser des avis
// connecté) ; "artisan" = gère sa propre boutique depuis /espace-artisan/.
function mda_register_roles() {
    if ( ! get_role( 'client' ) ) {
        add_role( 'client', 'Client', array( 'read' => true ) );
    }
    if ( ! get_role( 'artisan' ) ) {
        add_role( 'artisan', 'Artisan', array( 'read' => true, 'upload_files' => true ) );
    }
}
add_action( 'after_switch_theme', 'mda_register_roles' );
add_action( 'init', 'mda_register_roles' ); // filet de sécurité si le thème était déjà actif lors de l'ajout de ce code

// Empêche un artisan/client de se rendre dans /wp-admin/ (front uniquement).
function mda_bloquer_admin_non_staff() {
    if ( is_admin() && ! wp_doing_ajax() && is_user_logged_in()
         && ! current_user_can( 'edit_posts' ) && ! current_user_can( 'manage_options' ) ) {
        wp_redirect( home_url( '/espace-artisan/' ) );
        exit;
    }
}
add_action( 'admin_init', 'mda_bloquer_admin_non_staff' );

// Boutique appartenant à l'artisan actuellement connecté (ou null).
function mda_get_current_artisan_shop() {
    if ( ! is_user_logged_in() ) {
        return null;
    }
    $user = wp_get_current_user();
    if ( ! in_array( 'artisan', (array) $user->roles, true ) ) {
        return null;
    }
    $posts = get_posts( array(
        'post_type'      => 'boutique',
        'author'         => $user->ID,
        'posts_per_page' => 1,
        'post_status'    => array( 'publish', 'draft', 'pending' ),
    ) );
    return $posts ? $posts[0] : null;
}


// Détermine si le visiteur connecté est un artisan.
function mda_current_user_is_artisan() {
    return is_user_logged_in() && in_array( 'artisan', (array) wp_get_current_user()->roles, true );
}

// N'autorise jamais une redirection ("redirect_to") vers un domaine externe.
// À utiliser systématiquement avant tout wp_redirect() basé sur une valeur
// fournie par le visiteur (paramètre GET/POST, champ caché, etc.), sous
// peine de "open redirect" exploitable en phishing juste après une connexion
// légitime.
function mda_url_interne_ou_accueil( $url ) {
    $url = ! empty( $url ) ? esc_url_raw( wp_unslash( $url ) ) : '';
    if ( '' === $url || 0 !== strpos( $url, home_url() ) ) {
        return home_url( '/' );
    }
    return $url;
}

// ---------------------------------------------------------------------
// POLITIQUE D'ACCÈS DU SITE (rappel + filet de sécurité)
// ---------------------------------------------------------------------
// Librement consultables SANS compte : Accueil, Boutiques (répertoire +
// fiches), Plan du marché, résultats de recherche, connexion/inscription,
// pages légales.
// Réservés aux visiteurs connectés : Contact (page-contact.php gère déjà
// l'affichage d'un mur de connexion) et Espace Artisan (page-espace-artisan.php
// redirige déjà vers /connexion-artisan/).
//
// Cette fonction ne bloque jamais un guest sur les URLs listées ci-dessous :
// si une redirection de connexion venait à s'y appliquer malgré tout (statut
// de Page mis par erreur sur "Privé"/"Brouillon" dans l'admin, réglage
// Réglages > Lecture, extension tierce, etc.), on l'annule explicitement ici.
function mda_garantir_pages_publiques() {
    if ( is_user_logged_in() || is_admin() ) {
        return;
    }

    $est_page_toujours_publique =
        is_front_page()
        || is_post_type_archive( 'boutique' )
        || is_singular( 'boutique' )
        || is_page( 'plan' )
        || is_search()
        || is_page( array( 'connexion', 'connexion-artisan', 'inscription', 'inscription-artisan', 'confidentialite', 'mentions-legales' ) );

    if ( ! $est_page_toujours_publique ) {
        return;
    }

    // Cas fréquent : la Page correspondante (ex. "Plan") a été enregistrée
    // par erreur avec le statut "Privé" dans l'admin, ce qui la rend
    // invisible aux visiteurs non connectés (WordPress renvoie alors un
    // refus d'accès/404 au lieu d'afficher la page). Comme ces emplacements
    // doivent toujours rester publics, on la republie automatiquement.
    $obj = get_queried_object();
    if ( $obj instanceof WP_Post && 'private' === $obj->post_status ) {
        wp_update_post( array( 'ID' => $obj->ID, 'post_status' => 'publish' ) );
        wp_safe_redirect( get_permalink( $obj->ID ) );
        exit;
    }
}
add_action( 'template_redirect', 'mda_garantir_pages_publiques', 1 );

