<?php get_header(); ?>

<?php
$hero_query = new WP_Query( array(
    'post_type'           => 'boutique',
    'posts_per_page'      => 12,
    'post_status'         => 'publish',
    'orderby'             => 'date',
    'order'               => 'DESC',
    'ignore_sticky_posts' => true,
) );

// Ne retenir que les boutiques ayant réellement une image mise en avant.
$hero_real_slides = array();
if ( $hero_query->have_posts() ) {
    while ( $hero_query->have_posts() ) {
        $hero_query->the_post();
        if ( has_post_thumbnail() ) {
            $hero_real_slides[] = get_the_ID();
        }
        if ( count( $hero_real_slides ) >= 6 ) {
            break;
        }
    }
    wp_reset_postdata();
}

// Secours : tant qu'aucune boutique publiée n'a d'image, le diaporama
// utilise les 4 photos statiques déjà fournies (assets/boutique-1.jpg à 4.jpg).
$hero_fallback = array();
if ( empty( $hero_real_slides ) ) {
    $fallback_data = array(
        array( 'Atelier Agbodjè', 'boutique-1.jpg' ),
        array( 'Maison Kente', 'boutique-2.jpg' ),
        array( "Couleurs d'Afrique", 'boutique-3.jpg' ),
        array( 'Bijoux Touareg', 'boutique-4.jpg' ),
    );
    foreach ( $fallback_data as $fb ) {
        if ( file_exists( get_stylesheet_directory() . '/assets/' . $fb[1] ) ) {
            $hero_fallback[] = $fb;
        }
    }
}
?>

<section class="hero" id="hero-slideshow" style="position:relative;width:100%;height:640px;overflow:hidden;background:#F3F4F6;">
  <?php
  $hero_id = get_theme_mod( 'mda_hero_image' );
  $has_static_hero = file_exists( get_stylesheet_directory() . '/assets/hero-marche.jpg' );

  if ( $hero_id || $has_static_hero ) :
  ?>
  <div class="hero-slide active" data-slide="0" style="position:absolute;inset:0;opacity:1;transition:opacity 0.9s ease;">
    <?php
    if ( $hero_id ) {
        echo wp_get_attachment_image( $hero_id, 'full', false, array( 'style' => 'width:100%;height:100%;object-fit:cover;' ) );
    } else {
        echo '<img src="' . get_template_directory_uri() . '/assets/hero-marche.jpg" alt="Marché des Arts du Bénin" style="width:100%;height:100%;object-fit:cover;">';
    }
    ?>
  </div>
  <?php
  $i = 1;
  else :
  $i = 0;
  endif;
  ?>

  <?php
  if ( ! empty( $hero_real_slides ) ) :
      foreach ( $hero_real_slides as $boutique_id ) :
      ?>
      <div class="hero-slide<?php echo $i === 0 ? ' active' : ''; ?>" data-slide="<?php echo $i; ?>" style="position:absolute;inset:0;opacity:<?php echo $i === 0 ? '1' : '0'; ?>;transition:opacity 0.9s ease;">
        <?php echo get_the_post_thumbnail( $boutique_id, 'full', array( 'style' => 'width:100%;height:100%;object-fit:cover;' ) ); ?>
        <div class="hero-slide-caption">
          <span><?php echo esc_html( get_the_title( $boutique_id ) ); ?></span>
        </div>
      </div>
      <?php
      $i++;
      endforeach;
  else :
      foreach ( $hero_fallback as $fb ) :
      ?>
      <div class="hero-slide<?php echo $i === 0 ? ' active' : ''; ?>" data-slide="<?php echo $i; ?>" style="position:absolute;inset:0;opacity:<?php echo $i === 0 ? '1' : '0'; ?>;transition:opacity 0.9s ease;">
        <img src="<?php echo esc_url( get_template_directory_uri() . '/assets/' . $fb[1] ); ?>" alt="<?php echo esc_attr( $fb[0] ); ?>" style="width:100%;height:100%;object-fit:cover;">
        <div class="hero-slide-caption">
          <span><?php echo esc_html( $fb[0] ); ?></span>
        </div>
      </div>
      <?php
      $i++;
      endforeach;
  endif;

  $slide_count = max( $i, 1 );
  ?>

  <?php if ( $slide_count > 1 ) : ?>
  <button class="hero-nav prev" aria-label="Précédent">&#10094;</button>
  <button class="hero-nav next" aria-label="Suivant">&#10095;</button>
  <div class="hero-dots">
    <?php for ( $d = 0; $d < $slide_count; $d++ ) : ?>
      <span class="hero-dot<?php echo $d === 0 ? ' active' : ''; ?>" data-slide="<?php echo $d; ?>"></span>
    <?php endfor; ?>
  </div>
  <?php endif; ?>
</section>

<?php if ( $slide_count > 1 ) : ?>
<script>
(function(){
  var root = document.getElementById('hero-slideshow');
  var slides = root.querySelectorAll('.hero-slide');
  var dots = root.querySelectorAll('.hero-dot');
  var current = 0;
  var total = slides.length;
  var timer;

  function show(index){
    slides[current].style.opacity = '0';
    slides[current].classList.remove('active');
    if (dots.length) dots[current].classList.remove('active');
    current = (index + total) % total;
    slides[current].style.opacity = '1';
    slides[current].classList.add('active');
    if (dots.length) dots[current].classList.add('active');
  }
  function next(){ show(current + 1); }
  function prev(){ show(current - 1); }
  function restart(){ clearInterval(timer); timer = setInterval(next, 5500); }

  root.querySelector('.hero-nav.next').addEventListener('click', function(){ next(); restart(); });
  root.querySelector('.hero-nav.prev').addEventListener('click', function(){ prev(); restart(); });
  dots.forEach(function(dot){
    dot.addEventListener('click', function(){ show(parseInt(dot.dataset.slide, 10)); restart(); });
  });

  restart();
})();
</script>
<?php endif; ?>

<?php $mda_stats = mda_global_stats(); ?>
<section class="stats-band" id="stats-band">
  <div class="stat-item">
    <span class="stat-number" data-count="<?php echo esc_attr( $mda_stats['boutiques'] ); ?>">0</span>
    <span class="stat-label">Boutiques d'artisans</span>
  </div>
  <div class="stat-item">
    <span class="stat-number" data-count="<?php echo esc_attr( $mda_stats['oeuvres'] ); ?>">0</span>
    <span class="stat-label">Articles exposés</span>
  </div>
  <div class="stat-item">
    <span class="stat-number" data-count="<?php echo esc_attr( $mda_stats['avis'] ); ?>">0</span>
    <span class="stat-label">Avis des clients</span>
  </div>
  <div class="stat-item">
    <span class="stat-number" data-count="<?php echo esc_attr( $mda_stats['note_moyenne'] ); ?>" data-decimal="1">0</span>
    <span class="stat-label">Note moyenne</span>
  </div>
</section>

<script>
(function(){
  var band = document.getElementById('stats-band');
  if (!band) return;
  var done = false;

  function animate(){
    if (done) return;
    done = true;
    band.querySelectorAll('.stat-number').forEach(function(el){
      var target = parseFloat(el.dataset.count) || 0;
      var decimal = parseInt(el.dataset.decimal || '0', 10);
      var start = 0;
      var duration = 1200;
      var startTime = null;
      function step(ts){
        if (!startTime) startTime = ts;
        var progress = Math.min((ts - startTime) / duration, 1);
        var value = start + (target - start) * progress;
        el.textContent = decimal ? value.toFixed(decimal) : Math.round(value);
        if (progress < 1) requestAnimationFrame(step);
        else el.textContent = decimal ? target.toFixed(decimal) : target;
      }
      requestAnimationFrame(step);
    });
  }

  if ('IntersectionObserver' in window) {
    var obs = new IntersectionObserver(function(entries){
      entries.forEach(function(entry){ if (entry.isIntersecting) animate(); });
    }, { threshold: 0.4 });
    obs.observe(band);
  } else {
    animate();
  }
})();
</script>

<section class="section-boutiques">
  <div class="section-head">
    <h2>Boutiques les plus consultées</h2>
    <a href="<?php echo home_url('/boutiques'); ?>" class="see-all">Toutes les boutiques &rsaquo;</a>
  </div>

  <div class="boutiques-grid">
    <?php
    $query = new WP_Query( array(
        'post_type'      => 'boutique',
        'posts_per_page' => 8,
        'meta_key'       => '_mda_visites',
        'orderby'        => 'meta_value_num',
        'order'          => 'DESC',
    ) );

    if ( $query->have_posts() ) :
        while ( $query->have_posts() ) : $query->the_post();
            $note_stats  = mda_boutique_rating_stats( get_the_ID() );
            $note        = $note_stats['average'];
            $emplacement = get_post_meta( get_the_ID(), '_mda_emplacement', true );
            $types_terms = get_the_terms( get_the_ID(), 'type_article' );
            $description = get_post_meta( get_the_ID(), '_mda_description', true );
            ?>
            <article class="boutique-card">
              <div class="thumb">
                <a href="<?php the_permalink(); ?>"><?php the_post_thumbnail( 'medium_large' ); ?></a>
                <span class="badge-rating">&#9733; <?php echo esc_html( $note ); ?></span>
                <?php if ( $emplacement ) : ?>
                <span class="badge-location">&#128205; <?php echo esc_html( $emplacement ); ?></span>
                <?php endif; ?>
              </div>
              <div class="boutique-body">
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
                <?php if ( $types_terms && ! is_wp_error( $types_terms ) ) : ?>
                <span class="tag-chip"><?php echo esc_html( implode( ' · ', wp_list_pluck( $types_terms, 'name' ) ) ); ?></span>
                <?php endif; ?>
                <?php if ( $description ) : ?>
                <p><?php echo esc_html( wp_trim_words( $description, 20 ) ); ?></p>
                <?php endif; ?>
              </div>
            </article>
            <?php
        endwhile;
        wp_reset_postdata();
    else :
        // Contenu de démonstration si aucune boutique n'est encore créée
        $demo = array(
            array('Atelier Agbodjè','Sculptures & Art','4.8','A1-01',"Sculptures en bois sculpté à la main, transmettant la sagesse des ancêtres. Chaque pièce est unique, réalisée da...",'boutique-1.jpg'),
            array('Maison Kente','Tissage & Textile','4.9','A2-04',"Tissus kente traditionnels et créations contemporaines. Des étoffes colorées pour habiller votre espace et votre qu...",'boutique-2.jpg'),
            array('Couleurs d\'Afrique','Peintures & Design','4.7','A1-03',"Peintures contemporaines et art moderne inspirés du patrimoine visuel béninois. Toiles vibrantes et composit...",'boutique-3.jpg'),
            array('Bijoux Touareg','Bijoux & Accessoires','4.6','A3-09',"Bijoux en argent et accessoires du désert. Pendentifs, colliers et bagues inspirés des traditions nomades du S...",'boutique-4.jpg'),
        );
        foreach ( $demo as $d ) :
        ?>
        <article class="boutique-card">
          <div class="thumb">
            <img src="<?php echo get_template_directory_uri(); ?>/assets/<?php echo esc_attr( $d[5] ); ?>" alt="<?php echo esc_attr( $d[0] ); ?>">
            <span class="badge-rating">&#9733; <?php echo esc_html( $d[2] ); ?></span>
            <span class="badge-location">&#128205; <?php echo esc_html( $d[3] ); ?></span>
          </div>
          <div class="boutique-body">
            <h3><?php echo esc_html( $d[0] ); ?></h3>
            <span class="tag-chip"><?php echo esc_html( $d[1] ); ?></span>
            <p><?php echo esc_html( $d[4] ); ?></p>
          </div>
        </article>
        <?php endforeach;
    endif;
    ?>
  </div>
</section>

<section class="section-types">
  <h2>Catégories</h2>
  <div class="types-grid">
    <?php
    $type_colors = array( '#D4681A', '#FFB347', '#6B4423', '#1A5C28', '#101828', '#8D3C1E', '#6B4423' );
    $fallback_files = array(
        'peintures-dessins' => 'peintures.jpg',
        'bijoux'            => 'bijoux.jpg',
        'peaux'             => 'peaux.png',
        'sacs'              => 'sacs.jpg',
        'vetements'         => 'vetements.jpg',
        'sculptures'        => 'sculptures.jpg',
        'decoration'        => 'decoration.jpg',
        'accessoires'       => 'accessoires.jpg',
    );
    $types = get_terms( array( 'taxonomy' => 'type_article', 'hide_empty' => false ) );
    if ( ! is_wp_error( $types ) ) :
        foreach ( $types as $index => $t ) :
            $fallback = isset( $fallback_files[ $t->slug ] ) ? $fallback_files[ $t->slug ] : 'placeholder-boutique.jpg';
            $color    = $type_colors[ $index % count( $type_colors ) ];
            ?>
            <a href="<?php echo esc_url( home_url( '/boutiques?type_slug=' . $t->slug ) ); ?>" class="type-card">
              <?php echo mda_type_card_media( $t->term_id, $fallback, $color ); ?>
              <span><?php echo esc_html( $t->name ); ?></span>
            </a>
            <?php
        endforeach;
    endif;
    ?>
  </div>
</section>

<?php get_footer(); ?>