<?php
/**
 * Plan des kiosques : données, réglage GPS Customizer, tracé et chargement de Leaflet.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

// Données du plan des kiosques : boutique + jusqu'à 3 photos (œuvres, puis
// image mise en avant en repli) pour chaque kiosque occupé.
function mda_plan_kiosques_data() {
    $boutiques = get_posts( array(
        'post_type'      => 'boutique',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'meta_key'       => '_mda_kiosque',
    ) );

    $data = array();
    foreach ( $boutiques as $b ) {
        $numero = (int) get_post_meta( $b->ID, '_mda_kiosque', true );
        if ( $numero < 1 || $numero > 225 ) {
            continue;
        }

        $types_terms = get_the_terms( $b->ID, 'type_article' );
        $type_label  = ( $types_terms && ! is_wp_error( $types_terms ) ) ? implode( ' · ', wp_list_pluck( $types_terms, 'name' ) ) : '';

        $photos = array();
        $oeuvres = get_posts( array(
            'post_type'      => 'oeuvre',
            'posts_per_page' => 3,
            'meta_key'       => '_mda_boutique_id',
            'meta_value'     => $b->ID,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ) );
        foreach ( $oeuvres as $o ) {
            if ( has_post_thumbnail( $o->ID ) ) {
                $photos[] = get_the_post_thumbnail_url( $o->ID, 'medium' );
            }
        }
        if ( empty( $photos ) && has_post_thumbnail( $b->ID ) ) {
            $photos[] = get_the_post_thumbnail_url( $b->ID, 'medium' );
        }

        $photo_principale = has_post_thumbnail( $b->ID ) ? get_the_post_thumbnail_url( $b->ID, 'medium' ) : '';
        if ( ! $photo_principale && ! empty( $photos ) ) {
            $photo_principale = array_shift( $photos );
        }

        $stats_kiosque = mda_boutique_rating_stats( $b->ID );

        $data[ $numero ] = array(
            'titre'            => get_the_title( $b->ID ),
            'type'             => $type_label,
            'note'             => $stats_kiosque['total'] ? $stats_kiosque['average'] : 0,
            'emplacement'      => get_post_meta( $b->ID, '_mda_emplacement', true ),
            'photo_principale' => $photo_principale,
            'photos'           => array_slice( $photos, 0, 2 ),
            'lien'             => get_permalink( $b->ID ),
        );
    }
    return $data;
}


// ---------------------------------------------------------------------
// Carte réelle géoréférencée du plan des kiosques (page "Plan").
// ---------------------------------------------------------------------

// Réglage Customizer : tracé GPS réel de l'allée du marché, sous forme
// d'une liste de points de passage (un par ligne, "latitude,longitude"),
// du kiosque n°1 jusqu'au kiosque n°225. Avec seulement 2 points, le tracé
// est une ligne droite ; ajouter des points intermédiaires à chaque coude
// réel de l'allée permet à la projection de suivre fidèlement sa forme au
// lieu de dériver au fur et à mesure qu'on s'éloigne du premier point.
function mda_plan_customize_register( $wp_customize ) {
    $wp_customize->add_section( 'mda_plan_geo', array(
        'title'       => 'Plan du marché — coordonnées GPS',
        'description' => "Tracé réel de l'allée : un point \"latitude,longitude\" par ligne, du kiosque 1 au kiosque 225, dans l'ordre. Ajoutez un point à chaque coude de l'allée pour un rendu fidèle — 2 points suffisent seulement si elle est parfaitement droite.",
        'priority'    => 160,
    ) );

    $wp_customize->add_setting( 'mda_plan_chemin_gps', array(
        'default'           => "6.3591899,2.4118313\n6.3595899,2.4124313",
        'sanitize_callback' => 'sanitize_textarea_field',
    ) );
    $wp_customize->add_control( 'mda_plan_chemin_gps', array(
        'section' => 'mda_plan_geo',
        'label'   => "Tracé de l'allée (1 point par ligne)",
        'type'    => 'textarea',
    ) );
}
add_action( 'customize_register', 'mda_plan_customize_register' );

// Transforme le texte du réglage ci-dessus en tableau de points [lat, lng]
// utilisables en JS. Les lignes vides ou mal formées sont ignorées.
function mda_plan_chemin_gps() {
    $brut   = get_theme_mod( 'mda_plan_chemin_gps', "6.3591899,2.4118313\n6.3595899,2.4124313" );
    $lignes = preg_split( '/\r\n|\r|\n/', trim( (string) $brut ) );
    $points = array();

    foreach ( $lignes as $ligne ) {
        $ligne = trim( $ligne );
        if ( '' === $ligne ) {
            continue;
        }
        $parts = array_map( 'trim', explode( ',', $ligne ) );
        if ( 2 === count( $parts ) && is_numeric( $parts[0] ) && is_numeric( $parts[1] ) ) {
            $points[] = array( (float) $parts[0], (float) $parts[1] );
        }
    }

    // Sécurité : il faut au moins 2 points pour tracer un chemin.
    if ( count( $points ) < 2 ) {
        $points = array( array( 6.3648, 2.4197 ), array( 6.3652, 2.4203 ) );
    }

    return $points;
}

// Leaflet (bibliothèque cartographique open-source compatible OpenStreetMap)
// n'est chargé que sur la page "Plan", pour ne pas alourdir le reste du site.
function mda_enqueue_leaflet() {
    if ( is_page( 'plan' ) ) {
        wp_enqueue_style( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.css', array(), '1.9.4' );
        wp_enqueue_script( 'leaflet', 'https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.min.js', array(), '1.9.4', true );
    }
}
add_action( 'wp_enqueue_scripts', 'mda_enqueue_leaflet' );

