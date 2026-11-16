<?php get_header(); ?>

<?php while ( have_posts() ) : the_post();
    $stats       = mda_boutique_rating_stats( get_the_ID() );
    $note        = $stats['average'];
    $avis_count  = $stats['total'];
    $emplacement = get_post_meta( get_the_ID(), '_mda_emplacement', true );
    $metier      = get_post_meta( get_the_ID(), '_mda_metier', true );
    $experience  = get_post_meta( get_the_ID(), '_mda_experience', true );
    $statut      = get_post_meta( get_the_ID(), '_mda_statut', true );
    $description = get_post_meta( get_the_ID(), '_mda_description', true );
    $img1        = get_post_meta( get_the_ID(), '_mda_histoire_img1', true );
    $img2        = get_post_meta( get_the_ID(), '_mda_histoire_img2', true );

    $oeuvres = new WP_Query( array(
        'post_type'      => 'oeuvre',
        'posts_per_page' => -1,
        'meta_key'       => '_mda_boutique_id',
        'meta_value'     => get_the_ID(),
        'orderby'        => 'date',
        'order'          => 'DESC',
    ) );

    $boutique_id_courante = get_the_ID();
    ?>

    <div class="breadcrumb breadcrumb-fiche">
      <a href="<?php echo home_url('/'); ?>">Accueil</a> &rsaquo;
      <a href="<?php echo esc_url( get_post_type_archive_link('boutique') ); ?>">Boutiques</a> &rsaquo;
      <span><?php the_title(); ?></span>
    </div>

    <section class="fiche-hero">
      <div class="fiche-hero-photo">
        <?php the_post_thumbnail( 'medium_large' ); ?>
      </div>
      <div class="fiche-hero-content">
        <div class="fiche-rating">
          <?php
          $full = round( (float) $note );
          for ( $s = 1; $s <= 5; $s++ ) echo $s <= $full ? '&#9733;' : '&#9734;';
          ?>
          <strong><?php echo esc_html( $note ); ?></strong>
          <span>(<?php echo esc_html( $avis_count ); ?> Avis)</span>
        </div>

        <h1><?php the_title(); ?></h1>

        <?php if ( $metier || $emplacement ) : ?>
        <p class="fiche-tagline">
          <?php echo esc_html( $metier ); ?><?php if ( $metier && $emplacement ) echo ' &middot; '; ?><?php echo $emplacement ? 'Boutique ' . esc_html( $emplacement ) : ''; ?>
        </p>
        <?php endif; ?>

        <?php if ( $description ) : ?>
        <p class="fiche-desc"><?php echo esc_html( $description ); ?></p>
        <?php endif; ?>

        <div class="fiche-stats">
          <div><span class="label">ŒUVRES</span><strong><?php echo esc_html( $oeuvres->found_posts ); ?></strong></div>
          <?php if ( $experience ) : ?><div><span class="label">EXPÉRIENCE</span><strong><?php echo esc_html( $experience ); ?></strong></div><?php endif; ?>
          <?php if ( $statut ) : ?><div><span class="label">STATUT</span><strong class="statut-pill"><?php echo esc_html( strtoupper( $statut ) ); ?></strong></div><?php endif; ?>
        </div>

        <a href="<?php echo esc_url( mda_contact_link( get_the_ID(), 'Bonjour, je vous contacte depuis Marché des Arts au sujet de ' . get_the_title() ) ); ?>" target="_blank" rel="noopener" class="btn-contact-artisan">CONTACTER L'ARTISAN</a>
      </div>
    </section>

    <section class="fiche-histoire">
      <h2>L'histoire</h2>
      <div class="fiche-histoire-text">
        <?php the_content(); ?>
      </div>
      <?php if ( $img1 || $img2 ) : ?>
      <div class="fiche-histoire-images">
        <?php if ( $img1 ) : ?><img src="<?php echo esc_url( $img1 ); ?>" alt=""><?php endif; ?>
        <?php if ( $img2 ) : ?><img src="<?php echo esc_url( $img2 ); ?>" alt=""><?php endif; ?>
      </div>
      <?php endif; ?>
    </section>

    <section class="fiche-oeuvres">
      <?php
      $oeuvre_ids_courants = $oeuvres->have_posts() ? wp_list_pluck( $oeuvres->posts, 'ID' ) : array();
      $terms = $oeuvre_ids_courants
          ? get_terms( array(
              'taxonomy'   => 'materiau',
              'object_ids' => $oeuvre_ids_courants,
            ) )
          : array();
      if ( ! is_wp_error( $terms ) && $terms ) :
      ?>
      <div class="oeuvres-toolbar">
        <div class="oeuvres-filters">
          <button class="filtre-btn active" data-filtre="tous">Tous</button>
          <?php foreach ( $terms as $t ) : ?>
          <button class="filtre-btn" data-filtre="<?php echo esc_attr( $t->slug ); ?>"><?php echo esc_html( $t->name ); ?></button>
          <?php endforeach; ?>
        </div>
        <div class="oeuvres-sort">
          <label>Trier par :</label>
          <select id="oeuvres-tri">
            <option value="recent">Plus récent</option>
            <option value="ancien">Plus ancien</option>
            <option value="alpha">Alphabétique</option>
          </select>
        </div>
      </div>
      <?php endif; ?>

      <div class="oeuvres-grid" id="oeuvres-grid">
        <?php if ( $oeuvres->have_posts() ) : while ( $oeuvres->have_posts() ) : $oeuvres->the_post();
            $o_terms = get_the_terms( get_the_ID(), 'materiau' );
            $slugs = array();
            $label = '';
            if ( $o_terms && ! is_wp_error( $o_terms ) ) {
                foreach ( $o_terms as $ot ) { $slugs[] = $ot->slug; }
                $label = count( $o_terms ) > 1 ? 'Mixte' : $o_terms[0]->name;
            }
            ?>
            <article class="oeuvre-card" data-materiau="<?php echo esc_attr( implode( ' ', $slugs ) ); ?>" data-date="<?php echo esc_attr( get_the_date('U') ); ?>" data-titre="<?php echo esc_attr( get_the_title() ); ?>">
              <div class="oeuvre-thumb"><?php the_post_thumbnail( 'medium_large' ); ?></div>
              <div class="oeuvre-body">
                <?php if ( $label ) : ?><span class="oeuvre-tag"><?php echo esc_html( strtoupper( $label ) ); ?></span><?php endif; ?>
                <h3><?php the_title(); ?></h3>
                <a href="<?php echo esc_url( mda_contact_link( $boutique_id_courante, 'Intéressé par : ' . get_the_title() ) ); ?>" target="_blank" rel="noopener" class="btn-contacter">Contacter le vendeur</a>
              </div>
            </article>
        <?php endwhile; wp_reset_postdata(); else : ?>
          <p>Aucune œuvre publiée pour cette boutique.</p>
        <?php endif; ?>
      </div>
    </section>

    <section class="fiche-avis">
      <h2>Avis des collectionneurs</h2>
      <div class="avis-summary">
        <div class="avis-average">
          <div class="avis-score"><?php echo esc_html( $stats['average'] ); ?></div>
          <div class="avis-stars">
            <?php $full = round( $stats['average'] ); for ( $s = 1; $s <= 5; $s++ ) echo $s <= $full ? '&#9733;' : '&#9734;'; ?>
          </div>
          <div class="avis-total">Basé sur <?php echo esc_html( $stats['total'] ); ?> avis</div>
        </div>
        <div class="avis-bars">
          <?php for ( $s = 5; $s >= 1; $s-- ) :
              $count = $stats['dist'][ $s ];
              $pct = $stats['total'] ? round( ( $count / $stats['total'] ) * 100 ) : 0;
              ?>
              <div class="avis-bar-row">
                <span><?php echo $s; ?></span>
                <div class="avis-bar"><div class="avis-bar-fill" style="width:<?php echo esc_attr( $pct ); ?>%"></div></div>
              </div>
          <?php endfor; ?>
        </div>
      </div>

      <?php
      $reviews = get_comments( array( 'post_id' => get_the_ID(), 'status' => 'approve', 'number' => 5 ) );
      if ( $reviews ) :
      ?>
      <div class="avis-list">
        <?php foreach ( $reviews as $r ) :
            $r_rating = (int) get_comment_meta( $r->comment_ID, 'rating', true );
            ?>
            <div class="avis-item">
              <div class="avis-avatar"><?php echo esc_html( mb_substr( $r->comment_author, 0, 1 ) ); ?></div>
              <div class="avis-content">
                <div class="avis-head">
                  <div>
                    <strong><?php echo esc_html( $r->comment_author ); ?></strong>
                    <span class="avis-date"><?php echo esc_html( date_i18n( 'd M Y', strtotime( $r->comment_date ) ) ); ?></span>
                  </div>
                  <div class="avis-item-stars">
                    <?php for ( $s = 1; $s <= 5; $s++ ) echo $s <= $r_rating ? '&#9733;' : '&#9734;'; ?>
                  </div>
                </div>
                <p><?php echo esc_html( $r->comment_content ); ?></p>
              </div>
            </div>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>

      <div class="avis-form">
        <?php
        $mda_comment_args = array(
            'title_reply'          => 'Laisser un avis',
            'label_submit'         => 'Publier mon avis',
            'comment_notes_before' => '<p class="avis-required-note">Les champs marqués d\'un <span class="required">*</span> sont obligatoires.</p>',
            'comment_field'        => '<p class="comment-form-comment"><label for="comment">Commentaire</label><textarea id="comment" name="comment" placeholder="Votre avis..." required></textarea></p>',
            'fields'                => array(
                'author' => '<p class="comment-form-author"><input id="author" name="author" type="text" placeholder="Votre nom *" required></p>',
                'email'  => '<p class="comment-form-email"><input id="email" name="email" type="email" placeholder="Votre email *" required></p>',
                'rating' => mda_rating_input_html(),
            ),
            // Un compte est requis pour laisser un avis ; la navigation du
            // site, elle, reste entièrement libre sans compte. Ce message
            // remplace automatiquement le formulaire quand le visiteur
            // n'est pas connecté (voir comment_registration forcé à true
            // dans functions.php).
            'must_log_in'          => sprintf(
                '<div class="avis-login-required">
                    <p>Vous devez avoir un compte et être connecté pour laisser un avis.</p>
                    <div class="avis-login-required-actions">
                        <a href="%1$s" class="btn-avis-outline">Se connecter</a>
                        <a href="%2$s" class="btn-avis-ghost">Créer un compte</a>
                    </div>
                </div>',
                esc_url( home_url( '/connexion/' ) ),
                esc_url( home_url( '/inscription/' ) )
            ),
        );

        if ( is_user_logged_in() ) {
            $mda_user = wp_get_current_user();
            $mda_comment_args['logged_in_as'] = sprintf(
                '<div class="avis-logged-in">
                    <span>Connecté en tant que <strong>%1$s</strong></span>
                    <div class="avis-logged-in-actions">
                        <a href="%2$s" class="btn-avis-outline">Modifier mon profil</a>
                        <a href="%3$s" class="btn-avis-ghost">Se déconnecter</a>
                    </div>
                </div>',
                esc_html( $mda_user->display_name ),
                esc_url( get_edit_user_link() ),
                esc_url( wp_logout_url( get_permalink() ) )
            );
        }

        comment_form( $mda_comment_args );
        ?>
      </div>
    </section>

<?php endwhile; ?>

<script>
(function(){
  var buttons = document.querySelectorAll('.filtre-btn');
  var cards = document.querySelectorAll('#oeuvres-grid .oeuvre-card');
  var tri = document.getElementById('oeuvres-tri');
  var grid = document.getElementById('oeuvres-grid');

  buttons.forEach(function(btn){
    btn.addEventListener('click', function(){
      buttons.forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      var f = btn.dataset.filtre;
      cards.forEach(function(card){
        var match = f === 'tous' || (' ' + card.dataset.materiau + ' ').indexOf(' ' + f + ' ') > -1;
        card.style.display = match ? '' : 'none';
      });
    });
  });

  if (tri) {
    tri.addEventListener('change', function(){
      var arr = Array.prototype.slice.call(cards);
      arr.sort(function(a, b){
        if (tri.value === 'recent') return b.dataset.date - a.dataset.date;
        if (tri.value === 'ancien') return a.dataset.date - b.dataset.date;
        return a.dataset.titre.localeCompare(b.dataset.titre);
      });
      arr.forEach(function(card){ grid.appendChild(card); });
    });
  }
})();
</script>

<?php get_footer(); ?>
