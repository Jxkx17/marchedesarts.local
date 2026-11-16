<?php get_header(); ?>

<?php $mda_mot = get_search_query(); ?>

<section class="boutiques-hero">
  <div class="breadcrumb">
    <a href="<?php echo home_url('/'); ?>">Accueil</a> &rsaquo; <span>Résultats de recherche</span>
  </div>

  <h1>Résultats pour « <?php echo esc_html( $mda_mot ); ?> »</h1>
  <p class="boutiques-intro">
    <?php if ( have_posts() ) : ?>
      <?php echo esc_html( $GLOBALS['wp_query']->found_posts ); ?> résultat(s) trouvé(s).
    <?php else : ?>
      Aucun résultat pour cette recherche.
    <?php endif; ?>
  </p>

  <form class="boutiques-search" method="get" action="<?php echo home_url('/'); ?>">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#99A1AF" stroke-width="2">
      <circle cx="11" cy="11" r="7"></circle>
      <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
    </svg>
    <input type="text" name="s" placeholder="Rechercher une boutique, un type d'article, un matériau..." value="<?php echo esc_attr( $mda_mot ); ?>">
    <button type="submit">Rechercher</button>
  </form>
</section>

<section class="boutiques-grid-section">
  <?php if ( have_posts() ) : ?>

    <div class="boutiques-grid-3">
      <?php while ( have_posts() ) : the_post();

          if ( 'boutique' === get_post_type() ) :
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
              <?php
          else :
              // Résultat générique (Page : Contact, etc.)
              ?>
              <article class="search-page-card">
                <span class="search-page-tag">Page</span>
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <p><?php echo esc_html( wp_trim_words( get_the_excerpt() ?: get_the_content(), 24 ) ); ?></p>
                <a href="<?php the_permalink(); ?>" class="btn-contacter search-page-link">Voir la page</a>
              </article>
              <?php
          endif;
      endwhile; ?>
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

  <?php else : ?>

    <div class="search-empty">
      <p>Aucune boutique ni page ne correspond à « <?php echo esc_html( $mda_mot ); ?> ».</p>
      <p>Essayez un autre mot-clé, ou parcourez <a href="<?php echo esc_url( get_post_type_archive_link( 'boutique' ) ); ?>">toutes les boutiques</a>.</p>
    </div>

  <?php endif; ?>
</section>

<?php get_footer(); ?>
