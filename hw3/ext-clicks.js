const ClickTracker = {
  name: 'click-tracker',

  _handler: null,

  init: function(collector) {
    const self = this;
    let lastClick = 0;

    self._handler = function(event) {
      // Debounce: ignore clicks within 300ms of each other
      const now = Date.now();
      if (now - lastClick < 300) return;
      lastClick = now;

      const target = event.target;

      collector.track('click', {
        // What was clicked
        tagName: target.tagName,
        id: target.id || undefined,
        className: target.className || undefined,
        text: (target.textContent || '').substring(0, 100),
        // Where in the page
        x: event.clientX,
        y: event.clientY,
        // CSS selector path
        selector: self._getSelector(target)
      });
    };

    document.addEventListener('click', self._handler, true);
  },

  _getSelector: function(el) {
    const parts = [];
    while (el && el !== document.body) {
      let part = el.tagName.toLowerCase();
      if (el.id) {
        part += `#${el.id}`;
        parts.unshift(part);
        break;
      }
      if (el.className && typeof el.className === 'string') {
        part += `.${el.className.trim().split(/\s+/).join('.')}`;
      }
      parts.unshift(part);
      el = el.parentElement;
    }
    return parts.join(' > ');
  },

  destroy: function() {
    if (this._handler) {
      document.removeEventListener('click', this._handler, true);
    }
  }
};