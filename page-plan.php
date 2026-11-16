<?php
/**
 * WordPress utilise ce gabarit automatiquement pour une Page dont le slug est "plan".
 * Créer une Page intitulée "Plan" dans l'admin suffit — aucun choix de modèle requis.
 */
get_header();

$mda_kiosques = mda_plan_kiosques_data();
$mda_cols = 225;
$mda_rows = 1;
$mda_total = $mda_cols * $mda_rows;
?>

<section class="plan-intro">
  <div class="breadcrumb breadcrumb-fiche">
    <a href="<?php echo home_url('/'); ?>">Accueil</a> &rsaquo; <span>Plan du marché</span>
  </div>
  <h1>Plan du Marché des Arts</h1>
  <p>Survolez un kiosque occupé pour découvrir la boutique.</p>
  <div class="plan-legend">
    <span><i class="dot dot-occupe"></i> Kiosque occupé</span>
    <span><i class="dot dot-libre"></i> Emplacement libre</span>
  </div>
  <div class="plan-view-toggle" role="tablist">
    <button type="button" class="plan-view-btn active" data-vue="schema">Plan schématique</button>
    <button type="button" class="plan-view-btn" data-vue="carte">Carte réelle</button>
  </div>
</section>

<section class="plan-wrap" id="plan-vue-schema">
  <div class="plan-viewport" id="plan-viewport">
    <div class="plan-grid" id="plan-grid">
      <?php for ( $n = 1; $n <= $mda_total; $n++ ) :
          $occupe = isset( $mda_kiosques[ $n ] );
          $aisle  = ( $n % 5 === 0 && $n % $mda_cols !== 0 ) ? ' aisle-right' : '';
          $label  = $occupe && ! empty( $mda_kiosques[ $n ]['emplacement'] ) ? $mda_kiosques[ $n ]['emplacement'] : 'Kiosque ' . $n;
          ?>
          <button
            type="button"
            class="kiosque<?php echo $occupe ? ' occupe' : ' libre'; ?><?php echo esc_attr( $aisle ); ?>"
            data-kiosque="<?php echo esc_attr( $n ); ?>"
            <?php if ( ! $occupe ) echo 'disabled aria-disabled="true"'; ?>
            aria-label="<?php echo esc_attr( $label ); ?><?php echo $occupe ? ' — ' . esc_attr( $mda_kiosques[ $n ]['titre'] ) : ' — libre'; ?>"
          ><?php echo esc_html( $n ); ?></button>
      <?php endfor; ?>
    </div>

    <div class="plan-controls">
      <button type="button" id="plan-zoom-in" aria-label="Zoomer">+</button>
      <button type="button" id="plan-zoom-out" aria-label="Dézoomer">&minus;</button>
      <button type="button" id="plan-zoom-reset" aria-label="Réinitialiser la vue">&#8634;</button>
    </div>
  </div>
</section>

<section class="plan-wrap" id="plan-vue-carte" style="display:none;">
  <div id="plan-map"></div>
  <p class="plan-map-note">.</p>
</section>

<div class="plan-popover" id="plan-popover">
  <div id="plan-popover-content"></div>
</div>

<script>
(function(){
  var kiosques = <?php echo wp_json_encode( $mda_kiosques ); ?>;
  var mdaGeo = { chemin: <?php echo wp_json_encode( mda_plan_chemin_gps() ); ?> };
  var mdaTotal = <?php echo (int) $mda_total; ?>;
  var popover = document.getElementById('plan-popover');
  var content = document.getElementById('plan-popover-content');
  var viewport = document.getElementById('plan-viewport');
  var grid = document.getElementById('plan-grid');
  var hideTimer = null;
  var current = null;

  /* ---------- Échappement HTML : les valeurs (titre, emplacement, type…) ----------
     proviennent de champs saisis par les artisans (rôle à faibles privilèges).
     Elles ne doivent jamais être insérées telles quelles dans le DOM, sous
     peine d'injection de script (XSS stockée) visible par tous les visiteurs
     de cette page, y compris non connectés. */
  function echapperHtml(valeur){
    var div = document.createElement('div');
    div.textContent = valeur === undefined || valeur === null ? '' : String(valeur);
    return div.innerHTML;
  }

  /* ---------- Contenu du popover (photo principale plus grande) ---------- */
  function remplir(numero){
    var k = kiosques[numero];
    if (!k) return;

    var titre = echapperHtml(k.titre);
    var type = echapperHtml(k.type);
    var lien = encodeURI(k.lien || '#');

    var mediaHtml = '';
    if (k.photo_principale) {
      mediaHtml += '<img class="photo-principale" src="' + echapperHtml(k.photo_principale) + '" alt="' + titre + '">';
    }
    if (k.photos && k.photos.length) {
      mediaHtml += '<div class="photos-secondaires">';
      k.photos.forEach(function(url){
        mediaHtml += '<img src="' + echapperHtml(url) + '" alt="' + titre + '">';
      });
      mediaHtml += '</div>';
    }
    if (!mediaHtml) {
      mediaHtml = '<p class="plan-no-photo">Aucune photo pour le moment.</p>';
    }

    var noteHtml = '';
    if (k.note) {
      var full = Math.round(parseFloat(k.note));
      var stars = '';
      for (var s = 1; s <= 5; s++) stars += s <= full ? '\u2605' : '\u2606';
      noteHtml = '<div class="plan-modal-rating">' + stars + ' <strong>' + echapperHtml(k.note) + '</strong></div>';
    }

    var identifiant = echapperHtml(k.emplacement ? k.emplacement : ('Kiosque ' + numero));

    content.innerHTML =
      '<span class="plan-modal-kiosk">' + identifiant + '</span>' +
      '<h3>' + titre + '</h3>' +
      (k.type ? '<p class="plan-modal-type">' + type + '</p>' : '') +
      noteHtml +
      '<div class="plan-modal-photos">' + mediaHtml + '</div>' +
      '<a href="' + lien + '" class="btn-plan-voir-plus">Voir plus</a>';
  }

  function positionner(btn){
    var rect = btn.getBoundingClientRect();
    var pw = 260;
    var left = rect.left + window.scrollX + rect.width / 2 - pw / 2;
    left = Math.max(10, Math.min(left, window.innerWidth - pw - 10));
    var placerAuDessus = rect.top > 340;
    popover.style.left = left + 'px';
    if (placerAuDessus) {
      popover.style.top = 'auto';
      popover.style.bottom = (window.innerHeight - rect.top - window.scrollY + 10) + 'px';
      popover.classList.remove('below');
    } else {
      popover.style.bottom = 'auto';
      popover.style.top = (rect.bottom + window.scrollY + 10) + 'px';
      popover.classList.add('below');
    }
  }

  function afficher(btn){
    clearTimeout(hideTimer);
    current = btn.dataset.kiosque;
    remplir(current);
    positionner(btn);
    popover.classList.add('active');
  }

  function planifierMasquage(){
    clearTimeout(hideTimer);
    hideTimer = setTimeout(function(){
      popover.classList.remove('active');
      current = null;
    }, 200);
  }

  var dragMoved = false;

  document.querySelectorAll('.kiosque.occupe').forEach(function(btn){
    btn.addEventListener('mouseenter', function(){ afficher(btn); });
    btn.addEventListener('mouseleave', planifierMasquage);
    btn.addEventListener('focus', function(){ afficher(btn); });
    btn.addEventListener('blur', planifierMasquage);
    btn.addEventListener('click', function(){
      if (dragMoved) return; // un glissé de la carte ne doit pas ouvrir le popover
      if (current === btn.dataset.kiosque && popover.classList.contains('active')) {
        popover.classList.remove('active');
        current = null;
      } else {
        afficher(btn);
      }
    });
  });

  popover.addEventListener('mouseenter', function(){ clearTimeout(hideTimer); });
  popover.addEventListener('mouseleave', planifierMasquage);

  document.addEventListener('click', function(e){
    if (!popover.contains(e.target) && !e.target.closest('.kiosque.occupe')) {
      popover.classList.remove('active');
      current = null;
    }
  });

  /* ---------- Zoom / déplacement façon Google Maps ---------- */
  var state = { scale: 1, x: 0, y: 0 };
  var MIN_SCALE = 0.1, MAX_SCALE = 3;

  // Amortisseur appliqué au glissé à la souris : un mouvement de X pixels ne
  // déplace le plan que de X * DRAG_DAMPING pixels, pour un contrôle plus
  // doux et moins "nerveux" qu'un suivi 1:1 du pointeur.
  var DRAG_DAMPING = 0.6;

  function appliquer(){
    grid.style.transform = 'translate(' + state.x + 'px,' + state.y + 'px) scale(' + state.scale + ')';
  }

  // Dimensions réelles du plan (non affectées par le transform CSS en cours).
  function tailleNaturelle(){
    return { w: grid.offsetWidth, h: grid.offsetHeight };
  }

  // Empêche le plan d'être poussé trop loin hors du cadre visible : s'il est
  // plus petit que la zone visible sur un axe, il est recentré sur cet axe ;
  // sinon son déplacement est borné avec une petite marge de confort.
  function borner(){
    var vpRect = viewport.getBoundingClientRect();
    var taille = tailleNaturelle();
    var largeurZoomee = taille.w * state.scale;
    var hauteurZoomee = taille.h * state.scale;
    var marge = 60;

    if ( largeurZoomee <= vpRect.width ) {
      state.x = ( vpRect.width - largeurZoomee ) / 2;
    } else {
      var minX = vpRect.width - largeurZoomee - marge;
      var maxX = marge;
      state.x = Math.min( maxX, Math.max( minX, state.x ) );
    }

    if ( hauteurZoomee <= vpRect.height ) {
      state.y = ( vpRect.height - hauteurZoomee ) / 2;
    } else {
      var minY = vpRect.height - hauteurZoomee - marge;
      var maxY = marge;
      state.y = Math.min( maxY, Math.max( minY, state.y ) );
    }
  }

  function ajusterVue(){
    var vpRect = viewport.getBoundingClientRect();
    var taille = tailleNaturelle();
    var marge = 80;

    // Le plan est une seule longue allée : on calibre le zoom minimum pour
    // que l'utilisateur puisse toujours la voir en entier d'un coup, et le
    // zoom de départ pour qu'elle tienne bien en hauteur dans le cadre.
    var fitLargeur = ( vpRect.width - marge ) / taille.w;
    var fitHauteur = ( vpRect.height - marge ) / taille.h;
    MIN_SCALE = Math.min( fitLargeur, fitHauteur, 1 );

    state.scale = Math.max( MIN_SCALE, Math.min( fitHauteur, 1 ) );
    state.x = ( vpRect.width - taille.w * state.scale ) / 2;
    state.y = ( vpRect.height - taille.h * state.scale ) / 2;
    borner();
    appliquer();
  }
  ajusterVue();
  window.addEventListener('resize', ajusterVue);

  function zoomVers(deltaScale, cx, cy){
    var rect = viewport.getBoundingClientRect();
    var px = cx - rect.left;
    var py = cy - rect.top;
    var newScale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, state.scale * deltaScale));
    var ratio = newScale / state.scale;
    state.x = px - (px - state.x) * ratio;
    state.y = py - (py - state.y) * ratio;
    state.scale = newScale;
    borner();
    appliquer();
  }

  // Molette / trackpad : un trackpad transmet un pincement comme un
  // événement "wheel" avec ctrlKey actif (convention des navigateurs), donc
  // seul ce cas zoome. Un glissé à deux doigts (sans ctrlKey) déplace le
  // plan, comme un défilement horizontal classique.
  viewport.addEventListener('wheel', function(e){
    e.preventDefault();
    if (e.ctrlKey) {
      var delta = e.deltaY < 0 ? 1.08 : 1 / 1.08;
      zoomVers(delta, e.clientX, e.clientY);
    } else {
      state.x -= e.deltaX;
      state.y -= e.deltaY;
      borner();
      appliquer();
    }
  }, { passive: false });

  document.getElementById('plan-zoom-in').addEventListener('click', function(){
    var r = viewport.getBoundingClientRect();
    zoomVers(1.25, r.left + r.width / 2, r.top + r.height / 2);
  });
  document.getElementById('plan-zoom-out').addEventListener('click', function(){
    var r = viewport.getBoundingClientRect();
    zoomVers(1 / 1.25, r.left + r.width / 2, r.top + r.height / 2);
  });
  document.getElementById('plan-zoom-reset').addEventListener('click', ajusterVue);

  // Glisser à la souris
  var dragging = false, startX = 0, startY = 0, startState = null;
  viewport.addEventListener('mousedown', function(e){
    if (e.target.closest('.plan-controls')) return;
    dragging = true;
    dragMoved = false;
    startX = e.clientX; startY = e.clientY;
    startState = { x: state.x, y: state.y };
    viewport.classList.add('dragging');
  });
  window.addEventListener('mousemove', function(e){
    if (!dragging) return;
    var dx = (e.clientX - startX) * DRAG_DAMPING;
    var dy = (e.clientY - startY) * DRAG_DAMPING;
    if (Math.abs(dx) > 4 || Math.abs(dy) > 4) dragMoved = true;
    state.x = startState.x + dx;
    state.y = startState.y + dy;
    borner();
    appliquer();
  });
  window.addEventListener('mouseup', function(){
    dragging = false;
    viewport.classList.remove('dragging');
    setTimeout(function(){ dragMoved = false; }, 50);
  });

  // Tactile (écrans tactiles réels) : glisser à deux doigts pour se
  // déplacer, pincer (mêmes deux doigts qui s'écartent/se rapprochent)
  // pour zoomer. Un seul doigt n'est pas intercepté : il laisse la page
  // défiler normalement.
  var touchStart = null;

  function milieuTactile(touches){
    return {
      x: (touches[0].clientX + touches[1].clientX) / 2,
      y: (touches[0].clientY + touches[1].clientY) / 2
    };
  }

  viewport.addEventListener('touchstart', function(e){
    if (e.touches.length === 2) {
      dragMoved = false;
      touchStart = {
        dist: distanceTactile(e.touches),
        scale: state.scale,
        milieu: milieuTactile(e.touches),
        state: { x: state.x, y: state.y }
      };
    } else {
      touchStart = null;
    }
  }, { passive: true });

  viewport.addEventListener('touchmove', function(e){
    if (e.touches.length !== 2 || !touchStart) return;
    e.preventDefault(); // on gère nous-mêmes le geste à 2 doigts

    var rect = viewport.getBoundingClientRect();
    var nouvelleDist = distanceTactile(e.touches);
    var nouveauMilieu = milieuTactile(e.touches);

    // Zoom au pincement. Une petite zone morte évite qu'un simple glissé
    // (où l'écart entre les doigts varie légèrement malgré tout) ne
    // déclenche un zoom parasite.
    var ratioBrut = nouvelleDist / touchStart.dist;
    if (Math.abs(ratioBrut - 1) < 0.03) ratioBrut = 1;
    var ratioAmorti = Math.sign(ratioBrut - 1) * Math.sqrt(Math.abs(ratioBrut - 1)) + 1;
    var newScale = Math.min(MAX_SCALE, Math.max(MIN_SCALE, touchStart.scale * ratioAmorti));

    // Point du plan qui se trouvait sous le milieu initial des 2 doigts.
    var localX = ((touchStart.milieu.x - rect.left) - touchStart.state.x) / touchStart.scale;
    var localY = ((touchStart.milieu.y - rect.top) - touchStart.state.y) / touchStart.scale;

    // On replace ce même point sous la nouvelle position des doigts : en
    // glissant les 2 doigts vers la droite, le plan suit et affiche les
    // kiosques qui étaient à gauche (et inversement), tout en zoomant si
    // les doigts s'écartent ou se rapprochent.
    state.x = (nouveauMilieu.x - rect.left) - newScale * localX;
    state.y = (nouveauMilieu.y - rect.top) - newScale * localY;
    state.scale = newScale;

    var dx = nouveauMilieu.x - touchStart.milieu.x;
    var dy = nouveauMilieu.y - touchStart.milieu.y;
    if (Math.abs(dx) > 4 || Math.abs(dy) > 4) dragMoved = true;

    borner();
    appliquer();
  }, { passive: false });

  viewport.addEventListener('touchend', function(e){
    if (e.touches.length < 2) touchStart = null;
    setTimeout(function(){ dragMoved = false; }, 50);
  });

  function distanceTactile(touches){
    var dx = touches[0].clientX - touches[1].clientX;
    var dy = touches[0].clientY - touches[1].clientY;
    return Math.sqrt(dx * dx + dy * dy);
  }

  /* ---------- Carte réelle géoréférencée (Leaflet + OpenStreetMap) ---------- */
  var carteLeaflet = null;
  var schemaEl = document.getElementById('plan-vue-schema');
  var carteEl = document.getElementById('plan-vue-carte');

  // Distance approximative entre 2 points [lat,lng] : une approximation
  // plane suffit largement à l'échelle d'une allée de quelques centaines
  // de mètres (pas besoin de calcul de distance sur une sphère ici).
  function distancePoints(a, b){
    var dx = b[1] - a[1], dy = b[0] - a[0];
    return Math.sqrt(dx * dx + dy * dy);
  }

  // Renvoie le point situé à la fraction "ratio" (0 à 1) de la distance
  // totale parcourue le long d'un tracé à plusieurs points [lat,lng] —
  // contrairement à une interpolation entre 2 points seulement, ceci suit
  // fidèlement chaque coude du chemin fourni.
  function pointSurChemin(chemin, ratio){
    if (chemin.length === 1) return chemin[0];

    var segments = [];
    var longueurTotale = 0;
    for (var i = 0; i < chemin.length - 1; i++) {
      var l = distancePoints(chemin[i], chemin[i + 1]);
      segments.push(l);
      longueurTotale += l;
    }

    var cible = ratio * longueurTotale;
    var cumul = 0;
    for (var i = 0; i < segments.length; i++) {
      var estDernier = i === segments.length - 1;
      if (cumul + segments[i] >= cible || estDernier) {
        var t = segments[i] > 0 ? (cible - cumul) / segments[i] : 0;
        t = Math.max(0, Math.min(1, t));
        var a = chemin[i], b = chemin[i + 1];
        return [ a[0] + (b[0] - a[0]) * t, a[1] + (b[1] - a[1]) * t ];
      }
      cumul += segments[i];
    }
    return chemin[chemin.length - 1];
  }

  function initCarte(){
    if (carteLeaflet || typeof L === 'undefined') return;

    carteLeaflet = L.map('plan-map', { scrollWheelZoom: true, maxZoom: 22 });
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      maxZoom: 25,       // zoom maximum autorisé sur la carte
      maxNativeZoom: 19, // résolution réelle max des tuiles OSM : au-delà,
                         // Leaflet agrandit numériquement les tuiles (moins
                         // nettes, mais permet de continuer à zoomer)
      attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">contributeurs OpenStreetMap</a>'
    }).addTo(carteLeaflet);

    var points = [];
    for (var n = 1; n <= mdaTotal; n++) {
      var t = mdaTotal > 1 ? (n - 1) / (mdaTotal - 1) : 0;
      var pos = pointSurChemin(mdaGeo.chemin, t);
      var lat = pos[0], lng = pos[1];
      points.push([lat, lng]);

      var k = kiosques[n];
      var estOccupe = !!k;
      var marqueur = L.circleMarker([lat, lng], {
        radius: estOccupe ? 6 : 3,
        weight: 1,
        color: '#fff',
        fillColor: estOccupe ? '#D4681A' : '#6A7282',
        fillOpacity: 1
      }).addTo(carteLeaflet);

      if (estOccupe) {
        (function(numero, marqueurCourant){
          var geoHideTimer = null;

          function ouvrirPopup(){
            clearTimeout(geoHideTimer);
            remplir(numero); // réutilise le même gabarit HTML que le plan schématique
            marqueurCourant
              .bindPopup(content.innerHTML, { maxWidth: 260, closeButton: false, autoClose: false, closeOnClick: false })
              .openPopup();

            // Le popup Leaflet est recréé dans le DOM à chaque ouverture : on
            // rattache le maintien au survol une fois qu'il existe vraiment,
            // pour permettre à l'utilisateur de bouger la souris jusqu'au
            // bouton "Voir plus" sans que le popup ne se referme sous lui.
            var popupEl = marqueurCourant.getPopup() ? marqueurCourant.getPopup().getElement() : null;
            if (popupEl) {
              popupEl.addEventListener('mouseenter', function(){ clearTimeout(geoHideTimer); });
              popupEl.addEventListener('mouseleave', planifierFermeture);
            }
          }

          function planifierFermeture(){
            clearTimeout(geoHideTimer);
            geoHideTimer = setTimeout(function(){
              marqueurCourant.closePopup();
            }, 250);
          }

          marqueurCourant.on('mouseover', ouvrirPopup);
          marqueurCourant.on('mouseout', planifierFermeture);
          // Conserve le tap tactile (pas de survol réel sur mobile).
          marqueurCourant.on('click', ouvrirPopup);
        })(n, marqueur);
      }
    }

    var ligne = L.polyline(points, { color: '#D4681A', weight: 2, opacity: 0.3 }).addTo(carteLeaflet);
    carteLeaflet.fitBounds(ligne.getBounds(), { padding: [40, 40] });
  }

  document.querySelectorAll('.plan-view-btn').forEach(function(btn){
    btn.addEventListener('click', function(){
      document.querySelectorAll('.plan-view-btn').forEach(function(b){ b.classList.remove('active'); });
      btn.classList.add('active');
      var vue = btn.dataset.vue;
      schemaEl.style.display = vue === 'schema' ? '' : 'none';
      carteEl.style.display = vue === 'carte' ? '' : 'none';
      if (vue === 'carte') {
        initCarte();
        // Leaflet a besoin d'être notifié après un changement de taille de
        // son conteneur (ici : passage de display:none à visible).
        setTimeout(function(){ if (carteLeaflet) carteLeaflet.invalidateSize(); }, 50);
      }
    });
  });
})();
</script>

<?php get_footer(); ?>
