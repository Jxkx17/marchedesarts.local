<?php
/**
 * Fonctions utilitaires : lien de contact, lien icône compte, upload de fichier.
 *
 * @package MarcheDesArts
 */

if ( ! defined( "ABSPATH" ) ) {
    exit;
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
