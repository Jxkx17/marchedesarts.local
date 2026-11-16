<?php
/**
 * Marché des Arts du Bénin — point d'entrée du thème.
 *
 * Toute la logique est découpée par domaine dans le dossier inc/.
 *
 * @package MarcheDesArts
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$mda_inc = get_template_directory() . '/inc/';

require_once $mda_inc . 'setup.php';                 // Supports, menus, styles.
require_once $mda_inc . 'helpers.php';               // Utilitaires partagés.
require_once $mda_inc . 'cpt-boutique.php';          // CPT Boutique + meta boxes.
require_once $mda_inc . 'taxonomy-type-article.php'; // Taxonomie Types d'article.
require_once $mda_inc . 'cpt-oeuvre.php';            // CPT Articles exposés.
require_once $mda_inc . 'avis.php';                  // Avis et notes.
require_once $mda_inc . 'recherche.php';             // Recherche.
require_once $mda_inc . 'plan.php';                  // Plan des kiosques (Leaflet).
require_once $mda_inc . 'stats.php';                 // Statistiques et visites.
require_once $mda_inc . 'admin.php';                 // Tableau de bord admin.
require_once $mda_inc . 'comptes.php';               // Rôles, accès, comptes.
