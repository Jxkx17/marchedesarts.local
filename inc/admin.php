<?php
/**
 * Interface d'administration regroupée : menu, tableau de bord, génération de contenus d'exemple.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

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

