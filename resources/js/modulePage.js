(function(){
  if (window.__modulePageReady) return; window.__modulePageReady = true;

  // Expose Alpine x-data helpers
  window.videoGate = function(count) {
    count = count | 0;
    return {
      videoCount: count,
      ended: {},
      get done() { return this.videoCount === 0 || Object.keys(this.ended).length >= this.videoCount; }
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

    window.__initYtPlayers = function() {
      if (!(window.YT && YT.Player)) return;
      document.querySelectorAll('iframe.yt-player').forEach(function(el) {
        if (el.dataset.playerBound === '1') return;
        el.dataset.playerBound = '1';
        var id = el.id;
        var endId = el.dataset.endId || id;
        try {
          var player = new YT.Player(id, {
            events: {
              'onStateChange': function(e) {
                if (e && e.data === YT.PlayerState.ENDED) {
                  window.dispatchEvent(new CustomEvent('module-video-ended', { detail: { id: endId } }));
                }
              }
            }
          });
          window.__ytPlayers[id] = player;
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
})();
