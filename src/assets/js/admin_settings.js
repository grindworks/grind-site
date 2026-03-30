/**
 * admin_settings.js
 * Handle migration export and search index rebuilding.
 */
document.addEventListener('alpine:init', () => {
  Alpine.data('migrationExporter', (config) => ({
    processing: false,
    progress: 0,
    statusMsg: '',
    csrfToken: config.csrfToken,
    trans: config.trans,

    /**
     * Start export process.
     */
    async startExport() {
      if (this.processing) return;

      window.onbeforeunload = (e) => {
        e.preventDefault();
        e.returnValue = '';
        return '';
      };

      this.processing = true;
      this.progress = 0;
      this.statusMsg = this.trans.init;

      try {
        // Initialize export
        const initRes = await this.callApi('init');
        const totalFiles = initRes.total_files;

        // Archive batch
        let offset = 0;
        const limit = 50;
        let done = false;

        if (totalFiles === 0) {
          this.progress = 90;
        } else {
          while (!done) {
            this.statusMsg = this.trans.archiving + ' ' + Math.min(offset, totalFiles) + '/' + totalFiles;
            this.progress = Math.min(90, Math.round((offset / totalFiles) * 90));

            const batchRes = await this.callApi('archive_batch', {
              offset,
              limit,
            });
            offset = batchRes.next_offset;
            done = batchRes.done;
          }
        }

        // Finalize export
        this.statusMsg = this.trans.finalizing;
        this.progress = 95;
        const finalRes = await this.callApi('finalize');

        this.progress = 100;
        this.statusMsg = this.trans.complete;

        // Trigger download
        window.onbeforeunload = null;

        // Relative path resolution based on current page
        const urlObj = new URL(finalRes.url, window.location.href);
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = urlObj.pathname;
        form.style.display = 'none';

        urlObj.searchParams.forEach((value, key) => {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = key;
          input.value = value;
          form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);

        setTimeout(() => {
          this.processing = false;
          this.progress = 0;
        }, 3000);
      } catch (e) {
        window.showToast('Error: ' + e.message, 'error');
        this.processing = false;
        window.onbeforeunload = null;
      }
    },

    /**
     * Call API endpoint.
     * @param {string} step
     * @param {object} data
     */
    async callApi(step, data = {}) {
      const formData = new FormData();
      formData.append('csrf_token', this.csrfToken);
      formData.append('step', step);
      formData.append('data', JSON.stringify(data));

      const base = (window.grindsBaseUrl || '').replace(/\/$/, '');
      const res = await fetch(`${base}/admin/api/create_migration_package.php`, {
        method: 'POST',
        body: formData,
        credentials: 'include',
      });

      if (!res.ok) throw new Error(`Server Error: ${res.status}`);

      const text = await res.text();
      try {
        const json = JSON.parse(text);
        if (!json.success) throw new Error(json.error || 'Unknown error');

        // Update CSRF token
        if (json.csrf_token) {
          this.csrfToken = json.csrf_token;
        }

        return json;
      } catch (e) {
        console.error('Invalid JSON:', text);
        throw new Error('Invalid server response');
      }
    },
  }));

  Alpine.data('searchIndexer', (config) => ({
    processing: false,
    percentage: 0,
    statusMsg: '',
    csrfToken: config.csrfToken,
    trans: config.trans,

    /**
     * Start index rebuild.
     * @param {boolean} skipConfirm
     */
    async startRebuild(skipConfirm = false) {
      if (!skipConfirm && !confirm(this.trans.confirm)) return;

      window.onbeforeunload = (e) => {
        e.preventDefault();
        e.returnValue = '';
        return '';
      };

      this.processing = true;
      this.percentage = 0;
      this.statusMsg = this.trans.init;

      await this.processBatch(0);
    },

    /**
     * Process batch.
     * @param {number} offset
     */
    async processBatch(offset) {
      try {
        const base = (window.grindsBaseUrl || '').replace(/\/$/, '');
        const res = await fetch(`${base}/admin/api/rebuild_index.php`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            offset: offset,
            csrf_token: this.csrfToken,
          }),
        });

        const data = await res.json();

        if (!data.success) {
          throw new Error(data.error);
        }

        this.percentage = data.percentage;
        this.statusMsg = `${data.next_offset} / ${data.total}`;

        if (data.has_more) {
          await this.processBatch(data.next_offset);
        } else {
          this.statusMsg = this.trans.doneMsg || 'Done!';
          this.percentage = 100;
          setTimeout(() => {
            window.showToast(this.trans.done, 'success');
            this.processing = false;
            this.percentage = 0;
            window.onbeforeunload = null;
          }, 500);
        }
      } catch (e) {
        console.error(e);
        window.showToast(this.trans.error.replace('%s', e.message), 'error');
        this.processing = false;
        window.onbeforeunload = null;
      }
    },
  }));
});
