<?php
/**
 * Taxonomie Types d'article : enregistrement, termes de départ, image vitrine par catégorie.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
}

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
