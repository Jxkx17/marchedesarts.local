<?php
/**
 * WordPress utilise ce gabarit automatiquement pour une Page dont le slug est "confidentialite".
 * Créer une Page intitulée "Politique de confidentialité" dans l'admin suffit.
 * Le contenu (texte) se rédige normalement dans l'éditeur de la Page.
 */
get_header();
?>

<section class="contact-hero">
  <span class="eyebrow">Vos données</span>
  <h1>Politique de confidentialité</h1>
</section>

<section class="mda-legal-wrap">
  <div class="breadcrumb breadcrumb-fiche">
    <a href="<?php echo home_url('/'); ?>">Accueil</a> &rsaquo; <span>Politique de confidentialité</span>
  </div>

  <div class="mda-legal-content">
    <?php
    while ( have_posts() ) :
        the_post();
        the_content();
    endwhile;
    ?>
  </div>
</section>

<?php get_footer(); ?>
