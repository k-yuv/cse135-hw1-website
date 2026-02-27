/**
 * collector-v9.js – Analytics Collector
 */

(function () {
  'use strict';

  // Config
  const config = {
    endpoint: '',
    enableVitals: true,
    enableErrors: true,
    sampleRate: 1.0,
    debug: false,
    respectConsent: true,
    detectBots: true
  };

  // Internal state
  let initialized = false;
  let blocked = false;
  const customData = {};
  let userId = null;
  const plugins = [];
  const reportedErrors = new Set();
  let errorCount = 0;
  const MAX_ERRORS = 10;

  // Web Vitals
  const vitals = { lcp: null, cls: 0, inp: null };

  // Time-on-page
  let pageShowTime = Date.now();
  let totalVisibleTime = 0;

  // Utility
  function round(n) {
    return Math.round(n * 100) / 100;
  }

  function merge(dst, src) {
    for (const key of Object.keys(src)) {
      dst[key] = src[key];
    }
    return dst;
  }

  // Consent
  function hasConsent() {
    if (navigator.globalPrivacyControl) {
      return false;
    }

    const cookies = document.cookie.split(';');
    for (const c of cookies) {
      const cookie = c.trim();
      if (cookie.indexOf('analytics_consent=') === 0) {
        return cookie.split('=')[1] === 'true';
      }
    }

    return false;
  }

  // Bot detection
  function isBot() {
    if (navigator.webdriver) return true;

    const ua = navigator.userAgent;
    if (/HeadlessChrome|PhantomJS|Lighthouse/i.test(ua)) return true;

    if (/Chrome/.test(ua) && !window.chrome) return true;

    if (window._phantom || window.__nightmare || window.callPhantom) return true;

    return false;
  }

  // Sampling
  function isSampled() {
    if (config.sampleRate >= 1.0) return true;
    if (config.sampleRate <= 0) return false;

    const key = '_collector_sample';
    let val = sessionStorage.getItem(key);
    if (val === null) {
      val = Math.random();
      sessionStorage.setItem(key, val);
    } else {
      val = parseFloat(val);
    }
    return val < config.sampleRate;
  }

  // Session identity
  function getSessionId() {
    let sid = sessionStorage.getItem('_collector_sid');
    if (!sid) {
      sid = Math.random().toString(36).substring(2) + Date.now().toString(36);
      sessionStorage.setItem('_collector_sid', sid);
    }
    return sid;
  }

  // Technographics
  function getNetworkInfo() {
    if (!('connection' in navigator)) return {};
    const conn = navigator.connection;
    return {
      effectiveType: conn.effectiveType,
      downlink: conn.downlink,
      rtt: conn.rtt,
      saveData: conn.saveData
    };
  }

  function getTechnographics() {
    return {
      userAgent: navigator.userAgent,
      language: navigator.language,
      cookiesEnabled: navigator.cookieEnabled,
      viewportWidth: window.innerWidth,
      viewportHeight: window.innerHeight,
      screenWidth: window.screen.width,
      screenHeight: window.screen.height,
      pixelRatio: window.devicePixelRatio,
      cores: navigator.hardwareConcurrency || 0,
      memory: navigator.deviceMemory || 0,
      network: getNetworkInfo(),
      colorScheme: window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light',
      timezone: Intl.DateTimeFormat().resolvedOptions().timeZone,
      allowJS: true,
      allowCSS: true,
      allowImage: true
    };
  }

  // Navigation timing
  function getNavigationTiming() {
      const entries = performance.getEntriesByType('navigation');
      if (!entries.length) return {};
      const n = entries[0];
      return {
        dnsLookup: round(n.domainLookupEnd - n.domainLookupStart),
        tcpConnect: round(n.connectEnd - n.connectStart),
        tlsHandshake: n.secureConnectionStart > 0 ? round(n.connectEnd - n.secureConnectionStart) : 0,
        ttfb: round(n.responseStart - n.requestStart),
        download: round(n.responseEnd - n.responseStart),
        domInteractive: round(n.domInteractive - n.fetchStart),
        domComplete: round(n.domComplete - n.fetchStart),
        loadEvent: round(n.loadEventEnd - n.fetchStart),
        fetchTime: round(n.responseEnd - n.fetchStart),
        transferSize: n.transferSize,
        headerSize: n.transferSize - n.encodedBodySize,
        legacyTiming: performance.timing ? {
          full: performance.timing.toJSON ? performance.timing.toJSON() : {},
          startTime: performance.timing.navigationStart,
          endTime: performance.timing.loadEventEnd,
          totalLoadMs: performance.timing.loadEventEnd - performance.timing.navigationStart
        } : null
      };
    }

  // Resource timing
  function getResourceSummary() {
    const resources = performance.getEntriesByType('resource');
    const summary = {
      script:         { count: 0, totalSize: 0, totalDuration: 0 },
      link:           { count: 0, totalSize: 0, totalDuration: 0 },
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
    return { totalResources: resources.length, byType: summary };
  }

  // Web Vitals
  function initWebVitals() {
    try {
      const lcpObs = new PerformanceObserver((list) => {
        const entries = list.getEntries();
        if (entries.length) {
          vitals.lcp = round(entries[entries.length - 1].startTime);
        }
      });
      lcpObs.observe({ type: 'largest-contentful-paint', buffered: true });
    } catch (e) {}

    try {
      const clsObs = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
          if (!entry.hadRecentInput) {
            vitals.cls = round(vitals.cls + entry.value);
          }
        });
      });
      clsObs.observe({ type: 'layout-shift', buffered: true });
    } catch (e) {}

    try {
      const inpObs = new PerformanceObserver((list) => {
        list.getEntries().forEach((entry) => {
          if (vitals.inp === null || entry.duration > vitals.inp) {
            vitals.inp = round(entry.duration);
          }
        });
      });
      inpObs.observe({ type: 'event', buffered: true, durationThreshold: 16 });
    } catch (e) {}
  }

  function getWebVitals() {
    return { lcp: vitals.lcp, cls: vitals.cls, inp: vitals.inp };
  }

  // Error tracking
  function reportError(errorData) {
    if (errorCount >= MAX_ERRORS) return;

    const key = `${errorData.type}:${errorData.message || ''}:${errorData.source || ''}:${errorData.line || ''}`;
    if (reportedErrors.has(key)) return;
    reportedErrors.add(key);
    errorCount++;

    send({
      type: 'error',
      error: errorData,
      timestamp: new Date().toISOString(),
      url: window.location.href,
      session: getSessionId()
    });

    window.dispatchEvent(new CustomEvent('collector:error', {
      detail: { errorData: errorData, count: errorCount }
    }));
  }

  function initErrorTracking() {
    window.addEventListener('error', (event) => {
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
      } else {
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
    }, true);

    window.addEventListener('unhandledrejection', (event) => {
      const reason = event.reason;
      reportError({
        type: 'promise-rejection',
        message: reason instanceof Error ? reason.message : String(reason),
        stack: reason instanceof Error ? reason.stack : '',
        url: window.location.href
      });
    });
  }

  // Retry queue
  function queueForRetry(payload) {
    try {
      const queue = JSON.parse(sessionStorage.getItem('_collector_retry') || '[]');
      if (queue.length >= 50) return;
      queue.push(payload);
      sessionStorage.setItem('_collector_retry', JSON.stringify(queue));
    } catch (e) {}
  }

  function processRetryQueue() {
    try {
      const queue = JSON.parse(sessionStorage.getItem('_collector_retry') || '[]');
      if (!queue.length) return;
      sessionStorage.removeItem('_collector_retry');
      queue.forEach((payload) => { send(payload); });
    } catch (e) {}
  }

  // Payload delivery
  function send(payload) {
    const markSupported = typeof performance.mark === 'function';
    if (markSupported) {
      performance.mark('collector_send_start');
    }

    if (config.debug) {
      console.log('[Collector] Debug payload:', payload);
      return;
    }

    if (!config.endpoint) {
      console.warn('[Collector] No endpoint configured');
      return;
    }

    const json = JSON.stringify(payload);
    let sent = false;

    if (navigator.sendBeacon) {
      sent = navigator.sendBeacon(
        config.endpoint,
        new Blob([json], { type: 'application/json' })
      );
    }

    if (!sent) {
      fetch(config.endpoint, {
        method: 'POST',
        body: json,
        headers: { 'Content-Type': 'application/json' },
        keepalive: true
      }).catch(() => {
        queueForRetry(payload);
      });
    }

    if (markSupported) {
      performance.mark('collector_send_end');
      performance.measure('collector_send', 'collector_send_start', 'collector_send_end');
    }

    window.dispatchEvent(new CustomEvent('collector:beacon', { detail: payload }));
  }

  // Full pageview payload
  function collect(type) {
    let payload = {
      type: type || 'pageview',
      url: window.location.href,
      title: document.title,
      referrer: document.referrer,
      timestamp: new Date().toISOString(),
      session: getSessionId(),
      technographics: getTechnographics(),
      timing: getNavigationTiming(),
      resources: getResourceSummary(),
      vitals: getWebVitals(),
      errorCount: errorCount,
      customData: customData
    };

    if (userId) {
      payload.userId = userId;
    }

    plugins.forEach((plugin) => {
      if (typeof plugin.beforeSend === 'function') {
        const result = plugin.beforeSend(payload);
        if (result === false) return;
        if (result && typeof result === 'object') {
          payload = result;
        }
      }
    });

    send(payload);

    window.dispatchEvent(new CustomEvent('collector:payload', { detail: payload }));
  }

  // Time-on-page
  function initTimeOnPage() {
    send({
      type: 'page_enter',
      url: window.location.href,
      timestamp: new Date().toISOString(),
      session: getSessionId()
    });

    document.addEventListener('visibilitychange', () => {
      if (document.visibilityState === 'hidden') {
        totalVisibleTime += Date.now() - pageShowTime;

        const exitPayload = {
          type: 'page_exit',
          url: window.location.href,
          timeOnPage: totalVisibleTime,
          vitals: getWebVitals(),
          errorCount: errorCount,
          timestamp: new Date().toISOString(),
          session: getSessionId()
        };

        plugins.forEach((plugin) => {
          if (typeof plugin.onExit === 'function') {
            plugin.onExit(exitPayload);
          }
        });

        send(exitPayload);
      } else {
        pageShowTime = Date.now();
      }
    });
  }

  // Command queue
  function processQueue() {
    const queue = window._cq || [];
    for (const args of queue) {
      const method = args[0];
      const params = args.slice(1);
      if (typeof publicAPI[method] === 'function') {
        publicAPI[method](...params);
      }
    }
    window._cq = {
      push: (args) => {
        const method = args[0];
        const params = args.slice(1);
        if (typeof publicAPI[method] === 'function') {
          publicAPI[method](...params);
        }
      }
    };
  }

  // Public API
  const publicAPI = {
    init: function (options) {
      if (initialized) {
        console.warn('[Collector] Already initialized');
        return;
      }

      if (typeof performance.mark === 'function') {
        performance.mark('collector_init_start');
      }

      if (options) merge(config, options);

      if (config.respectConsent && !hasConsent()) {
        console.log('[Collector] No consent — collection disabled');
        blocked = true;
        initialized = true;
        return;
      }

      if (config.detectBots && isBot()) {
        console.log('[Collector] Bot detected — collection disabled');
        blocked = true;
        initialized = true;
        return;
      }

      if (!isSampled()) {
        console.log(`[Collector] Session not sampled (rate: ${config.sampleRate})`);
        blocked = true;
        initialized = true;
        return;
      }

      initialized = true;
      console.log('[Collector] Initialized', config);

      if (config.enableVitals) initWebVitals();
      if (config.enableErrors) initErrorTracking();
      initTimeOnPage();
      initActivityTracking();

      processRetryQueue();

      if (document.readyState === 'complete') {
        setTimeout(() => { collect('pageview'); }, 0);
      } else {
        window.addEventListener('load', () => {
          setTimeout(() => { collect('pageview'); }, 0);
        });
      }

      if (typeof performance.mark === 'function') {
        performance.mark('collector_init_end');
        performance.measure('collector_init', 'collector_init_start', 'collector_init_end');
      }
    },

    track: function (eventName, eventData) {
      if (!initialized || blocked) return;
      const payload = {
        type: 'event',
        event: eventName,
        data: eventData || {},
        timestamp: new Date().toISOString(),
        url: window.location.href,
        session: getSessionId(),
        customData: customData
      };
      if (userId) payload.userId = userId;
      send(payload);
    },

    set: function (key, value) {
      customData[key] = value;
    },

    identify: function (id) {
      userId = id;
    },

    use: function (plugin) {
      if (!plugin || typeof plugin !== 'object') {
        console.warn('[Collector] Invalid plugin');
        return;
      }
      plugins.push(plugin);
      if (typeof plugin.init === 'function') {
        plugin.init(config);
      }
      console.log(`[Collector] Plugin registered: ${plugin.name || '(unnamed)'}`);
    }
  };

  function ActivityTracking(){

    //User activity tracking
    const IDLE_USER_TIMEOUT = 30000; // 30 seconds
    let userLastActive = Date.now();
    let idleStart = null;
    let idleEnd;
    let idleTimerHandle = null;

     function sendActivity(data) {
      if (!initialized || blocked) return;
      send({
        type: 'activity',
        session: getSessionId(),
        timestamp: new Date().toISOString(),
        url: window.location.href,
        ...data
      });
    }

    function activeUser(){
      if(idleStart !== null){
        idleEnd = Date.now();
        const idleDuration = idleEnd - idleStart;
        totalVisibleTime += idleDuration;
        
        sendActivity({
        activity: 'idle_end',
        idleEndedAt: new Date(idleEnd).toISOString(),
        idleDurationMs: idleDuration
      });
      idleStart = null;
    }

    userLastActive = Date.now();
    clearTimeout(idleTimerHandle);
    idleTimerHandle = setTimeout(() => {
      idleStart = Date.now();
    }, IDLE_USER_TIMEOUT);
  }

    const activityHandlers = {
      mousemove: (e) => ({ x: e.clientX, y: e.clientY }),
      click:     (e) => ({ x: e.clientX, y: e.clientY, button: e.button }),
      scroll:    ()  => ({ x: window.scrollX, y: window.scrollY }),
      keydown:   (e) => ({ key: e.key, code: e.code }),
      keyup:     (e) => ({ key: e.key, code: e.code })
    };

    let lastMouseSend = 0;

    Object.entries(activityHandlers).forEach(([eventName, extractData]) => {
      document.addEventListener(eventName, (e) => {
        onActivity();

        if (eventName === 'mousemove') {
          const now = Date.now();
          if (now - lastMouseSend < 200) return;
          lastMouseSend = now;
        }

        idleTimerHandle = setTimeout(() => {
          idleStart = Date.now();
        }, IDLE_USER_TIMEOUT);
      });
    });
}

  // Bootstrap
  processQueue();

  // Test hooks
  window.__collector = {
    getNavigationTiming: getNavigationTiming,
    getResourceSummary: getResourceSummary,
    getTechnographics: getTechnographics,
    getWebVitals: getWebVitals,
    getSessionId: getSessionId,
    getNetworkInfo: getNetworkInfo,
    reportError: reportError,
    collect: collect,
    hasConsent: hasConsent,
    isBot: isBot,
    isSampled: isSampled,
    getErrorCount: () => errorCount,
    getConfig: () => config,
    isBlocked: () => blocked,
    api: publicAPI
  };

  _cq.push(['init', {
    endpoint: '/log',
    enableVitals: true,
    enableErrors: true
  }]);

})();