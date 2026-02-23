const ScrollTracker = {
  name: 'scroll-tracker',

  _collector: null,
  _maxDepth: 0,
  _reported: {},
  _thresholds: [25, 50, 75, 100],

  init: function(collector) {
    const self = this;
    self._collector = collector;

    // Throttle scroll measurement with requestAnimationFrame
    let ticking = false;

    self._onScroll = function() {
      if (!ticking) {
        ticking = true;
        requestAnimationFrame(() => {
          self._measure();
          ticking = false;
        });
      }
    };

    self._onVisibilityChange = function() {
      if (document.visibilityState === 'hidden') {
        self._reportFinal();
      }
    };

    window.addEventListener('scroll', self._onScroll);
    document.addEventListener('visibilitychange', self._onVisibilityChange);
  },

  _measure: function() {
    const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
    const docHeight = Math.max(
      document.documentElement.scrollHeight,
      document.body.scrollHeight
    );
    const winHeight = window.innerHeight;
    const percent = Math.round((scrollTop + winHeight) / docHeight * 100);

    if (percent > this._maxDepth) {
      this._maxDepth = percent;
    }

    // Report threshold crossings
    for (const t of this._thresholds) {
      if (percent >= t && !this._reported[t]) {
        this._reported[t] = true;
        this._collector.track('scroll_depth', {
          threshold: t,
          maxDepth: this._maxDepth
        });
      }
    }
  },

  _reportFinal: function() {
    this._collector.track('scroll_final', {
      maxDepth: this._maxDepth
    });
  },

  destroy: function() {
    if (this._onScroll) {
      window.removeEventListener('scroll', this._onScroll);
    }
    if (this._onVisibilityChange) {
      document.removeEventListener('visibilitychange', this._onVisibilityChange);
    }
  }
};