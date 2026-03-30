/**
 * static_tools.js
 * GrindSite - Static Site Search & Form Handler
 */
document.addEventListener('DOMContentLoaded', () => {
  // Read config injected by SSG (provides localized labels and settings)
  const cfg = window.grindsSearchConfig || {};
  const labelNoResults = cfg.noResults || 'No results found.';
  const labelReadMore = cfg.readMore || 'Read more';
  const labelLoadMore = cfg.loadMore || 'Load More';
  const cacheBust = cfg.cacheBust || String(Date.now());

  // Handle search forms
  const forms = document.querySelectorAll('.grinds-search-form');
  forms.forEach((form) => {
    form.addEventListener('submit', (e) => {
      e.preventDefault();
      const input = form.querySelector('input[name="q"]');
      if (input && input.value) {
        const action = form.getAttribute('action') || 'search.html';
        // Redirect to results
        window.location.href = action + '?q=' + encodeURIComponent(input.value);
      }
    });
  });

  // Execute search logic
  const resultContainer = document.getElementById('static-search-results');
  const queryDisplay = document.getElementById('search-query-display');

  if (resultContainer) {
    const params = new URLSearchParams(window.location.search);
    const q = params.get('q');

    if (q) {
      if (queryDisplay) queryDisplay.textContent = q;
      const qLower = q.toLowerCase();
      const keywords = qLower.split(/[\s\u3000]+/).filter((k) => k.length > 0);

      // Show loading animation
      resultContainer.innerHTML =
        '<div class="flex justify-center p-10"><div class="border-gray-900 border-b-2 rounded-full w-8 h-8 animate-spin"></div></div>';

      const escapeHtml = (unsafe) => {
        return unsafe
          .replace(/&/g, '&amp;')
          .replace(/</g, '&lt;')
          .replace(/>/g, '&gt;')
          .replace(/"/g, '&quot;')
          .replace(/'/g, '&#039;');
      };

      const highlightHtml = (text, keywords) => {
        let result = escapeHtml(text || '');
        if (!keywords || keywords.length === 0) return result;

        const escapeRegExp = (string) => string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');

        keywords.forEach((kw) => {
          if (!kw) return;
          const escapedKw = escapeRegExp(escapeHtml(kw));
          const regex = new RegExp(`(${escapedKw})`, 'gi');
          result = result.replace(regex, '<mark class="bg-yellow-200 text-gray-900 rounded-sm px-0.5">$1</mark>');
        });
        return result;
      };

      const baseUrl = window.grindsBaseUrl || './';
      const searchLimit = window.grindsSearchLimit || 1000;

      /**
       * Filter search data by keywords and render results.
       * @param {Array} allData - Array of search data items
       */
      const processSearchData = (allData) => {
        const allMatches = allData
          .filter((item) => {
            const text = ((item.t || '') + ' ' + (item.k || '')).toLowerCase();
            return keywords.every((kw) => text.includes(kw));
          })
          .slice(0, searchLimit);

        renderResults(allMatches);
      };

      /**
       * Render search results into the container.
       * @param {Array} allMatches - Filtered search results
       */
      const renderResults = (allMatches) => {
        if (allMatches.length > 0) {
          resultContainer.innerHTML =
            '<div class="gap-8 grid grid-cols-1 md:grid-cols-2" id="search-results-grid"></div>';
          const grid = document.getElementById('search-results-grid');

          let renderedCount = 0;
          const batchSize = 20;

          const renderBatch = () => {
            const batch = allMatches.slice(renderedCount, renderedCount + batchSize);
            let html = '';
            batch.forEach((item) => {
              html += `
                <article class="flex flex-col bg-white shadow-md p-6 border border-gray-100 rounded-lg">
                    <h3 class="mb-2 font-bold text-xl">
                        <a href="${item.u}" class="text-gray-900 hover:text-blue-600">${highlightHtml(item.t, keywords)}</a>
                    </h3>
                    <p class="mb-4 text-gray-600 text-sm line-clamp-3">${highlightHtml(item.d, keywords)}</p>
                    <a href="${item.u}" class="mt-auto font-bold text-blue-600 text-sm hover:underline">${escapeHtml(labelReadMore)}</a>
                </article>`;
            });
            grid.insertAdjacentHTML('beforeend', html);
            renderedCount += batch.length;

            // Handle load more
            let loadMoreBtn = document.getElementById('search-load-more');
            if (renderedCount < allMatches.length) {
              if (!loadMoreBtn) {
                loadMoreBtn = document.createElement('button');
                loadMoreBtn.id = 'search-load-more';
                loadMoreBtn.className =
                  'col-span-1 md:col-span-2 mt-8 px-6 py-3 bg-gray-100 hover:bg-gray-200 text-gray-800 font-bold rounded transition-colors mx-auto block';
                loadMoreBtn.textContent = labelLoadMore;
                loadMoreBtn.onclick = renderBatch;
                resultContainer.appendChild(loadMoreBtn);
              } else {
                resultContainer.appendChild(loadMoreBtn);
              }
            } else {
              if (loadMoreBtn) loadMoreBtn.remove();
            }
          };

          renderBatch();
        } else {
          resultContainer.innerHTML = `<div class="p-8 text-gray-500 text-center">${escapeHtml(labelNoResults)}</div>`;
        }
      };

      const dataUrl = baseUrl + `assets/data/`;

      /**
       * Fetches and processes search data from chunks.
       */
      const loadAndProcessSearchData = async () => {
        try {
          // 1. Fetch the manifest file
          const manifestResponse = await fetch(`${dataUrl}search_manifest.json?v=${cacheBust}`);
          if (!manifestResponse.ok) throw new Error('Search index manifest not found.');
          const manifest = await manifestResponse.json();

          if (!manifest.files || manifest.files.length === 0) {
            processSearchData([]); // No data to search
            return;
          }

          // 2. Fetch all chunk files in parallel
          const chunkPromises = manifest.files.map((file) =>
            fetch(`${dataUrl}${file}?v=${cacheBust}`).then((res) => {
              if (!res.ok) throw new Error(`Failed to load search data chunk: ${file}`);
              return res.json();
            })
          );
          const allChunks = await Promise.all(chunkPromises);

          // 3. Combine chunks and process
          const allData = allChunks.flat();
          processSearchData(allData);
        } catch (error) {
          console.error('Failed to load search data:', error);
          resultContainer.innerHTML = `<div class="p-8 text-gray-500 text-center">${escapeHtml(error.message)}</div>`;
        }
      };

      loadAndProcessSearchData();
    }
  }
});
