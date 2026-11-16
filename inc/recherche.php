<?php
/**
 * Recherche : archive Boutiques (?recherche, ?type_slug) et restriction de la recherche globale.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

// Recherche sur l'archive Boutiques (?recherche=terme) : titre de la boutique,
// nom de l'artisan, types d'article (taxonomie) ET matériau des articles exposés
// rattachés. Filtrage exact par type via ?type_slug=xxx (clic sur une vignette).
function mda_boutique_search( $query ) {
    if ( is_admin() || ! $query->is_main_query() || ! is_post_type_archive( 'boutique' ) ) {
        return;
    }

    $query->set( 'posts_per_page', 25 );
    // Ordre aléatoire à chaque visite — recalculé côté serveur sur chaque
    // chargement, y compris lorsqu'un filtre (type ou recherche) est actif.
    $query->set( 'orderby', 'rand' );

    // Filtre exact déclenché par un clic sur une vignette "Types d'article".
    if ( ! empty( $_GET['type_slug'] ) ) {
        $query->set( 'tax_query', array(
            array(
                'taxonomy' => 'type_article',
                'field'    => 'slug',
                'terms'    => sanitize_title( $_GET['type_slug'] ),
            ),
        ) );
        return;
    }

    if ( empty( $_GET['recherche'] ) ) {
        return;
    }

    $mot = sanitize_text_field( $_GET['recherche'] );
    $ids = array();

    // 1) Titre / contenu de la boutique (recherche WordPress standard).
    $par_titre = get_posts( array(
        'post_type'      => 'boutique',
        'post_status'    => 'publish',
        's'              => $mot,
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    $ids = array_merge( $ids, $par_titre );

    // 2) Types d'article dont le nom contient le mot recherché.
    $termes = get_terms( array(
        'taxonomy'   => 'type_article',
        'hide_empty' => false,
        'name__like' => $mot,
    ) );
    if ( ! is_wp_error( $termes ) && $termes ) {
        $par_type = get_posts( array(
            'post_type'      => 'boutique',
            'post_status'    => 'publish',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array( array(
                'taxonomy' => 'type_article',
                'field'    => 'term_id',
                'terms'    => wp_list_pluck( $termes, 'term_id' ),
            ) ),
        ) );
        $ids = array_merge( $ids, $par_type );
    }

    // 3) Matériau des articles exposés rattachés à une boutique.
    $materiaux = get_terms( array(
        'taxonomy'   => 'materiau',
        'hide_empty' => false,
        'name__like' => $mot,
    ) );
    if ( ! is_wp_error( $materiaux ) && $materiaux ) {
        $oeuvres = get_posts( array(
            'post_type'      => 'oeuvre',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'tax_query'      => array( array(
                'taxonomy' => 'materiau',
                'field'    => 'term_id',
                'terms'    => wp_list_pluck( $materiaux, 'term_id' ),
            ) ),
        ) );
        foreach ( $oeuvres as $oeuvre_id ) {
            $boutique_id = get_post_meta( $oeuvre_id, '_mda_boutique_id', true );
            if ( $boutique_id ) {
                $ids[] = (int) $boutique_id;
            }
        }
    }

    $ids = array_unique( $ids );

    // Aucun résultat sur aucun des 3 axes : forcer une requête vide plutôt
    // que de laisser WP_Query retomber sur "tous les articles".
    $query->set( 'post__in', $ids ? $ids : array( 0 ) );
    $query->set( 's', '' );
}
add_action( 'pre_get_posts', 'mda_boutique_search' );

// La recherche générale du header (?s=) ne doit porter que sur du contenu
// réellement consultable : boutiques et pages. Le CPT "oeuvre" n'a pas de
// gabarit d'affichage individuel et serait un lien mort dans les résultats.
function mda_restrict_search_post_types( $query ) {
    if ( ! is_admin() && $query->is_search() && $query->is_main_query() ) {
        $query->set( 'post_type', array( 'boutique', 'page' ) );
    }
}
add_action( 'pre_get_posts', 'mda_restrict_search_post_types' );

