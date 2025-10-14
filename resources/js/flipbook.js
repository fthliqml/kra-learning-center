import * as pdfjsLib from 'pdfjs-dist/build/pdf';
import pdfWorkerUrl from 'pdfjs-dist/build/pdf.worker.min.js?url';
import { PageFlip } from 'page-flip/dist/js/page-flip.module.js';

(function(){
  if (window.__flipbookReady) return;
  window.__flipbookReady = true;

  try { pdfjsLib.GlobalWorkerOptions.workerSrc = pdfWorkerUrl; } catch(e) {}

  function initRoot(root) {
    if (!root || root.__flipInit) return;
    root.__flipInit = true;
    var url = root.getAttribute('data-pdf-url');
    var container = root.querySelector('.flip-container');
    // Ensure the flip container uses flex layout
    if (container) {
      container.style.display = 'flex';
      container.style.justifyContent = 'center';
      // Responsive vertical alignment: center on desktop, top on mobile to reduce empty space
      function adjustAlign(){
        var small = (window.innerWidth || 0) <= 640 || ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
        container.style.alignItems = small ? 'flex-start' : 'center';
      }
      try {
        if (root.__alignHandler) window.removeEventListener('resize', root.__alignHandler);
        root.__alignHandler = adjustAlign;
        window.addEventListener('resize', adjustAlign, { passive: true });
      } catch(_) {}
      adjustAlign();
    }
    if (!url || !container) return;
    var state = { pdf: null, total: 0 };

    function renderAllPagesToImages(pdf, maxPages){
      var tasks = [];
      var h = container.clientHeight || root.clientHeight || 600;
      for (let p = 1; p <= pdf.numPages && p <= (maxPages || 200); p++){
        tasks.push(pdf.getPage(p).then(function(page){
          var viewport = page.getViewport({ scale: 1 });
          var scale = h / viewport.height; // fit height
          var vp = page.getViewport({ scale: scale });
          var canvas = document.createElement('canvas');
          var dpr = window.devicePixelRatio || 1;
          canvas.width = Math.floor(vp.width * dpr);
          canvas.height = Math.floor(vp.height * dpr);
          canvas.style.width = vp.width + 'px';
          canvas.style.height = vp.height + 'px';
          var ctx = canvas.getContext('2d');
          var renderContext = { canvasContext: ctx, viewport: vp, transform: [dpr,0,0,dpr,0,0] };
          return page.render(renderContext).promise.then(function(){
            return { url: canvas.toDataURL('image/jpeg', 0.92), width: vp.width, height: vp.height };
          });
        }));
      }
      return Promise.all(tasks);
    }

    // Load PDF using locally bundled pdfjs
    try {
      var firstTry = pdfjsLib.getDocument(url).promise;
      firstTry.then(function(pdf) {
        state.pdf = pdf; state.total = pdf.numPages;
        root.querySelector('.pdf-loading')?.remove();
        renderAllPagesToImages(pdf).then(function(images){
          if (!images || !images.length) { showError('Tidak ada halaman'); return; }
          var pages = images.map(function(img){
            var d = document.createElement('div'); d.className = 'page';
            var im = document.createElement('img'); im.src = img.url; im.alt = 'Halaman';
            im.style.width = img.width + 'px'; im.style.height = img.height + 'px';
            im.oncontextmenu = function(e){ e.preventDefault(); return false; };
            im.ondragstart = function(e){ e.preventDefault(); return false; };
            d.appendChild(im); return d;
          });
          container.innerHTML = '';
          var w = images[0].width, h = images[0].height;
          var book = document.createElement('div');
          book.className = 'flip-wrapper flex items-center justify-center';
          book.style.width = Math.round(w) + 'px';
          book.style.height = Math.round(h) + 'px';
          container.appendChild(book);
          var pageFlip = new PageFlip(book, {
            width: Math.round(w),
            height: Math.round(h),
            size: 'fixed',           // fixed size to force single page
            usePortrait: true,       // always allow portrait mode
            autoSize: false,         // do not stretch to container
            maxShadowOpacity: 0.2,
            showCover: false,
            mobileScrollSupport: true,
          });
          // Fit to container (scale)
          try {
            if (root.__fitHandler) { window.removeEventListener('resize', root.__fitHandler); }
            function fitBook(){
              var cw = container.clientWidth || root.clientWidth || w;
              var ch = container.clientHeight || root.clientHeight || h;
              var sf = Math.min(cw / w, ch / h, 1);
              book.style.transformOrigin = 'center center';
              book.style.transform = 'scale(' + sf + ')';
              root.__fitScale = sf;
              if (typeof root.__positionNav === 'function') {
                try { root.__positionNav(); } catch(_){}
              }
            }
            root.__fitHandler = fitBook;
            window.addEventListener('resize', fitBook, { passive: true });
            fitBook();
          } catch(_) {}
          pageFlip.loadFromHTML(pages);
          // Setup UI: current/total indicator and left/right navigation overlays
          setupNavigationUI(root, pageFlip, state.total);
        }).catch(function(e){ showError('Gagal merender halaman'); console.error('[Flipbook] Render error:', e); });
  // keep waiting without timeout limit
      }).catch(function(err) {
        console.warn('[Flipbook] PDF load failed (first try), retrying w/o worker', err);
        try {
          pdfjsLib.getDocument({ url: url, disableWorker: true }).promise.then(function(pdf){
            state.pdf = pdf; state.total = pdf.numPages;
            root.querySelector('.pdf-loading')?.remove();
            renderAllPagesToImages(pdf).then(function(images){
              if (!images || !images.length) { showError('Tidak ada halaman'); return; }
              var pages = images.map(function(img){
                var d = document.createElement('div'); d.className = 'page';
                var im = document.createElement('img'); im.src = img.url; im.alt = 'Halaman';
                im.oncontextmenu = function(e){ e.preventDefault(); return false; };
                im.ondragstart = function(e){ e.preventDefault(); return false; };
                d.appendChild(im); return d;
              });
              container.innerHTML = '';
              var w = images[0].width, h = images[0].height;
              var book = document.createElement('div');
              book.className = 'flip-wrapper flex items-center justify-center';
              book.style.width = Math.round(w) + 'px';
              book.style.height = Math.round(h) + 'px';
              container.appendChild(book);
              var pageFlip = new PageFlip(book, {
                width: Math.round(w),
                height: Math.round(h),
                size: 'fixed',
                usePortrait: true,
                autoSize: false,
                maxShadowOpacity: 0.2,
                showCover: false,
                mobileScrollSupport: true,
              });
              // Fit to container (scale) for retry path
              try {
                if (root.__fitHandler) { window.removeEventListener('resize', root.__fitHandler); }
                function fitBook2(){
                  var cw = container.clientWidth || root.clientWidth || w;
                  var ch = container.clientHeight || root.clientHeight || h;
                  var sf = Math.min(cw / w, ch / h, 1);
                  book.style.transformOrigin = 'center center';
                  book.style.transform = 'scale(' + sf + ')';
                  root.__fitScale = sf;
                  if (typeof root.__positionNav === 'function') {
                    try { root.__positionNav(); } catch(_){}
                  }
                }
                root.__fitHandler = fitBook2;
                window.addEventListener('resize', fitBook2, { passive: true });
                fitBook2();
              } catch(_) {}
              pageFlip.loadFromHTML(pages);
              setupNavigationUI(root, pageFlip, state.total);
            }).catch(function(e){ showError('Gagal merender halaman'); console.error('[Flipbook] Render error:', e); });
            // keep waiting without timeout limit
          }).catch(function(e2){
            console.error('[Flipbook] PDF load failed (retry)', e2);
            showError('Gagal memuat PDF (file tidak dapat diakses atau rusak)');
          });
        } catch(e){
          showError('Gagal memuat PDF (exception)');
        }
      });
    } catch(e) {
      console.error('[Flipbook] Unexpected error', e);
      showError('Gagal memuat viewer PDF (exception)');
    }

    function showError(text){
      root.querySelector('.pdf-loading')?.remove();
      var msg = document.createElement('div');
      msg.className = 'absolute inset-0 flex flex-col items-center justify-center gap-2 text-sm text-red-600 bg-white/80';
      msg.textContent = text;
      root.appendChild(msg);
  var retry = document.createElement('button');
      retry.type = 'button';
      retry.className = 'px-3 py-1.5 rounded border text-sm bg-white hover:bg-gray-50';
      retry.textContent = 'Muat ulang viewer';
      retry.addEventListener('click', function(){
        root.innerHTML = '<div class="flip-container w-full h-full"></div>';
        root.__flipInit = false;
        initRoot(root);
      });
      root.appendChild(retry);
    }
  }

  window.__initFlipbook = function(scopeEl) {
    var roots = (scopeEl instanceof Element ? scopeEl : document).querySelectorAll('.flipbook-root');
    roots.forEach(initRoot);
  };

  // Helper: current/total display and prev/next clickable zones
  function setupNavigationUI(root, pageFlip, totalPages) {
    // Info badge (current/total)
    var info = root.querySelector('.flip-info');
    if (!info) {
      info = document.createElement('div');
      info.className = 'flip-info';
      Object.assign(info.style, {
        position: 'absolute', bottom: '8px', left: '50%', transform: 'translateX(-50%)', zIndex: '30',
        background: 'rgba(0,0,0,0.65)', color: '#fff', padding: '4px 8px',
        borderRadius: '6px', fontSize: '12px', pointerEvents: 'none',
      });
      root.appendChild(info);
    }

    function setInfo(cur){ info.textContent = cur + ' / ' + totalPages; }
    // Try initial page index (PageFlip reports current page via events after init)
    try { setInfo((pageFlip.getCurrentPageIndex?.() ?? 0) + 1); } catch(_) { setInfo(1); }

    // Left/right clickable overlays
    var left = root.querySelector('.flip-hit-left');
    var right = root.querySelector('.flip-hit-right');
    if (!left) {
      left = document.createElement('div');
      left.className = 'flip-hit-left';
      Object.assign(left.style, {
        position: 'absolute', left: '0', top: '0', bottom: '0', width: '40%',
        zIndex: '25', cursor: 'w-resize',
      });
      root.appendChild(left);
    }
    if (!right) {
      right = document.createElement('div');
      right.className = 'flip-hit-right';
      Object.assign(right.style, {
        position: 'absolute', right: '0', top: '0', bottom: '0', width: '40%',
        zIndex: '25', cursor: 'e-resize',
      });
      root.appendChild(right);
    }

    // Hover hints
    function makeHint(dir){
      var h = document.createElement('div');
      Object.assign(h.style, {
        position: 'absolute', bottom: '10px', padding: '4px 8px',
        background: 'rgba(0,0,0,0.5)', color: '#fff', borderRadius: '6px',
        fontSize: '12px', pointerEvents: 'none', opacity: '0', transition: 'opacity .15s',
      });
      h.textContent = dir === 'left' ? 'Sebelumnya' : 'Berikutnya';
      if (dir === 'left') { h.style.left = '12px'; }
      else { h.style.right = '12px'; }
      return h;
    }
    var hintL = root.querySelector('.flip-hint-left');
    if (!hintL){ hintL = makeHint('left'); hintL.className = 'flip-hint-left'; root.appendChild(hintL); }
    var hintR = root.querySelector('.flip-hint-right');
    if (!hintR){ hintR = makeHint('right'); hintR.className = 'flip-hint-right'; root.appendChild(hintR); }

  // Always-visible arrow buttons (all devices)
    var btnL = root.querySelector('.flip-btn-left');
    if (!btnL){
      btnL = document.createElement('button');
      btnL.type = 'button';
      btnL.className = 'flip-btn-left';
      btnL.setAttribute('aria-label', 'Halaman sebelumnya');
      Object.assign(btnL.style, {
        position: 'absolute', left: '8px', bottom: '12px',
        width: '40px', height: '40px', borderRadius: '9999px',
        background: 'rgba(0,0,0,0.6)', color: '#fff', border: 'none',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        zIndex: '35', cursor: 'pointer'
      });
      btnL.textContent = '‹';
      root.appendChild(btnL);
    }
    var btnR = root.querySelector('.flip-btn-right');
    if (!btnR){
      btnR = document.createElement('button');
      btnR.type = 'button';
      btnR.className = 'flip-btn-right';
      btnR.setAttribute('aria-label', 'Halaman berikutnya');
      Object.assign(btnR.style, {
        position: 'absolute', right: '8px', bottom: '12px',
        width: '40px', height: '40px', borderRadius: '9999px',
        background: 'rgba(0,0,0,0.6)', color: '#fff', border: 'none',
        display: 'flex', alignItems: 'center', justifyContent: 'center',
        zIndex: '35', cursor: 'pointer'
      });
      btnR.textContent = '›';
      root.appendChild(btnR);
    }

    // Always show on touch/mobile; hover on desktop
    var isTouch = 'ontouchstart' in window || navigator.maxTouchPoints > 0;
    // Hide hover text hints entirely (arrow-only navigation)
    hintL.style.display = 'none';
    hintR.style.display = 'none';

    // Show arrow buttons on all devices
    btnL.style.display = 'flex';
    btnR.style.display = 'flex';

    // Click navigation
    function updateEdges(){
      try {
        var idx = (pageFlip.getCurrentPageIndex?.() ?? 0);
        var last = totalPages - 1;
        var atFirst = idx <= 0;
        var atLast = idx >= last;
        left.style.pointerEvents = atFirst ? 'none' : 'auto';
        right.style.pointerEvents = atLast ? 'none' : 'auto';
        // Buttons state
        btnL.style.opacity = atFirst ? '0.5' : '1';
        btnR.style.opacity = atLast ? '0.5' : '1';
        btnL.style.pointerEvents = atFirst ? 'none' : 'auto';
        btnR.style.pointerEvents = atLast ? 'none' : 'auto';
      } catch(_){}
    }
    left.addEventListener('click', function(){ try { pageFlip.flipPrev?.(); } catch(_){}; updateEdges(); });
    right.addEventListener('click', function(){ try { pageFlip.flipNext?.(); } catch(_){}; updateEdges(); });
    btnL.addEventListener('click', function(e){ e.stopPropagation(); try { pageFlip.flipPrev?.(); } catch(_){}; updateEdges(); });
    btnR.addEventListener('click', function(e){ e.stopPropagation(); try { pageFlip.flipNext?.(); } catch(_){}; updateEdges(); });

    // Sync current page on flip events
    try {
      pageFlip.on?.('flip', function(e){
        var idx = (e?.data ?? e) | 0; // current page index
        setInfo((idx + 1));
        updateEdges();
      });
      pageFlip.on?.('init', function(){ setInfo((pageFlip.getCurrentPageIndex?.() ?? 0) + 1); updateEdges(); });
      pageFlip.on?.('update', function(){ setInfo((pageFlip.getCurrentPageIndex?.() ?? 0) + 1); updateEdges(); });
    } catch(_) {}

    // Reposition buttons to bottom corners using side and bottom margins (avoid covering the PDF when possible)
    try {
      var container = root.querySelector('.flip-container');
      var bookEl = root.querySelector('.flip-wrapper');
      function positionNav(){
        if (!container || !bookEl) return;
        var cw = container.clientWidth;
        var ch = container.clientHeight;
        var bw = parseInt(bookEl.style.width || '0') || bookEl.getBoundingClientRect().width;
        var bh = parseInt(bookEl.style.height || '0') || bookEl.getBoundingClientRect().height;
        var sf = root.__fitScale || Math.min(cw / bw, ch / bh, 1);
        var displayedW = bw * sf;
        var displayedH = bh * sf;
        var marginX = Math.max(0, (cw - displayedW) / 2);
        var marginY = Math.max(0, (ch - displayedH) / 2);
        var btnSize = cw < 360 ? 36 : 44;
        btnL.style.width = btnSize + 'px'; btnL.style.height = btnSize + 'px';
        btnR.style.width = btnSize + 'px'; btnR.style.height = btnSize + 'px';
        var sideOffset = marginX >= (btnSize + 8) ? (marginX - btnSize) / 2 : 8;
        var bottomOffset = marginY >= (btnSize + 8) ? (marginY - btnSize) / 2 : 8;
        btnL.style.left = sideOffset + 'px';
        btnR.style.right = sideOffset + 'px';
        // Ensure bottom placement and remove previous vertical centering styles
        btnL.style.top = '';
        btnR.style.top = '';
        btnL.style.transform = '';
        btnR.style.transform = '';
        btnL.style.bottom = bottomOffset + 'px';
        btnR.style.bottom = bottomOffset + 'px';

        // Keep indicator above safe bottom area
        info.style.bottom = (bottomOffset) + 'px';

        // Keep large left/right hit areas above the nav controls so taps don't conflict
        try {
          var navReserve = bottomOffset + btnSize + 8; // area reserved for bottom nav
          left.style.bottom = navReserve + 'px';
          right.style.bottom = navReserve + 'px';
        } catch(_) {}
      }
      root.__positionNav = positionNav;
      window.addEventListener('resize', positionNav, { passive: true });
      positionNav();
    } catch(_) {}
  }

  document.addEventListener('DOMContentLoaded', function() {
    window.__initFlipbook(document);
  }, { once: true });

  // Livewire SPA support (if Livewire is present)
  document.addEventListener('livewire:navigated', function() {
    window.__initFlipbook(document);
  });
})();
