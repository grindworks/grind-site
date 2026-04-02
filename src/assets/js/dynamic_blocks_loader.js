/**
 * dynamic_blocks_loader.js
 * Initialize dynamic blocks (KaTeX, Prism.js, Countdown, etc.) on the frontend.
 */
window.grindsInitDynamicBlocks = function (rootNode, config) {
  if (!rootNode) rootNode = document;
  config = config || window.grindsDynamicConfig || {};

  // 1. Auto Stop Media (Iframes, Audio, Video)
  if ('IntersectionObserver' in window) {
    var mediaObserver = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (!e.isIntersecting) {
          var i = e.target.querySelector('iframe');
          if (i) {
            i.contentWindow.postMessage('{"event":"command","func":"pauseVideo","args":""}', '*');
          }
          var m = e.target.querySelector('audio,video');
          if (m && typeof m.pause === 'function') {
            m.pause();
          }
        }
      });
    });
    rootNode.querySelectorAll('.grinds-auto-stop:not([data-observed])').forEach(function (el) {
      mediaObserver.observe(el);
      el.setAttribute('data-observed', 'true');
    });

    // 2. Progress Bar Animation
    var progObserver = new IntersectionObserver(
      function (entries) {
        entries.forEach(function (entry) {
          if (entry.isIntersecting) {
            var bars = entry.target.querySelectorAll('div[data-width]');
            bars.forEach(function (bar) {
              bar.style.width = bar.getAttribute('data-width');
            });
            progObserver.unobserve(entry.target);
          }
        });
      },
      { threshold: 0.1 }
    );
    rootNode.querySelectorAll('.cms-block-progress_bar:not([data-observed])').forEach(function (el) {
      progObserver.observe(el);
      el.setAttribute('data-observed', 'true');
    });
  }

  // 3. Countdowns
  rootNode.querySelectorAll('.cms-block-countdown:not([data-observed])').forEach(function (el) {
    el.setAttribute('data-observed', 'true');
    var deadline = el.getAttribute('data-deadline');
    var msg = el.getAttribute('data-finish-msg');
    var display = el.querySelector('.timer-display');
    if (!deadline || !display) return;
    var safeDeadline = deadline.replace(/-/g, '/');
    var end = new Date(safeDeadline).getTime();
    var timer = setInterval(function () {
      var now = new Date().getTime();
      var dist = end - now;
      if (dist < 0 || isNaN(dist)) {
        clearInterval(timer);
        display.innerHTML = msg;
        return;
      }
      var d = Math.floor(dist / (1000 * 60 * 60 * 24));
      var h = Math.floor((dist % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
      var m = Math.floor((dist % (1000 * 60 * 60)) / (1000 * 60));
      var s = Math.floor((dist % (1000 * 60)) / 1000);
      display.innerText =
        d +
        'd ' +
        h.toString().padStart(2, '0') +
        'h ' +
        m.toString().padStart(2, '0') +
        'm ' +
        s.toString().padStart(2, '0') +
        's';
    }, 1000);
  });

  // Asset loaders
  var loadCss = function (url) {
    var link = document.createElement('link');
    link.rel = 'stylesheet';
    link.href = url;
    link.crossOrigin = 'anonymous';
    document.head.appendChild(link);
  };
  var loadJs = function (url, callback) {
    var script = document.createElement('script');
    script.src = url;
    script.crossOrigin = 'anonymous';
    script.onload = callback;
    document.head.appendChild(script);
  };

  // 4. Math (KaTeX Loader)
  var mathBlocks = rootNode.querySelectorAll('.cms-block-math');
  if (mathBlocks.length > 0 && config.katex_css) {
    var renderMath = function (retries) {
      retries = retries || 0;
      if (typeof renderMathInElement === 'function') {
        renderMathInElement(rootNode, {
          delimiters: [
            { left: '$$', right: '$$', display: true },
            { left: '$', right: '$', display: false },
            { left: '\\\\(', right: '\\\\)', display: false },
            { left: '\\\\[', right: '\\\\]', display: true },
          ],
          throwOnError: false,
        });
      } else if (retries < 50) {
        setTimeout(function () {
          renderMath(retries + 1);
        }, 100);
      }
    };
    if (!window.grindsKatexLoaded) {
      window.grindsKatexLoaded = true;
      loadCss(config.katex_css);
      loadJs(config.katex_js, function () {
        loadJs(config.katex_auto, renderMath);
      });
    } else {
      renderMath();
    }
  }

  // 5. Code Syntax Highlighting (Prism.js Loader)
  var codeBlocks = rootNode.querySelectorAll('pre code[class*="language-"]');
  if (codeBlocks.length > 0 && config.prism_css) {
    var highlightCode = function (retries) {
      retries = retries || 0;
      if (typeof Prism !== 'undefined') {
        Prism.highlightAllUnder(rootNode);
      } else if (retries < 50) {
        setTimeout(function () {
          highlightCode(retries + 1);
        }, 100);
      }
    };
    if (!window.grindsPrismLoaded) {
      window.grindsPrismLoaded = true;
      loadCss(config.prism_css);
      loadJs(config.prism_js, function () {
        loadJs(config.prism_auto, highlightCode);
      });
    } else {
      highlightCode();
    }
  }
};

document.addEventListener('DOMContentLoaded', function () {
  window.grindsInitDynamicBlocks(document);
});
