<?php
/**
 * Statistiques globales et compteur de visites par boutique.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

// Statistiques globales du marché, utilisées par le bandeau de la page d'accueil.
function mda_global_stats() {
    $boutiques = wp_count_posts( 'boutique' );
    $oeuvres   = wp_count_posts( 'oeuvre' );

    $comments = get_comments( array(
        'status'  => 'approve',
        'post_type' => 'boutique',
    ) );
    $total = 0;
    foreach ( $comments as $c ) {
        $r = (int) get_comment_meta( $c->comment_ID, 'rating', true );
        if ( $r >= 1 && $r <= 5 ) {
            $total++;
        }
    }

    // Note moyenne globale = moyenne des notes moyennes accordées aux
    // différentes boutiques (et non la moyenne brute de tous les avis
    // confondus) : une boutique avec beaucoup d'avis ne doit pas peser plus
    // lourd qu'une autre dans le calcul global. Seules les boutiques ayant
    // reçu au moins un avis entrent dans le calcul.
    $boutique_ids = get_posts( array(
        'post_type'      => 'boutique',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    $somme_des_moyennes  = 0;
    $nb_boutiques_notees = 0;
    foreach ( $boutique_ids as $id ) {
        $stats_boutique = mda_boutique_rating_stats( $id );
        if ( $stats_boutique['total'] > 0 ) {
            $somme_des_moyennes += (float) $stats_boutique['average'];
            $nb_boutiques_notees++;
        }
    }

    return array(
        'boutiques'    => (int) $boutiques->publish,
        'oeuvres'      => (int) $oeuvres->publish,
        'avis'         => $total,
        'note_moyenne' => $nb_boutiques_notees ? round( $somme_des_moyennes / $nb_boutiques_notees, 1 ) : 0,
    );
}


// ---------------------------------------------------------------------
// Compteur de visites par boutique — usage interne uniquement (jamais
// affiché sur le site). Sert à déterminer les "Boutiques les plus
// consultées" sur la page d'accueil.
// ---------------------------------------------------------------------

// Garantit que chaque boutique publiée possède le compteur dès sa création,
// pour qu'aucune ne soit exclue du tri par nombre de visites (une boutique
// sans le champ meta serait ignorée par une requête "orderby meta_value_num").
function mda_init_visites_meta( $post_id, $post ) {
    if ( 'boutique' === $post->post_type && '' === get_post_meta( $post_id, '_mda_visites', true ) ) {
        add_post_meta( $post_id, '_mda_visites', 0, true );
    }
}
add_action( 'save_post_boutique', 'mda_init_visites_meta', 10, 2 );

// Rattrapage ponctuel pour les boutiques déjà publiées avant l'ajout de
// cette fonctionnalité (exécuté une seule fois).
function mda_backfill_visites_meta() {
    if ( get_option( 'mda_visites_backfilled' ) ) {
        return;
    }
    $boutiques = get_posts( array(
        'post_type'      => 'boutique',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ) );
    foreach ( $boutiques as $id ) {
        if ( '' === get_post_meta( $id, '_mda_visites', true ) ) {
            add_post_meta( $id, '_mda_visites', 0, true );
        }
    }
    update_option( 'mda_visites_backfilled', 1 );
}
add_action( 'init', 'mda_backfill_visites_meta', 30 );

// Incrémente le compteur à chaque visite réelle d'une fiche boutique côté
// public — jamais en admin, jamais sur un simple aperçu de brouillon.
function mda_increment_boutique_visits() {
    if ( is_admin() || is_preview() || ! is_singular( 'boutique' ) ) {
        return;
    }
    $post_id = get_queried_object_id();
    if ( ! $post_id ) {
        return;
    }
    $actuel = (int) get_post_meta( $post_id, '_mda_visites', true );
    update_post_meta( $post_id, '_mda_visites', $actuel + 1 );
}
add_action( 'wp', 'mda_increment_boutique_visits' );

