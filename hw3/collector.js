(function () {
  'use strict';

  const ENDPOINT = 'https://example.com/collect';

  function getSessionId() {
    let sid = sessionStorage.getItem('_collector_sid');
    if (!sid) {
      sid = Math.random().toString(36).substring(2) + Date.now().toString(36);
      sessionStorage.setItem('_collector_sid', sid);
    }
    return sid;
  }

  function getTechnographics() {
    let networkInfo = {};
    if ('connection' in navigator) {
      const conn = navigator.connection;
      networkInfo = {
        effectiveType: conn.effectiveType,
        downlink: conn.downlink,
        rtt: conn.rtt,
        saveData: conn.saveData,
      };
    }

    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      cookiesEnabled: navigator.cookieEnabled,
      viewportWidth: window.innerWidth,
      viewportHeight: window.innerHeight,
      screenWidth: screen.width,
      screenHeight: screen.height,
      pixelRatio: window.devicePixelRatio,
      cores: navigator.hardwareConcurrency || 0,
      memory: navigator.deviceMemory || 0,
      network: networkInfo,
      colorScheme: window.matchMedia('(prefers-color-scheme: dark)').matches
        ? 'dark'
        : 'light',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
    };
  }

  // 🔹 New: wrap the blob / beacon logic in a function
  function send(payload) {
    const blob = new Blob([JSON.stringify(payload)], {
      type: 'application/json',
    });

    if (navigator.sendBeacon) {
      navigator.sendBeacon(ENDPOINT, blob);
    } else {
      fetch(ENDPOINT, { method: 'POST', body: blob, keepalive: true });
    }
  }

  function collect() {
    const payload = {
      url: window.location.href,
      title: document.title,
      referrer: document.referrer,
      timestamp: new Date().toISOString(),
      type: 'pageview',
      session: getSessionId(),
      technographics: getTechnographics(),
      timing: getNavigationTiming(),
      resources: getResourceSummary()
    };

    send(payload);
  }

  function getNavigationTiming() {
    const entries = performance.getEntriesByType('navigation');
    if (!entries.length) return {};

    const n = entries[0];

    return {
      // DNS lookup time
      dnsLookup: round(n.domainLookupEnd - n.domainLookupStart),
      // TCP connection time
      tcpConnect: round(n.connectEnd - n.connectStart),
      // TLS handshake (HTTPS only)
      tlsHandshake: n.secureConnectionStart > 0
        ? round(n.connectEnd - n.secureConnectionStart)
        : 0,
      // Time to First Byte
      ttfb: round(n.responseStart - n.requestStart),
      // Download time (response)
      download: round(n.responseEnd - n.responseStart),
      // DOM interactive (HTML parsed, not all resources loaded)
      domInteractive: round(n.domInteractive - n.fetchStart),
      // DOM complete (all resources loaded)
      domComplete: round(n.domComplete - n.fetchStart),
      // Full page load
      loadEvent: round(n.loadEventEnd - n.fetchStart),
      // Total fetch time
      fetchTime: round(n.responseEnd - n.fetchStart),
      // Transfer size and header overhead
      transferSize: n.transferSize,
      headerSize: n.transferSize - n.encodedBodySize
    };
  }

  function round(n) {
    return Math.round(n * 100) / 100;
  }

  function getResourceSummary() {
    const resources = performance.getEntriesByType('resource');

    const summary = {
      script:         { count: 0, totalSize: 0, totalDuration: 0 },
      link:           { count: 0, totalSize: 0, totalDuration: 0 },  // CSS
      img:            { count: 0, totalSize: 0, totalDuration: 0 },
      font:           { count: 0, totalSize: 0, totalDuration: 0 },
      fetch:          { count: 0, totalSize: 0, totalDuration: 0 },
      xmlhttprequest: { count: 0, totalSize: 0, totalDuration: 0 },
      other:          { count: 0, totalSize: 0, totalDuration: 0 }
    };

    resources.forEach((r) => {
      const type = summary[r.initiatorType] ? r.initiatorType : 'other';
      summary[type].count++;
      summary[type].totalSize += r.transferSize || 0;
      summary[type].totalDuration += r.duration || 0;
    });

    return {
      totalResources: resources.length,
      byType: summary
    };
  }

  // Auto-collect once the page is loaded
  if (document.readyState === 'complete') {
    collect();
  } else {
    window.addEventListener('load', collect);
  }

    window.addEventListener('load', () => {
    setTimeout(() => {
      const payload = {
        url: window.location.href,
        type: 'pageview-detailed',
        session: getSessionId(),
        timing: getNavigationTiming(),
        resources: getResourceSummary()
      };
      send(payload);
    }, 0);
  });

  //LCP
  let lcpValue = 0;

  function observeLCP() {
    const observer = new PerformanceObserver((list) => {
      const entries = list.getEntries();
      const lastEntry = entries[entries.length - 1];
      // LCP uses renderTime if available, otherwise loadTime
      lcpValue = lastEntry.renderTime || lastEntry.loadTime;
    });
    observer.observe({ type: 'largest-contentful-paint', buffered: true });
    return observer;
  }

  //CLS
  let clsValue = 0;

  function observeCLS() {
    const observer = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        // Only count shifts without recent user input
        if (!entry.hadRecentInput) {
          clsValue += entry.value;
        }
      }
    });
    observer.observe({ type: 'layout-shift', buffered: true });
    return observer;
  }

  //INP 
  let inpValue = 0;

  function observeINP() {
    const interactions = [];

    const observer = new PerformanceObserver((list) => {
      for (const entry of list.getEntries()) {
        // event entries with interactionId represent user interactions
        if (entry.interactionId) {
          interactions.push(entry.duration);
        }
      }
      // INP is the worst interaction (simplified)
      // The actual algorithm uses the 98th percentile
      if (interactions.length > 0) {
        interactions.sort((a, b) => b - a);
        inpValue = interactions[0];
      }
    });
    observer.observe({ type: 'event', buffered: true, durationThreshold: 16 });
    return observer;
  }

  const thresholds = {
  lcp: [2500, 4000],
  cls: [0.1, 0.25],
  inp: [200, 500]
  };

  function getVitalsScore(metric, value) {
    const t = thresholds[metric];
    if (!t) return null;
    if (value <= t[0]) return 'good';
    if (value <= t[1]) return 'needsImprovement';
    return 'poor';
  }

  document.addEventListener('visibilitychange', () => {
  if (document.visibilityState === 'hidden') {
    sendVitals();
  }
});

  function sendVitals() {
    const vitals = {
      lcp: { value: round(lcpValue), score: getVitalsScore('lcp', lcpValue) },
      cls: { value: round(clsValue * 1000) / 1000, score: getVitalsScore('cls', clsValue) },
      inp: { value: round(inpValue), score: getVitalsScore('inp', inpValue) }
    };
    send({
      type: 'vitals',
      vitals: vitals,
      url: window.location.href,
      timestamp: new Date().toISOString()
    });
  }

  // Start observers immediately (they buffer past entries)
  const lcpObserver = observeLCP();
  const clsObserver = observeCLS();
  const inpObserver = observeINP();

  // Initial beacon: timing + technographics (same as v4)
  window.addEventListener('load', () => {
    setTimeout(() => {
      collect();
    }, 0);
  });

  // Vitals beacon: final values when page is hidden
  document.addEventListener('visibilitychange', () => {
    if (document.visibilityState === 'hidden') {
      sendVitals();
    }
  });

  window.addEventListener('error', (event) => {
  // event is an ErrorEvent when it's a JS error
    if (event instanceof ErrorEvent) {
      reportError({
        type: 'js-error',
        message: event.message,
        source: event.filename,
        line: event.lineno,
        column: event.colno,
        stack: event.error ? event.error.stack : '',
        url: window.location.href
      });
    }
  });

  window.addEventListener('unhandledrejection', (event) => {
    const reason = event.reason;
    reportError({
      type: 'promise-rejection',
      message: reason instanceof Error ? reason.message : String(reason),
      stack: reason instanceof Error ? reason.stack : '',
      url: window.location.href
    });
  });

  window.addEventListener('error', (event) => {
  // Resource errors bubble up as plain Events (not ErrorEvent)
    if (!(event instanceof ErrorEvent)) {
      const target = event.target;
      if (target && (target.tagName === 'IMG' || target.tagName === 'SCRIPT' || target.tagName === 'LINK')) {
        reportError({
          type: 'resource-error',
          tagName: target.tagName,
          src: target.src || target.href || '',
          url: window.location.href
        });
      }
    }
  }, true); // Note: must use capture phase!


  function reportError(errorData) {
    // Rate limit
    if (errorCount >= MAX_ERRORS) return;

    // Deduplicate by message + source + line
    const key = `${errorData.type}:${errorData.message}:${errorData.source || ''}:${errorData.line || ''}`;
    if (reportedErrors.has(key)) return;
    reportedErrors.add(key);
    errorCount++;

    // Send error beacon
    send({
      type: 'error',
      error: errorData,
      timestamp: new Date().toISOString(),
      url: window.location.href
    });
  }

})();