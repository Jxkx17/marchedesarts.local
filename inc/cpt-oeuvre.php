<?php
/**
 * Custom Post Type Articles exposés (œuvres) rattachés à une boutique, colonnes et filtre admin.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

// ---------- CPT Articles exposés (rattachés à une boutique) ----------
function mda_register_oeuvre_cpt() {
    register_post_type( 'oeuvre', array(
        'labels' => array(
            'name'               => 'Articles exposés',
            'singular_name'      => 'Article exposé',
            'menu_name'          => 'Articles exposés',
            'add_new'            => 'Ajouter un article',
            'add_new_item'       => 'Ajouter un article exposé',
            'edit_item'          => 'Modifier l\'article',
            'new_item'           => 'Nouvel article',
            'view_item'          => 'Voir l\'article',
            'search_items'       => 'Rechercher un article',
            'not_found'          => 'Aucun article exposé trouvé',
            'not_found_in_trash' => 'Aucun article dans la corbeille',
        ),
        'public'       => true,
        'has_archive'  => false,
        'menu_icon'    => 'dashicons-art',
        'show_in_menu' => 'mda-dashboard',
        'supports'     => array( 'title', 'thumbnail' ),
        'show_in_rest' => true,
    ) );

    register_taxonomy( 'materiau', 'oeuvre', array(
        'labels' => array( 'name' => 'Matériaux', 'singular_name' => 'Matériau' ),
        'hierarchical'      => false,
        'show_in_rest'      => true,
        'show_admin_column' => true,
    ) );
}
add_action( 'init', 'mda_register_oeuvre_cpt' );

function mda_oeuvre_meta_box() {
    add_meta_box( 'mda_oeuvre_boutique', 'Boutique rattachée', 'mda_oeuvre_boutique_callback', 'oeuvre', 'side', 'high' );
}
add_action( 'add_meta_boxes', 'mda_oeuvre_meta_box' );

function mda_oeuvre_boutique_callback( $post ) {
    $selected  = get_post_meta( $post->ID, '_mda_boutique_id', true );
    $boutiques = get_posts( array( 'post_type' => 'boutique', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    wp_nonce_field( 'mda_oeuvre_save', 'mda_oeuvre_nonce' );
    echo '<select name="mda_boutique_id" style="width:100%">';
    echo '<option value="">— Choisir une boutique —</option>';
    foreach ( $boutiques as $b ) {
        printf( '<option value="%d"%s>%s</option>', $b->ID, selected( $selected, $b->ID, false ), esc_html( $b->post_title ) );
    }
    echo '</select>';
}

function mda_save_oeuvre_meta( $post_id ) {
    if ( ! isset( $_POST['mda_oeuvre_nonce'] ) || ! wp_verify_nonce( $_POST['mda_oeuvre_nonce'], 'mda_oeuvre_save' ) ) {
        return;
    }
    if ( isset( $_POST['mda_boutique_id'] ) ) {
        update_post_meta( $post_id, '_mda_boutique_id', absint( $_POST['mda_boutique_id'] ) );
    }
}
add_action( 'save_post', 'mda_save_oeuvre_meta' );

// ---------- Amélioration de la liste admin "Articles exposés" ----------
function mda_oeuvre_columns( $columns ) {
    $new = array();
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['mda_photo']    = 'Photo';
        }
    }
    $new['mda_boutique'] = 'Boutique';
    return $new;
}
add_filter( 'manage_oeuvre_posts_columns', 'mda_oeuvre_columns' );

function mda_oeuvre_columns_content( $column, $post_id ) {
    if ( 'mda_photo' === $column ) {
        echo has_post_thumbnail( $post_id )
            ? get_the_post_thumbnail( $post_id, array( 40, 40 ), array( 'style' => 'border-radius:6px;object-fit:cover;' ) )
            : '—';
    }
    if ( 'mda_boutique' === $column ) {
        $boutique_id = get_post_meta( $post_id, '_mda_boutique_id', true );
        echo $boutique_id ? esc_html( get_the_title( $boutique_id ) ) : '<em>Non rattaché</em>';
    }
}
add_action( 'manage_oeuvre_posts_custom_column', 'mda_oeuvre_columns_content', 10, 2 );

// Filtre déroulant "Boutique" au-dessus de la liste des articles exposés
function mda_oeuvre_boutique_filter() {
    global $typenow;
    if ( 'oeuvre' !== $typenow ) {
        return;
    }
    $boutiques = get_posts( array( 'post_type' => 'boutique', 'posts_per_page' => -1, 'post_status' => 'publish' ) );
    $selected  = isset( $_GET['mda_filtre_boutique'] ) ? absint( $_GET['mda_filtre_boutique'] ) : '';
    echo '<select name="mda_filtre_boutique">';
    echo '<option value="">Toutes les boutiques</option>';
    foreach ( $boutiques as $b ) {
        printf( '<option value="%d"%s>%s</option>', $b->ID, selected( $selected, $b->ID, false ), esc_html( $b->post_title ) );
    }
    echo '</select>';
}
add_action( 'restrict_manage_posts', 'mda_oeuvre_boutique_filter' );

function mda_oeuvre_boutique_filter_query( $query ) {
    global $pagenow, $typenow;
    if ( is_admin() && 'edit.php' === $pagenow && 'oeuvre' === $typenow && ! empty( $_GET['mda_filtre_boutique'] ) ) {
        $query->query_vars['meta_key']   = '_mda_boutique_id';
        $query->query_vars['meta_value'] = absint( $_GET['mda_filtre_boutique'] );
    }
}
add_filter( 'parse_query', 'mda_oeuvre_boutique_filter_query' );

