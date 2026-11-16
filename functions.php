<?php

function mda_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    register_nav_menus( array(
        'primary' => 'Menu principal',
    ) );
}
add_action( 'after_setup_theme', 'mda_setup' );

function mda_enqueue_assets() {
    wp_enqueue_style( 'mda-style', get_stylesheet_uri(), array(), filemtime( get_stylesheet_directory() . '/style.css' ) );
    wp_enqueue_style( 'mda-google-fonts', 'https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&family=Poppins:wght@400;600&display=swap', array(), null );
}
add_action( 'wp_enqueue_scripts', 'mda_enqueue_assets' );

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

// Taxonomie "Types d'article" : catégories/spécialités des boutiques.
// Source unique utilisée pour les vignettes de l'accueil, les badges des
// cartes boutiques, et le filtrage/la recherche sur la vue "Toutes les Boutiques".
function mda_register_type_article_taxonomy() {
    register_taxonomy( 'type_article', 'boutique', array(
        'labels' => array(
            'name'          => "Catégories",
            'singular_name' => "Catégorie",
            'menu_name'     => "Catégories",
        ),
        'hierarchical'      => true,
        'show_in_rest'      => true,
        'show_admin_column' => true,
        'rewrite'           => array( 'slug' => 'type-article' ),
    ) );
}
add_action( 'init', 'mda_register_type_article_taxonomy' );

// Amorce les 7 types de départ une seule fois si la taxonomie est vide,
// pour éviter de repartir de zéro à l'admin. Modifiable librement ensuite
// depuis Boutiques > Types d'article.
function mda_seed_type_article_terms() {
    if ( ! taxonomy_exists( 'type_article' ) || get_option( 'mda_types_seeded' ) ) {
        return;
    }
    $defaults = array( 'Peintures / Dessins', 'Bijoux', 'Sacs', 'Vêtements', 'Sculptures', 'Décoration', 'Accessoires' );
    foreach ( $defaults as $t ) {
        if ( ! term_exists( $t, 'type_article' ) ) {
            wp_insert_term( $t, 'type_article' );
        }
    }
    update_option( 'mda_types_seeded', 1 );
}
add_action( 'init', 'mda_seed_type_article_terms', 20 );

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

// Génère un lien de contact : WhatsApp si un numéro est renseigné, mailto sinon.
function mda_contact_link( $boutique_id, $message = '' ) {
    $whatsapp = get_post_meta( $boutique_id, '_mda_whatsapp', true );
    if ( $whatsapp ) {
        $digits = preg_replace( '/[^0-9]/', '', $whatsapp );
        $url = 'https://wa.me/' . $digits;
        if ( $message ) {
            $url .= '?text=' . rawurlencode( $message );
        }
        return $url;
    }
    $to = get_option( 'admin_email' );
    return 'mailto:' . $to . ( $message ? '?subject=' . rawurlencode( $message ) : '' );
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

// Récupère l'image d'illustration d'un type d'article : priorité absolue à
// l'image vitrine assignée manuellement au terme (champ admin dédié,
// indépendant des photos de boutiques), puis à l'image d'exemple livrée
// avec le thème, puis à un bloc de couleur. Les photos de boutiques
// n'interviennent plus du tout dans ce calcul.
function mda_type_card_media( $term_id, $fallback_file, $color = '#6B4423' ) {
    $image_id = get_term_meta( $term_id, '_mda_type_image_id', true );
    if ( $image_id ) {
        $html = wp_get_attachment_image( $image_id, 'medium_large' );
        if ( $html ) {
            return $html;
        }
    }

    $fallback_path = get_stylesheet_directory() . '/assets/types/' . $fallback_file;
    if ( file_exists( $fallback_path ) ) {
        return '<img src="' . esc_url( get_stylesheet_directory_uri() . '/assets/types/' . $fallback_file ) . '" alt="">';
    }

    return '<div class="type-fallback" style="background:' . esc_attr( $color ) . '"></div>';
}

// --- Image vitrine par catégorie (type_article) --------------------------
// Champ dédié dans l'admin (Boutiques > Catégories), stocké en term meta.
// Totalement séparé des photos de boutiques : uploader une image de
// boutique ne touche jamais à ce champ, et inversement.

add_action( 'type_article_add_form_fields', 'mda_type_article_champ_image_ajout' );
function mda_type_article_champ_image_ajout( $taxonomy ) {
    ?>
    <div class="form-field">
        <label for="mda_type_image_id">Image vitrine de la catégorie</label>
        <input type="hidden" name="mda_type_image_id" id="mda_type_image_id" value="">
        <div id="mda_type_image_preview" style="margin:10px 0;"></div>
        <button type="button" class="button" id="mda_type_image_bouton">Choisir une image</button>
        <button type="button" class="button" id="mda_type_image_retirer" style="display:none;">Retirer</button>
        <p class="description">Image affichée sur la page d'accueil pour cette catégorie. N'est jamais remplacée par une photo de boutique.</p>
    </div>
    <?php
}

add_action( 'type_article_edit_form_fields', 'mda_type_article_champ_image_edition' );
function mda_type_article_champ_image_edition( $term ) {
    $image_id  = get_term_meta( $term->term_id, '_mda_type_image_id', true );
    $image_url = $image_id ? wp_get_attachment_image_url( $image_id, 'medium' ) : '';
    ?>
    <tr class="form-field">
        <th scope="row"><label for="mda_type_image_id">Image vitrine de la catégorie</label></th>
        <td>
            <input type="hidden" name="mda_type_image_id" id="mda_type_image_id" value="<?php echo esc_attr( $image_id ); ?>">
            <div id="mda_type_image_preview" style="margin-bottom:10px;">
                <?php if ( $image_url ) : ?>
                    <img src="<?php echo esc_url( $image_url ); ?>" style="max-width:200px;height:auto;display:block;">
                <?php endif; ?>
            </div>
            <button type="button" class="button" id="mda_type_image_bouton">Choisir une image</button>
            <button type="button" class="button" id="mda_type_image_retirer" style="<?php echo $image_id ? '' : 'display:none;'; ?>">Retirer</button>
            <p class="description">Image affichée sur la page d'accueil pour cette catégorie. N'est jamais remplacée par une photo de boutique.</p>
        </td>
    </tr>
    <?php
}

add_action( 'create_type_article', 'mda_type_article_sauver_image' );
add_action( 'edited_type_article', 'mda_type_article_sauver_image' );
function mda_type_article_sauver_image( $term_id ) {
    if ( ! isset( $_POST['mda_type_image_id'] ) ) {
        return;
    }
    $image_id = absint( $_POST['mda_type_image_id'] );
    if ( $image_id ) {
        update_term_meta( $term_id, '_mda_type_image_id', $image_id );
    } else {
        delete_term_meta( $term_id, '_mda_type_image_id' );
    }
}

add_action( 'admin_enqueue_scripts', 'mda_type_article_admin_media' );
function mda_type_article_admin_media( $hook ) {
    if ( ! in_array( $hook, array( 'edit-tags.php', 'term.php' ), true ) ) {
        return;
    }
    if ( ! isset( $_GET['taxonomy'] ) || 'type_article' !== $_GET['taxonomy'] ) {
        return;
    }
    wp_enqueue_media();
    wp_add_inline_script( 'jquery', mda_type_article_media_js() );
}
function mda_type_article_media_js() {
    ob_start();
    ?>
    jQuery(function($){
        var frame;
        $(document).on('click', '#mda_type_image_bouton', function(e){
            e.preventDefault();
            if (frame) { frame.open(); return; }
            frame = wp.media({
                title: 'Choisir une image vitrine',
                button: { text: 'Utiliser cette image' },
                multiple: false
            });
            frame.on('select', function(){
                var attachment = frame.state().get('selection').first().toJSON();
                var url = ( attachment.sizes && attachment.sizes.medium ) ? attachment.sizes.medium.url : attachment.url;
                $('#mda_type_image_id').val(attachment.id);
                $('#mda_type_image_preview').html('<img src="' + url + '" style="max-width:200px;height:auto;display:block;">');
                $('#mda_type_image_retirer').show();
            });
            frame.open();
        });
        $(document).on('click', '#mda_type_image_retirer', function(e){
            e.preventDefault();
            $('#mda_type_image_id').val('');
            $('#mda_type_image_preview').html('');
            $(this).hide();
        });
    });
    <?php
    return ob_get_clean();
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

// ===========================================================================
// INTERFACE D'ADMINISTRATION REGROUPÉE — "Marché des Arts"
// Menu unique regroupant Boutiques, Articles exposés, Types d'article,
// Matériaux, et un tableau de bord d'ensemble avec actions rapides.
// ===========================================================================

function mda_admin_menu() {
    add_menu_page(
        'Marché des Arts',
        'Marché des Arts',
        'edit_posts',
        'mda-dashboard',
        'mda_dashboard_page',
        'dashicons-store',
        3
    );
    add_submenu_page( 'mda-dashboard', 'Tableau de bord', 'Tableau de bord', 'edit_posts', 'mda-dashboard', 'mda_dashboard_page' );
}
add_action( 'admin_menu', 'mda_admin_menu' );

// Ajoute les sous-menus des taxonomies (Types d'article, Matériaux) dans le
// même regroupement, une fois que WordPress a fini d'enregistrer les menus
// natifs des CPT (sinon les liens "Ajouter" pointent vers le mauvais parent).
function mda_admin_submenus_taxonomies() {
    add_submenu_page( 'mda-dashboard', "Catégories", "Catégories", 'manage_categories', 'edit-tags.php?taxonomy=type_article&post_type=boutique' );
    add_submenu_page( 'mda-dashboard', 'Matériaux', 'Matériaux', 'manage_categories', 'edit-tags.php?taxonomy=materiau&post_type=oeuvre' );
    add_submenu_page( 'mda-dashboard', 'Avis (commentaires)', 'Avis (commentaires)', 'moderate_comments', 'edit-comments.php' );
}
add_action( 'admin_menu', 'mda_admin_submenus_taxonomies', 20 );

function mda_dashboard_page() {
    if ( ! current_user_can( 'edit_posts' ) ) {
        return;
    }

    // ---- Action : générer des exemples ----
    $message = '';
    if ( isset( $_POST['mda_generer_exemples'] ) && check_admin_referer( 'mda_generer_exemples_action', 'mda_generer_exemples_nonce' ) ) {
        $nb = mda_generer_boutiques_exemple();
        $message = "{$nb} boutique(s) d'exemple générée(s), avec leurs articles exposés. Modifiables ou supprimables comme n'importe quelle fiche.";
    }

    $nb_boutiques = wp_count_posts( 'boutique' )->publish;
    $nb_oeuvres   = wp_count_posts( 'oeuvre' )->publish;
    $nb_avis      = get_comments( array( 'post_type' => 'boutique', 'status' => 'approve', 'count' => true ) );
    $nb_kiosques  = count( mda_plan_kiosques_data() );
    ?>
    <div class="wrap mda-dashboard">
        <h1>Marché des Arts - Tableau de bord</h1>
        <p>Interface de gestion des fiches artisans, boutiques, articles exposés et informations associées.</p>

        <?php if ( $message ) : ?>
        <div class="notice notice-success"><p><?php echo esc_html( $message ); ?></p></div>
        <?php endif; ?>

        <div class="mda-stats-cards">
            <div class="mda-card">
                <span class="mda-card-num"><?php echo esc_html( $nb_boutiques ); ?></span>
                <span class="mda-card-label">Boutiques publiées</span>
            </div>
            <div class="mda-card">
                <span class="mda-card-num"><?php echo esc_html( $nb_oeuvres ); ?></span>
                <span class="mda-card-label">Articles exposés</span>
            </div>
            <div class="mda-card">
                <span class="mda-card-num"><?php echo esc_html( $nb_avis ); ?></span>
                <span class="mda-card-label">Avis approuvés</span>
            </div>
            <div class="mda-card">
                <span class="mda-card-num"><?php echo esc_html( $nb_kiosques ); ?> / 225</span>
                <span class="mda-card-label">Kiosques occupés sur le plan</span>
            </div>
        </div>

        <h2>Actions rapides</h2>
        <p class="mda-actions">
            <a class="button button-primary" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=boutique' ) ); ?>">+ Ajouter une boutique</a>
            <a class="button" href="<?php echo esc_url( admin_url( 'post-new.php?post_type=oeuvre' ) ); ?>">+ Ajouter un article exposé</a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=boutique' ) ); ?>">Voir toutes les boutiques</a>
            <a class="button" href="<?php echo esc_url( admin_url( 'edit.php?post_type=oeuvre' ) ); ?>">Voir tous les articles</a>
        </p>

        <h2>Contenus d'exemple</h2>
        <p>Génère 3 boutiques complètes (profil, type d'article, numéro de kiosque) avec 2 articles exposés chacune, pour visualiser et tester l'ensemble des fonctionnalités avant de saisir les vraies données. Utilise les photos déjà fournies avec le thème.</p>
        <form method="post">
            <?php wp_nonce_field( 'mda_generer_exemples_action', 'mda_generer_exemples_nonce' ); ?>
            <button type="submit" name="mda_generer_exemples" class="button button-secondary">Générer 3 boutiques d'exemple avec produits</button>
        </form>

        <h2>Boutiques récentes</h2>
        <?php
        $recentes = get_posts( array( 'post_type' => 'boutique', 'posts_per_page' => 5, 'post_status' => array( 'publish', 'draft' ) ) );
        if ( $recentes ) :
        ?>
        <table class="widefat striped" style="max-width:800px;">
            <thead><tr><th>Boutique</th><th>Statut</th><th>Kiosque</th><th></th></tr></thead>
            <tbody>
            <?php foreach ( $recentes as $b ) :
                $kiosque = get_post_meta( $b->ID, '_mda_kiosque', true );
                ?>
                <tr>
                    <td><?php echo esc_html( $b->post_title ); ?></td>
                    <td><?php echo esc_html( $b->post_status ); ?></td>
                    <td><?php echo $kiosque ? esc_html( $kiosque ) : '\u2014'; ?></td>
                    <td><a href="<?php echo esc_url( get_edit_post_link( $b->ID ) ); ?>">Modifier</a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php else : ?>
        <p><em>Aucune boutique pour le moment.</em></p>
        <?php endif; ?>
    </div>

    <style>
        .mda-stats-cards{display:flex;gap:16px;flex-wrap:wrap;margin:20px 0;}
        .mda-card{background:#fff;border:1px solid #E5E7EB;border-radius:10px;padding:18px 24px;min-width:160px;}
        .mda-card-num{display:block;font-size:30px;font-weight:800;color:#1A5C28;}
        .mda-card-label{display:block;font-size:12.5px;color:#6A7282;margin-top:4px;}
        .mda-actions .button{margin-right:8px;}
        .mda-dashboard h2{margin-top:34px;}
    </style>
    <?php
}

// ---------------------------------------------------------------------
// Génération de contenus d'exemple (bouton du tableau de bord)
// ---------------------------------------------------------------------
function mda_sideload_theme_image( $relative_path, $post_id_to_attach, $desc ) {
    $file_path = get_stylesheet_directory() . '/' . ltrim( $relative_path, '/' );
    if ( ! file_exists( $file_path ) ) {
        return 0;
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $upload_dir = wp_upload_dir();
    $filename   = wp_unique_filename( $upload_dir['path'], basename( $file_path ) );
    $new_path   = $upload_dir['path'] . '/' . $filename;
    copy( $file_path, $new_path );

    $filetype = wp_check_filetype( $filename, null );
    $attachment = array(
        'post_mime_type' => $filetype['type'],
        'post_title'     => sanitize_text_field( $desc ),
        'post_status'    => 'inherit',
    );
    $attach_id = wp_insert_attachment( $attachment, $new_path, $post_id_to_attach );
    $attach_data = wp_generate_attachment_metadata( $attach_id, $new_path );
    wp_update_attachment_metadata( $attach_id, $attach_data );
    return $attach_id;
}

function mda_generer_boutiques_exemple() {
    $jeux = array(
        array(
            'titre'      => 'Atelier Agbodjè (exemple)',
            'metier'     => 'Maître Sculpteur sur Bois',
            'experience' => '12 ans',
            'statut'     => 'Certifié',
            'type'       => 'Sculptures',
            'materiau'   => 'Bois',
            'histoire'   => "Depuis plus de dix ans, l'atelier travaille l'ébène et l'iroko pour sculpter des masques et statuettes inspirés des traditions du Dahomey. Chaque pièce est façonnée entièrement à la main.",
            'description'=> "Sculptures en bois sculptés à la main, transmettant la sagesse des ancêtres.",
            'kiosque'    => 1,
            'image'      => 'assets/boutique-1.jpg',
            'produits'   => array(
                array( 'nom' => 'Masque Cérémoniel Egungun (exemple)', 'image' => 'assets/types/sculptures.jpg' ),
                array( 'nom' => 'Tête Royale d\'Oba (exemple)', 'image' => 'assets/types/sculptures.jpg' ),
            ),
        ),
        array(
            'titre'      => 'Maison Kente (exemple)',
            'metier'     => 'Tisserande',
            'experience' => '8 ans',
            'statut'     => 'Certifié',
            'type'       => 'Vêtements',
            'materiau'   => 'Textile',
            'histoire'   => "Les tissus kente et wax de cette boutique sont tissés selon des techniques transmises de mère en fille depuis trois générations, avec des motifs propres à chaque famille.",
            'description'=> "Tissus traditionnels et créations contemporaines aux couleurs vives.",
            'kiosque'    => 2,
            'image'      => 'assets/boutique-2.jpg',
            'produits'   => array(
                array( 'nom' => 'Pagne Wax Premium (exemple)', 'image' => 'assets/types/vetements.jpg' ),
            ),
        ),
        array(
            'titre'      => 'Bijoux Touareg (exemple)',
            'metier'     => 'Bijoutière',
            'experience' => '6 ans',
            'statut'     => 'Nouveau membre',
            'type'       => 'Bijoux',
            'materiau'   => 'Bijoux',
            'histoire'   => "Pendentifs, colliers et bagues en argent, inspirés des traditions nomades du Sahel et retravaillés avec une sensibilité contemporaine.",
            'description'=> "Bijoux en argent et accessoires inspirés des traditions nomades.",
            'kiosque'    => 3,
            'image'      => 'assets/boutique-4.jpg',
            'produits'   => array(
                array( 'nom' => 'Collier Argent Ciseleur (exemple)', 'image' => 'assets/types/bijoux.jpg' ),
                array( 'nom' => 'Bague Traditionnelle (exemple)', 'image' => 'assets/types/bijoux.jpg' ),
            ),
        ),
    );

    $cree = 0;

    foreach ( $jeux as $b ) {
        // Évite les doublons si le bouton est cliqué plusieurs fois.
        $deja_existant = new WP_Query( array(
            'post_type'      => 'boutique',
            'title'          => $b['titre'],
            'posts_per_page' => 1,
            'post_status'    => 'any',
            'fields'         => 'ids',
        ) );
        if ( $deja_existant->have_posts() ) {
            continue;
        }

        $boutique_id = wp_insert_post( array(
            'post_type'    => 'boutique',
            'post_title'   => $b['titre'],
            'post_content' => $b['histoire'],
            'post_status'  => 'publish',
        ) );
        if ( is_wp_error( $boutique_id ) || ! $boutique_id ) {
            continue;
        }

        update_post_meta( $boutique_id, '_mda_metier', $b['metier'] );
        update_post_meta( $boutique_id, '_mda_experience', $b['experience'] );
        update_post_meta( $boutique_id, '_mda_statut', $b['statut'] );
        update_post_meta( $boutique_id, '_mda_description', $b['description'] );
        update_post_meta( $boutique_id, '_mda_kiosque', mda_prochain_kiosque_libre( $b['kiosque'] ) );
        update_post_meta( $boutique_id, '_mda_visites', 0 );

        wp_set_object_terms( $boutique_id, $b['type'], 'type_article' );

        $img_id = mda_sideload_theme_image( $b['image'], $boutique_id, $b['titre'] );
        if ( $img_id ) {
            set_post_thumbnail( $boutique_id, $img_id );
        }

        foreach ( $b['produits'] as $p ) {
            $oeuvre_id = wp_insert_post( array(
                'post_type'   => 'oeuvre',
                'post_title'  => $p['nom'],
                'post_status' => 'publish',
            ) );
            if ( is_wp_error( $oeuvre_id ) || ! $oeuvre_id ) {
                continue;
            }
            update_post_meta( $oeuvre_id, '_mda_boutique_id', $boutique_id );
            wp_set_object_terms( $oeuvre_id, $b['materiau'], 'materiau' );
            $pimg_id = mda_sideload_theme_image( $p['image'], $oeuvre_id, $p['nom'] );
            if ( $pimg_id ) {
                set_post_thumbnail( $oeuvre_id, $pimg_id );
            }
        }

        $cree++;
    }

    return $cree;
}

function mda_prochain_kiosque_libre( $depart ) {
    for ( $n = $depart; $n <= 225; $n++ ) {
        if ( ! mda_kiosque_deja_pris( $n, 0 ) ) {
            return $n;
        }
    }
    return $depart;
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

// Lien de l'icône "compte" dans le header — utilisé uniquement quand le
// visiteur n'est PAS connecté (redirige vers la connexion). Quand il est
// connecté, l'icône ouvre un panneau profil (voir header.php) au lieu de
// déconnecter directement au clic.
function mda_account_icon_link() {
    return home_url( '/connexion/' );
}

// Upload générique d'un champ <input type="file"> vers la médiathèque,
// rattaché à un post. Retourne l'ID de la pièce jointe, ou 0 si vide/erreur.
function mda_handle_single_upload( $field_name, $post_id ) {
    if ( empty( $_FILES[ $field_name ]['name'] ) ) {
        return 0;
    }
    require_once ABSPATH . 'wp-admin/includes/image.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/media.php';

    $attach_id = media_handle_upload( $field_name, $post_id );
    return is_wp_error( $attach_id ) ? 0 : $attach_id;
}