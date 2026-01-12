(function(){
  if (window.__modulePageReady) return; window.__modulePageReady = true;

  // Expose Alpine x-data helpers
  window.videoGate = function(requiredIds, storageKey) {
    const ids = Array.isArray(requiredIds) ? requiredIds.filter(Boolean) : [];
    const key = String(storageKey || 'videoGate');

    return {
      requiredIds: ids,
      storageKey: key,
      ended: {},

      init() {
        // Load persisted completion state so refresh doesn't reset the gate.
        try {
          const raw = localStorage.getItem(this.storageKey);
          if (!raw) return;
          const parsed = JSON.parse(raw);
          if (parsed && typeof parsed === 'object' && parsed.ended && typeof parsed.ended === 'object') {
            this.ended = parsed.ended;
          }
        } catch (e) {}
      },

      persist() {
        try {
          localStorage.setItem(
            this.storageKey,
            JSON.stringify({ ended: this.ended, updatedAt: Date.now() })
          );
        } catch (e) {}
      },

      markEnded(id) {
        if (!id) return;
        this.ended[id] = true;
        this.persist();
      },

      get done() {
        if (!this.requiredIds || this.requiredIds.length === 0) return true;
        for (let i = 0; i < this.requiredIds.length; i++) {
          const id = this.requiredIds[i];
          if (!this.ended || !this.ended[id]) return false;
        }
        return true;
      }
    };
  };

  window.readingAccordionState = function(key) {
    return {
      open: false,
      key: key,
      isMobile: false,
      init() {
        let raw = null;
        try { raw = localStorage.getItem(this.key); } catch (e) {}
        if (raw !== null) {
          try { this.open = JSON.parse(raw); } catch (e) { this.open = !!raw; }
        }
        this.$watch('open', (v) => {
          try { localStorage.setItem(this.key, JSON.stringify(v)); } catch (e) {}
        });
        const setM = () => { this.isMobile = window.matchMedia('(max-width: 767px)').matches; };
        setM();
        window.addEventListener('resize', setM, { passive: true });
      }
    };
  };

  // YouTube API + video end gating
  if (!window.__moduleVideoGateReady){
    window.__moduleVideoGateReady = true;
    window.__ytPlayers = window.__ytPlayers || {};
    window.__ytPreferredQ = window.__ytPreferredQ || {};

    window.__initYtPlayers = function() {
      if (!(window.YT && YT.Player)) return;
      document.querySelectorAll('iframe.yt-player').forEach(function(el) {
        if (el.dataset.playerBound === '1') return;
        el.dataset.playerBound = '1';
        var id = el.id;
        var endId = el.dataset.endId || id;
        try {
          var player = new YT.Player(id, {
            playerVars: {
              controls: 0, // hide timeline/controls
              modestbranding: 1,
              rel: 0,
              iv_load_policy: 3,
              playsinline: 1
            },
            events: {
              'onReady': function(){
                try {
                  var pref = window.__ytPreferredQ[id];
                  if (pref) { tryForceQuality(player, pref); }
                } catch(_){ }
              },
              'onStateChange': function(e) {
                if (e && e.data === YT.PlayerState.ENDED) {
                  window.dispatchEvent(new CustomEvent('module-video-ended', { detail: { id: endId } }));
                }
                try {
                  var overlayBtn = document.querySelector('[data-yt-overlay="'+id+'"]');
                  var overlayWrap = document.querySelector('[data-yt-overlay-container="'+id+'"]');
                  var show = (e && (e.data === YT.PlayerState.PAUSED || e.data === YT.PlayerState.CUED || e.data === YT.PlayerState.ENDED));
                  if (e && e.data === YT.PlayerState.PLAYING) show = false;
                  if (overlayBtn) overlayBtn.style.display = show ? '' : 'none';
                  if (overlayWrap) overlayWrap.style.display = show ? '' : 'none';
                } catch(_){}
                // Try apply preferred quality on PLAYING/BUFFERING
                try {
                  var pref = window.__ytPreferredQ[id];
                  if (pref && (e && (e.data === YT.PlayerState.PLAYING || e.data === YT.PlayerState.BUFFERING))) {
                    tryForceQuality(player, pref);
                  }
                } catch(_){ }
              }
            }
          });
          window.__ytPlayers[id] = player;

          // Bind custom controls if present
          try {
            var wrapper = el.parentElement;
            var controls = wrapper && wrapper.querySelector('[data-yt-for="' + id + '"]');
            if (controls) {
              bindCustomControls(player, controls, el);
            }
            var overlayBtn = document.querySelector('[data-yt-overlay="'+id+'"]');
            var overlayWrap = document.querySelector('[data-yt-overlay-container="'+id+'"]');
            if (overlayBtn) {
              overlayBtn.addEventListener('click', function(ev){ ev.stopPropagation(); try { player.playVideo(); } catch(e){} });
            }
            if (overlayWrap) {
              overlayWrap.addEventListener('click', function(ev){ ev.stopPropagation(); try { player.playVideo(); } catch(e){} });
            }
          } catch (e) {}
        } catch (err) {}
      });
    };

    function loadYtApi() {
      if (window.YT && YT.Player) { window.__initYtPlayers(); return; }
      if (window.__ytApiLoading) return;
      window.__ytApiLoading = true;
      var tag = document.createElement('script');
      tag.src = 'https://www.youtube.com/iframe_api';
      var first = document.getElementsByTagName('script')[0];
      if (first && first.parentNode) { first.parentNode.insertBefore(tag, first); }
      else { document.head.appendChild(tag); }
    }

    window.onYouTubeIframeAPIReady = function() {
      window.__ytApiReady = true;
      window.__initYtPlayers();
    };

    document.addEventListener('DOMContentLoaded', loadYtApi, { once: true });
    window.addEventListener('load', loadYtApi, { once: true });
    document.addEventListener('livewire:load', loadYtApi);
    document.addEventListener('livewire:navigated', function() {
      if (window.__ytApiReady) { window.__initYtPlayers(); }
      else { loadYtApi(); }
    });

    if ('MutationObserver' in window) {
      var mo = new MutationObserver(function(muts) {
        if (!(window.YT && YT.Player)) return;
        for (var i = 0; i < muts.length; i++) {
          var m = muts[i]; if (!m.addedNodes) continue;
          for (var j = 0; j < m.addedNodes.length; j++) {
            var n = m.addedNodes[j]; if (n.nodeType !== 1) continue;
            if ((n.matches && n.matches('iframe.yt-player')) || (n.querySelector && n.querySelector('iframe.yt-player'))) {
              window.__initYtPlayers(); return;
            }
          }
        }
      });
      mo.observe(document.body, { childList: true, subtree: true });
    }
  }

  // Helper: format time mm:ss or hh:mm:ss
  function fmtTime(sec) {
    sec = Math.max(0, Math.floor(sec || 0));
    var h = Math.floor(sec / 3600);
    var m = Math.floor((sec % 3600) / 60);
    var s = sec % 60;
    var mm = (h ? String(m).padStart(2, '0') : String(m));
    var ss = String(s).padStart(2, '0');
    return (h ? (h + ':' + mm + ':' + ss) : (m + ':' + ss));
  }

  // Bind custom controls to a YT player
  function bindCustomControls(player, controls, iframe) {
    var playerId = iframe && iframe.id;
    var btnPlay = controls.querySelector('[data-yt-action="togglePlay"]');
    var btnMute = controls.querySelector('[data-yt-action="toggleMute"]');
    var rngVol = controls.querySelector('[data-yt-el="volume"]');
    var elCur = controls.querySelector('[data-yt-el="currentTime"]');
    var elDur = controls.querySelector('[data-yt-el="duration"]');
    var btnCC = controls.querySelector('[data-yt-action="toggleCaptions"]');
    var selQ = controls.querySelector('[data-yt-el="quality"]');
  var btnFs = controls.querySelector('[data-yt-action="fullscreen"]');
  var btnQ = controls.querySelector('[data-yt-action="qualityMenu"]');
  var menuQ = controls.querySelector('[data-yt-quality-menu]');

    // Sync duration when available
    var duration = 0, tickId = 0;
    function syncDuration() {
      try { duration = player.getDuration() || 0; } catch (e) { duration = 0; }
      if (elDur) elDur.textContent = fmtTime(duration);
    }

    // Tick to update current time
    function tick() {
      var t = 0;
      try { t = player.getCurrentTime() || 0; } catch (e) { t = 0; }
      if (elCur) elCur.textContent = fmtTime(t);
      tickId = requestAnimationFrame(tick);
    }

    function startTick(){ if (!tickId) tick(); }
    function stopTick(){ if (tickId) { cancelAnimationFrame(tickId); tickId = 0; } }

    // UI icon helpers
    function setPlayIcon(isPlaying){
      if (!btnPlay) return;
      var iPlay = btnPlay.querySelector('[data-yt-icon-variant="play"]');
      var iPause = btnPlay.querySelector('[data-yt-icon-variant="pause"]');
      if (iPlay && iPause) {
        var hidePlay = !!isPlaying, hidePause = !isPlaying;
        iPlay.classList.toggle('hidden', hidePlay);
        iPause.classList.toggle('hidden', hidePause);
        iPlay.style.display = hidePlay ? 'none' : '';
        iPause.style.display = hidePause ? 'none' : '';
      }
    }
    function setMuteIcon(isMuted, vol){
      if (!btnMute) return;
      var v = (typeof vol === 'number') ? vol : null;
      var iMuted = btnMute.querySelector('[data-yt-icon-variant="muted"]');
      var iHigh = btnMute.querySelector('[data-yt-icon-variant="vol-high"]');
      var iMid = btnMute.querySelector('[data-yt-icon-variant="vol-mid"]');
      var iLow = btnMute.querySelector('[data-yt-icon-variant="vol-low"]');
      // Decide visible icon
      var show = { muted: false, high: false, mid: false, low: false };
      if (isMuted) {
        show.muted = true;
      } else {
        var vv = (v == null ? 100 : v);
        if (vv >= 70) show.high = true;
        else if (vv >= 30) show.mid = true;
        else show.low = true; // show low icon for 0..29 even if not muted
      }
  if (iMuted) { iMuted.classList.toggle('hidden', !show.muted); iMuted.style.display = show.muted ? '' : 'none'; }
  if (iHigh) { iHigh.classList.toggle('hidden', !show.high); iHigh.style.display = show.high ? '' : 'none'; }
  if (iMid)  { iMid.classList.toggle('hidden', !show.mid);  iMid.style.display = show.mid  ? '' : 'none'; }
  if (iLow)  { iLow.classList.toggle('hidden', !show.low);  iLow.style.display = show.low  ? '' : 'none'; }
    }

    // Initial sync once player is ready
    try {
      var stateCheck = setInterval(function(){
        try {
          // Accessing getPlayerState will throw until ready
          var st = player.getPlayerState();
          clearInterval(stateCheck);
          syncDuration();
          // volume
          if (rngVol) {
            try { rngVol.value = player.getVolume(); } catch (e) {}
          }
          // icons initial
          try {
            var v = player.getVolume();
            setPlayIcon(st === YT.PlayerState.PLAYING);
            setMuteIcon(player.isMuted(), v);
          } catch (e) {}
          // Build custom quality menu
          if (menuQ && player.getAvailableQualityLevels) {
            buildQualityMenu();
          }
        } catch (e) {}
      }, 150);
    } catch (e) {}

    // Listen to state changes for tick
    try {
      var orig = player.addEventListener ? null : null; // placeholder if needed
    } catch (e) {}

    // Use polling of state via onStateChange (already set in init) by also overriding setState handler
    var _onState = function(e){
      try {
        if (e && e.data === YT.PlayerState.PLAYING) { startTick(); }
        else if (e && (e.data === YT.PlayerState.PAUSED || e.data === YT.PlayerState.ENDED)) { stopTick(); }
        // Update play icon
        try { setPlayIcon(e && e.data === YT.PlayerState.PLAYING); } catch (er) {}
      } catch (er) {}
    };
    // Attach another handler without overriding existing one
    try { player.addEventListener('onStateChange', _onState); } catch (e) {}

    // Rebuild menu on quality change so active item updates
    if (menuQ) {
      try {
        player.addEventListener('onPlaybackQualityChange', function(){ try { buildQualityMenu(); } catch(e){} });
      } catch (e) {}
    }

    // Controls handlers
    if (btnPlay) {
      btnPlay.addEventListener('click', function(){
        try {
          var st = player.getPlayerState();
          if (st === YT.PlayerState.PLAYING) player.pauseVideo(); else player.playVideo();
        } catch (e) {}
      });
    }
    // Volume slider toggle + mute behavior
  var volHideTimer = 0; var VOL_AUTOHIDE_MS = 2000; // 2s
  function showVol(){ if (!rngVol) return; rngVol.classList.remove('hidden'); rngVol.style.display = ''; }
  function hideVol(){ if (!rngVol) return; if (!volAutoHideEnabled) return; rngVol.classList.add('hidden'); rngVol.style.display = 'none'; }
  function scheduleHide(){ if (!volAutoHideEnabled) return; clearTimeout(volHideTimer); volHideTimer = setTimeout(hideVol, VOL_AUTOHIDE_MS); }
    if (btnMute) {
      btnMute.addEventListener('click', function(){
        try {
          if (!rngVol) return;
          // If slider hidden: just reveal it, don't toggle mute yet
          var isHidden = rngVol.classList.contains('hidden') || rngVol.style.display === 'none';
          if (isHidden) { showVol(); scheduleHide(); return; }
          var wasMuted = false; var volNow = 0;
          try { wasMuted = player.isMuted(); volNow = player.getVolume() || 0; } catch(_){}
          if (wasMuted) {
            player.unMute();
            // If unmuted but volume is zero, set a sensible default volume
            if (volNow <= 0) { try { player.setVolume(50); volNow = 50; } catch(_){} }
          } else {
            player.mute();
          }
          setMuteIcon(player.isMuted(), player.getVolume());
          scheduleHide();
        } catch (e) {}
      });
    }
    if (rngVol) {
      // Interacting with slider shows it and postpones auto-hide
      ['pointerdown','input','change','mousemove','touchstart'].forEach(function(evt){
        rngVol.addEventListener(evt, function(){ showVol(); scheduleHide(); });
      });
      // Hide when pointer leaves the control area after a moment
      rngVol.addEventListener('mouseleave', function(){ scheduleHide(); });
    }
    if (rngVol) {
      rngVol.addEventListener('input', function(){
        var v = parseInt(rngVol.value || '0', 10);
        if (isFinite(v)) {
          try {
            v = Math.max(0, Math.min(100, v));
            player.setVolume(v);
            // If volume > 0 and muted, unmute to reflect change
            if (v > 0 && player.isMuted()) player.unMute();
            setMuteIcon(player.isMuted(), v);
          } catch (e) {}
        }
      });
    }
    if (btnCC) {
      btnCC.addEventListener('click', function(){
        try {
          // Toggle subtitles track – YouTube API has limited direct toggling.
          // Strategy: if captions are on, turn off by setting track to empty; else set default language.
          var tr = player.getOption('captions', 'track');
          if (tr && tr.languageCode) {
            player.setOption('captions', 'track', {});
          } else {
            // Attempt using player language
            player.setOption('captions', 'track', { languageCode: (navigator.language || 'en').split('-')[0] });
          }
        } catch (e) {}
      });
    }
    if (selQ) {
      selQ.addEventListener('change', function(){
        var q = selQ.value;
        try {
          if (q === 'default') player.setPlaybackQuality('default');
          else player.setPlaybackQuality(q);
        } catch (e) {}
      });
    }
    if (btnQ && menuQ) {
      btnQ.addEventListener('click', function(e){ e.stopPropagation(); try { buildQualityMenu(); } catch(_){} toggleMenu(); });
      document.addEventListener('click', function(){ hideMenu(); });
    }
    if (btnFs) {
      btnFs.addEventListener('click', function(){
        try {
          var container = iframe.closest('.aspect-video') || iframe.parentElement;
          if (!document.fullscreenElement) {
            (container.requestFullscreen || container.webkitRequestFullscreen || container.msRequestFullscreen || container.mozRequestFullScreen).call(container);
          } else {
            (document.exitFullscreen || document.webkitExitFullscreen || document.msExitFullscreen || document.mozCancelFullScreen).call(document);
          }
        } catch (e) {}
      });
    }

    // Custom quality menu helpers
    function mapLabel(q){
      switch(q){
        case 'highres': return '2160p+'; // best available
        case 'hd2160': return '2160p';
        case 'hd1440': return '1440p';
        case 'hd1080': return '1080p';
        case 'hd720':  return '720p';
        case 'large':  return '480p';
        case 'medium': return '360p';
        case 'small':  return '240p';
        case 'tiny':   return '144p';
        case 'auto':
        case 'default': return 'Auto';
        default:
          // fallback: extract number if present
          var m = /\d{3,4}/.exec(q || '');
          return m ? (m[0] + 'p') : (q || 'Auto');
      }
    }
    function levelRank(q){
      var order = ['highres','hd2160','hd1440','hd1080','hd720','large','medium','small','tiny'];
      var idx = order.indexOf(q);
      return idx === -1 ? (q === 'auto' || q === 'default' ? 999 : 500) : idx;
    }
  function getQualities(){ try { return player.getAvailableQualityLevels() || []; } catch(e){ return []; } }
  function getCurrentQ(){ try { return player.getPlaybackQuality?.() || 'default'; } catch(e){ return 'default'; } }

    // Resolve to the closest available quality (pick next lower if exact not available)
    function resolveAvailableQuality(desired){
      try {
        if (!desired || desired === 'default' || desired === 'auto') return 'default';
        var levels = getQualities(); if (!levels || !levels.length) return desired;
        var order = ['highres','hd2160','hd1440','hd1080','hd720','large','medium','small','tiny'];
        var wantIdx = order.indexOf(desired);
        if (wantIdx === -1) return desired;
        // Build availability map
        var avail = {}; levels.forEach(function(q){ avail[q] = true; });
        for (var i = wantIdx; i < order.length; i++) {
          var q = order[i]; if (avail[q]) return q;
        }
        return 'default';
      } catch(_){ return desired; }
    }
    // Apply quality with retries and setPlaybackQualityRange if available
    function tryForceQuality(p, q){
      try {
        var target = resolveAvailableQuality(q);
        if (target === 'default') { p.setPlaybackQuality('default'); return; }
        if (typeof p.setPlaybackQualityRange === 'function') {
          try { p.setPlaybackQualityRange(target, target); } catch(_){ }
        }
        p.setPlaybackQuality(target);
        // retry attempts
        [250, 600, 1200].forEach(function(ms){ setTimeout(function(){ try { if (p.getPlaybackQuality?.() !== target) p.setPlaybackQuality(target); } catch(_){ } }, ms); });
      } catch(_){ }
    }
    function buildQualityMenu(){
      if (!menuQ) return;
      var levels = (getQualities() || []).slice();
      // Normalize and dedupe (remove 'auto' duplicates, keep only known levels)
      var normalized = [];
      var seen = {};
      levels.forEach(function(q){
        if (!q) return;
        var key = q.toLowerCase();
        if (key === 'auto') key = 'default';
        if (!seen[key]) { seen[key] = true; normalized.push(key); }
      });
      // Ensure Auto at top
      normalized = normalized.filter(function(q){ return q !== 'default'; });
      // Sort by rank descending
      normalized.sort(function(a,b){ return levelRank(a) - levelRank(b); });
      // Build list with Auto first
      var items = ['default'].concat(normalized);
      var cur = (function(){ var c = getCurrentQ(); return (c === 'auto') ? 'default' : c; })();
      menuQ.innerHTML = items.map(function(q){
        var active = (q === cur) || (q === 'default' && (cur === 'default' || cur === 'auto'));
        var label = mapLabel(q);
        return '<button type="button" data-q="'+q+'" class="w-full text-left px-2 py-1 rounded text-[12px] '+(active?'bg-white/15 text-white':'text-gray-200 hover:bg-white/10')+'">'+label+'</button>';
      }).join('');
      menuQ.querySelectorAll('button[data-q]').forEach(function(b){
        b.addEventListener('click', function(ev){
          ev.stopPropagation();
          var q = b.getAttribute('data-q') || 'default';
          // persist preferred quality and try apply if possible
          try { if (playerId) window.__ytPreferredQ[playerId] = q; } catch(_){ }
          try {
            var st = player.getPlayerState?.();
            if (st === YT.PlayerState.PLAYING || st === YT.PlayerState.BUFFERING) {
              tryForceQuality(player, q);
            } else {
              // Will be applied on next play
            }
          } catch(e){}
          hideMenu();
        });
      });
    }
    function toggleMenu(){ if (!menuQ) return; var hidden = menuQ.classList.contains('hidden'); hidden ? showMenu() : hideMenu(); }
    function showMenu(){ if (!menuQ) return; menuQ.classList.remove('hidden'); menuQ.style.display = ''; }
    function hideMenu(){ if (!menuQ) return; menuQ.classList.add('hidden'); menuQ.style.display = 'none'; }

    // Auto-hide whole controls on inactivity; show on tap/click
  var autohideAttr = controls.getAttribute('data-yt-autohide');
  var autoHideEnabled = controls.hasAttribute('data-yt-autohide') && autohideAttr !== '0' && autohideAttr !== 'false';
  // Volume auto-hide follows same flag so it can be kept visible when disabled
  var volAutoHideEnabled = autoHideEnabled;
  var hideTimer = 0; var AUTOHIDE_MS = 2500;
  function showControls(){ controls.style.opacity = '1'; }
  function hideControls(){ controls.style.opacity = '0'; hideMenu(); if (volAutoHideEnabled) hideVol(); }
  function scheduleControlsHide(){ if (!autoHideEnabled) return; clearTimeout(hideTimer); hideTimer = setTimeout(hideControls, AUTOHIDE_MS); }
  function cancelControlsHide(){ if (!autoHideEnabled) return; clearTimeout(hideTimer); }
  if (autoHideEnabled) {
      // Interactions that should keep controls visible
      ['mousemove','pointermove','touchstart','keydown'].forEach(function(evt){ document.addEventListener(evt, function(){ showControls(); scheduleControlsHide(); }, { passive: true }); });
      // Show controls on container tap/click
      var host = iframe.parentElement;
      if (host) {
        host.addEventListener('click', function(){ showControls(); scheduleControlsHide(); });
        host.addEventListener('touchstart', function(){ showControls(); scheduleControlsHide(); }, { passive: true });
      }
      // Start hidden after a short delay
      setTimeout(scheduleControlsHide, 800);
    }
  }
})();
