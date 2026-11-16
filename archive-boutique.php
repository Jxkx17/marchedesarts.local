<?php get_header(); ?>

<section class="boutiques-hero">
  <div class="breadcrumb">
    <a href="<?php echo home_url('/'); ?>">Accueil</a> &rsaquo; <span>Boutiques</span>
  </div>

  <h1>Toutes les Boutiques</h1>
  <p class="boutiques-intro">Explorez notre sélection de boutiques artisanales authentiques du Bénin. Chaque boutique raconte une histoire unique.</p>

  <div class="boutiques-banner">
    <span class="eyebrow">À la une</span>
    <h2>Actualités du marché des arts</h2>
    <p>Rencontrez les maîtres qui font vivre l'artisanat béninois. Des techniques ancestrales transmises de génération en génération.</p>
  </div>

  <form class="boutiques-search" method="get" action="<?php echo esc_url( get_post_type_archive_link( 'boutique' ) ); ?>">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#99A1AF" stroke-width="2">
      <circle cx="11" cy="11" r="7"></circle>
      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
    <input type="text" name="recherche" placeholder="Rechercher une boutique, un type d'article, un matériau..." value="<?php echo isset($_GET['recherche']) ? esc_attr($_GET['recherche']) : ''; ?>">
    <button type="submit">Rechercher</button>
  </form>

  <?php if ( ! empty( $_GET['type_slug'] ) ) :
      $terme_actif = get_term_by( 'slug', sanitize_title( $_GET['type_slug'] ), 'type_article' );
      if ( $terme_actif ) :
      ?>
      <div class="active-filter">
        Filtré par : <strong><?php echo esc_html( $terme_actif->name ); ?></strong>
        <a href="<?php echo esc_url( get_post_type_archive_link( 'boutique' ) ); ?>">&times; Retirer le filtre</a>
      </div>
  <?php endif; endif; ?>
</section>

<section class="boutiques-grid-section">
  <div class="boutiques-grid-3">
    <?php if ( have_posts() ) : while ( have_posts() ) : the_post();
        $emplacement = get_post_meta( get_the_ID(), '_mda_emplacement', true );
        $types_terms = get_the_terms( get_the_ID(), 'type_article' );
        $note_stats  = mda_boutique_rating_stats( get_the_ID() );
        ?>
        <article class="boutique-card-alt">
          <a href="<?php the_permalink(); ?>" class="thumb">
            <?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium_large' ); else : ?>
              <img src="<?php echo get_template_directory_uri(); ?>/assets/placeholder-boutique.jpg" alt="<?php the_title_attribute(); ?>">
            <?php endif; ?>
            <span class="badge-rating-mini"><span class="star">&#9733;</span> <?php echo esc_html( $note_stats['average'] ); ?></span>
            <?php if ( $emplacement ) : ?>
            <span class="badge-code"><?php echo esc_html( $emplacement ); ?></span>
            <?php endif; ?>
          </a>
          <div class="boutique-alt-body">
            <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
            <?php if ( $types_terms && ! is_wp_error( $types_terms ) ) : ?>
            <span class="role role-chip"><?php echo esc_html( implode( ' · ', wp_list_pluck( $types_terms, 'name' ) ) ); ?></span>
            <?php endif; ?>
          </div>
        </article>
    <?php endwhile; else : ?>
      <p>Aucune boutique ne correspond à cette recherche.</p>
    <?php endif; ?>
  </div>

  <div class="boutiques-pagination">
    <?php
    echo paginate_links( array(
        'total'     => $GLOBALS['wp_query']->max_num_pages,
        'prev_text' => '&larr;',
        'next_text' => '&rarr;',
    ) );
    ?>
  </div>
</section>

<?php get_footer(); ?>
