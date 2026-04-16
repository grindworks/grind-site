/**
 * media_manager.js
 * Shared media management logic and API client.
 */

window.GrindsMediaApi = {
  /**
   * Get API URL.
   * @param {string} endpoint
   */
  getApiUrl(endpoint) {
    const base = (window.grindsBaseUrl || '').replace(/\/$/, '');
    return `${base}/admin/api/${endpoint}`;
  },

  /**
   * Send API request.
   * @param {string} endpoint
   * @param {object} options
   */
  async request(endpoint, options = {}) {
    const url = this.getApiUrl(endpoint);
    const headers = {
      'X-Requested-With': 'XMLHttpRequest',
      ...(options.headers || {}),
    };

    const res = await fetch(url, { ...options, headers });

    if (res.status === 401) {
      throw new Error('SESSION_EXPIRED');
    }

    // Handle timeout
    if (res.status === 408) {
      const data = await res.json();
      const err = new Error(data.error || 'Timeout');
      err.status = 408;
      throw err;
    }

    if (!res.ok) {
      let errorMsg = 'Network Error';
      try {
        const data = await res.json();
        errorMsg = data.error || errorMsg;
      } catch (e) {}
      const err = new Error(errorMsg);
      err.status = res.status;
      throw err;
    }

    return await res.json();
  },

  /**
   * List media files.
   * @param {number} page
   * @param {object} params
   */
  async list(page, params = {}) {
    const query = new URLSearchParams({
      page: page,
      limit: params.limit || 20,
      q: params.keyword || '',
      sort: params.sort || 'newest',
      type: params.type || 'all',
      ext: params.ext || '',
      date: params.date || '',
      tag: params.tag || '',
      status: params.status || 'all',
    });
    return this.request(`media_list.php?${query.toString()}`);
  },

  /**
   * Upload file.
   * @param {File} file
   * @param {string} csrfToken
   * @param {function|null} onProgress Callback for upload progress
   */
  upload(file, csrfToken, onProgress = null) {
    return new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();
      const url = this.getApiUrl('upload.php');

      xhr.open('POST', url, true);
      xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

      if (onProgress && xhr.upload) {
        xhr.upload.onprogress = (e) => {
          if (e.lengthComputable) {
            const percentComplete = e.total > 0 ? Math.round((e.loaded / e.total) * 100) : 100;
            onProgress(percentComplete);
          }
        };
      }

      xhr.onload = () => {
        if (xhr.status === 401) {
          reject(new Error('SESSION_EXPIRED'));
          return;
        }
        if (xhr.status >= 200 && xhr.status < 300) {
          try {
            resolve(JSON.parse(xhr.responseText));
          } catch (e) {
            reject(new Error('Invalid JSON response'));
          }
        } else {
          let errorMsg = 'Network Error';
          try {
            errorMsg = JSON.parse(xhr.responseText).error || errorMsg;
          } catch (e) {}
          const err = new Error(errorMsg);
          err.status = xhr.status;
          reject(err);
        }
      };

      xhr.onerror = () => {
        reject(new Error('Network Error'));
      };

      const formData = new FormData();
      formData.append('image', file);
      formData.append('csrf_token', csrfToken);
      xhr.send(formData);
    });
  },

  /**
   * Delete media.
   * @param {number|number[]} ids
   * @param {string} csrfToken
   * @param {boolean} force
   */
  async delete(ids, csrfToken, force = false) {
    // Handle ID or array
    const idList = Array.isArray(ids) ? ids : [ids];
    const body = {
      csrf_token: csrfToken,
      force: force,
    };

    if (idList.length === 1) {
      body.id = idList[0];
    } else {
      body.ids = idList;
    }

    return this.request('media_delete.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
  },

  /**
   * Update metadata.
   * @param {number} id
   * @param {object} data
   * @param {string[]} tags
   * @param {string} csrfToken
   */
  async update(id, data, tags, csrfToken) {
    return this.request('media_update.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        id: id,
        metadata: data,
        tags: tags,
        csrf_token: csrfToken,
      }),
    });
  },

  /**
   * Get tags.
   * @param {number|null} id
   */
  async getTags(id = null) {
    const query = id ? `?id=${id}` : '?action=suggestions';
    return this.request(`media_tags.php${query}`);
  },

  /**
   * Get usage info.
   * @param {number} id
   */
  async getUsage(id) {
    return this.request(`media_usage.php?id=${id}`);
  },
};

// UI helpers
window.GrindsMediaHelpers = {
  /**
   * Get max upload size.
   */
  getMaxSize() {
    return window.grindsUploadMax || 2 * 1024 * 1024;
  },

  /**
   * Validate file size.
   * @param {File} file
   */
  validateSize(file) {
    const max = this.getMaxSize();
    if (file.size > max) {
      const maxMB = Math.round(max / 1024 / 1024);
      const trans = window.grindsTranslations || {};
      let msg = trans.file_too_large || 'File too large';
      msg = msg.includes('%s') ? msg.replace('%s', maxMB + 'MB') : msg + ` (Max: ${maxMB}MB)`;
      window.showToast(msg + ': ' + file.name, 'error');
      return false;
    }
    return true;
  },

  /**
   * Upload file.
   * @param {File} file
   * @param {string} csrfToken
   * @param {function|null} onProgress
   */
  async uploadFile(file, csrfToken, onProgress = null) {
    if (!this.validateSize(file)) return null;
    const json = await GrindsMediaApi.upload(file, csrfToken, onProgress);
    if (json.success && json.file) {
      if (json.file.file_type === 'image/svg+xml') json.file.is_image = true;
      return json.file;
    } else {
      const trans = window.grindsTranslations || {};
      const msg = trans.upload_failed || 'Upload failed: %s';
      window.showToast(msg.replace('%s', json.error || 'Unknown error'), 'error');
      return null;
    }
  },

  /**
   * Delete with confirmation.
   * @param {number|number[]} ids
   * @param {string} csrfToken
   * @param {object} trans
   */
  async deleteWithConfirmation(ids, csrfToken, trans = {}) {
    const idList = Array.isArray(ids) ? ids : [ids];
    const isBulk = idList.length > 1;
    const msg = isBulk
      ? (trans.confirm_bulk_delete || 'Delete %s items?').replace('%s', idList.length)
      : trans.confirm_delete_media || trans.confirm_delete || 'Delete permanently?';

    if (!confirm(msg)) return null;

    const attempt = async (force) => {
      try {
        const data = await GrindsMediaApi.delete(ids, csrfToken, force);
        if (data.success) return data;
        window.showToast((trans.delete_error || 'Error: %s').replace('%s', data.error || 'Unknown'), 'error');
        return null;
      } catch (e) {
        if (e.status === 409) {
          window.showToast(e.message, 'error');
          return null;
        }
        throw e;
      }
    };
    return await attempt(false);
  },
};

document.addEventListener('alpine:init', () => {
  // Register component once
  if (Alpine.data('mediaManager')) return;

  Alpine.data('mediaManager', () => ({
    files: [],
    selectedIds: [],
    allTags: [],
    tagInput: '',
    tagSuggestions: [],
    showTagSuggestions: false,
    lastSelectedId: null,
    loading: false,
    isUploading: false,
    uploadProgress: '',
    uploadProgressPercent: 0,
    isDragging: false,
    maxSize: GrindsMediaHelpers.getMaxSize(),

    showFilters: false,

    // Filter state
    page: 1,
    limit: 20,
    total: 0,
    hasMore: false,
    keyword: '',
    searchKeywords: [],
    searchInput: '',
    sort: 'newest',
    typeFilter: 'all',
    extFilter: '',
    dateFilter: '',
    tagFilter: '',
    statusFilter: 'all',
    viewMode: 'grid',
    gridCols: 5,

    // Modal
    detailModalOpen: false,
    activeFile: null,
    fileUsageList: [],
    isFetchingUsage: false,
    metaForm: { id: null, license: 'unknown', tags: [], credit: '', is_ai: false, prompt: '' },

    // Translation strings
    trans: window.grindsTranslations || {},

    get isFiltered() {
      return (
        this.typeFilter !== 'all' ||
        this.extFilter !== '' ||
        this.dateFilter !== '' ||
        this.tagFilter !== '' ||
        this.statusFilter !== 'all'
      );
    },

    get activeFilters() {
      const filters = [];
      if (this.typeFilter !== 'all') {
        let label = this.typeFilter;
        if (this.typeFilter === 'image') label = this.trans.filter_images || '画像';
        if (this.typeFilter === 'video') label = this.trans.Video || '動画';
        if (this.typeFilter === 'audio') label = this.trans.Audio || '音声';
        if (this.typeFilter === 'document') label = this.trans.filter_docs || 'ドキュメント';
        filters.push({ type: 'type', label: label });
      }
      if (this.extFilter !== '') {
        filters.push({ type: 'ext', label: `EXT: ${this.extFilter.toUpperCase()}` });
      }
      if (this.dateFilter !== '') {
        filters.push({ type: 'date', label: this.dateFilter });
      }
      if (this.tagFilter !== '') {
        filters.push({ type: 'tag', label: `Tag: ${this.tagFilter}` });
      }
      return filters;
    },

    clearFilter(type) {
      if (type === 'type' || type === 'all') this.typeFilter = 'all';
      if (type === 'ext' || type === 'all') this.extFilter = '';
      if (type === 'date' || type === 'all') this.dateFilter = '';
      if (type === 'tag' || type === 'all') this.tagFilter = '';

      this.search();
    },

    get gridClasses() {
      const colMap = {
        2: 'grid-cols-2',
        3: 'grid-cols-2 sm:grid-cols-3',
        4: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4',
        5: 'grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5',
        6: 'grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6',
        7: 'grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-7',
        8: 'grid-cols-3 sm:grid-cols-4 md:grid-cols-6 lg:grid-cols-8',
      };
      return colMap[this.gridCols] || colMap[5];
    },

    get isAllSelected() {
      return this.files.length > 0 && this.selectedIds.length === this.files.length;
    },

    /**
     * Initialize component.
     */
    init() {
      this.fetchFiles();
      this.$watch('detailModalOpen', (value) => {
        if (!value) {
          document.querySelectorAll('video, audio').forEach((el) => {
            if (!el.paused) el.pause();
          });
        }
      });
    },

    /**
     * Fetch files.
     * @param {boolean} append
     */
    async fetchFiles(append = false) {
      this.loading = true;
      try {
        const params = {
          limit: this.limit,
          keyword: this.keyword,
          sort: this.sort,
          type: this.typeFilter,
          ext: this.extFilter,
          date: this.dateFilter,
          tag: this.tagFilter,
          status: this.statusFilter,
        };
        const data = await GrindsMediaApi.list(this.page, params);
        if (data.success) {
          if (append) {
            this.files = [...this.files, ...data.files];
          } else {
            this.files = data.files;
            this.$nextTick(() => {
              // Use a more specific selector for the scroll container within the component
              const container = this.$el.querySelector('.overflow-y-auto');
              if (container) {
                container.scrollTop = 0;
              } else {
                // Fallback for the main media page view
                const mainContainer = document.querySelector('main');
                if (mainContainer) mainContainer.scrollTop = 0;
                else window.scrollTo({ top: 0, behavior: 'smooth' });
              }
            });
          }
          this.hasMore = data.has_more;
          this.total = data.total || 0;
        }
      } catch (e) {
        console.error(e);
      } finally {
        this.loading = false;
      }
    },

    /**
     * Search files.
     */
    search() {
      this.page = 1;
      this.selectedIds = [];
      let q = [...this.searchKeywords];
      if (this.searchInput.trim()) q.push(this.searchInput.trim());
      this.keyword = q.join(' ');
      this.fetchFiles();
    },

    /**
     * Change page.
     * @param {number} p
     */
    changePage(p) {
      this.selectedIds = [];
      this.page = p;
      this.fetchFiles();
    },

    /**
     * Toggle selection.
     * @param {number} id
     */
    toggleSelect(id) {
      if (this.selectedIds.includes(id)) {
        this.selectedIds = this.selectedIds.filter((i) => i !== id);
      } else {
        this.selectedIds.push(id);
      }
      this.lastSelectedId = id;
    },

    /**
     * Toggle select all.
     */
    toggleSelectAll() {
      this.selectedIds = this.isAllSelected ? [] : this.files.map((f) => f.id);
    },

    /**
     * Select range.
     * @param {number} index
     */
    selectRange(index) {
      if (this.lastSelectedId === null) return this.toggleSelect(this.files[index].id);
      const lastIdx = this.files.findIndex((f) => f.id === this.lastSelectedId);
      if (lastIdx === -1) return;
      const start = Math.min(lastIdx, index);
      const end = Math.max(lastIdx, index);
      const rangeIds = this.files.slice(start, end + 1).map((f) => f.id);
      rangeIds.forEach((id) => {
        if (!this.selectedIds.includes(id)) this.selectedIds.push(id);
      });
    },

    /**
     * Open detail modal.
     * @param {object} file
     */
    openDetail(file) {
      this.activeFile = file;
      this.syncForm(file);
      this.fetchFileTags(file.id);
      this.fetchFileUsage(file.id);
      this.detailModalOpen = true;
    },

    /**
     * Select previous file.
     */
    prevFile() {
      if (!this.activeFile) return;
      const idx = this.files.findIndex((f) => f.id === this.activeFile.id);
      if (idx === -1) return;
      const targetIdx = idx === 0 ? this.files.length - 1 : idx - 1;
      this.openDetail(this.files[targetIdx]);
    },

    /**
     * Select next file.
     */
    nextFile() {
      if (!this.activeFile) return;
      const idx = this.files.findIndex((f) => f.id === this.activeFile.id);
      if (idx === -1) return;
      const targetIdx = idx === this.files.length - 1 ? 0 : idx + 1;
      this.openDetail(this.files[targetIdx]);
    },

    /**
     * Sync form data.
     * @param {object} file
     */
    syncForm(file) {
      const meta = file.metadata || {};
      this.metaForm = {
        id: file.id,
        license: meta.license || 'unknown',
        license_url: meta.license_url || '',
        acquire_license_page: meta.acquire_license_page || '',
        tags: [],
        credit: meta.credit || '',
        source: meta.source || '',
        is_ai: !!meta.is_ai,
        prompt: meta.prompt || '',
        model: meta.model || '',
        alt: meta.alt || '',
        title: meta.title || '',
      };
    },

    /**
     * Fetch tag suggestions.
     */
    async fetchSuggestions() {
      if (this.allTags.length > 0) return;
      try {
        this.allTags = await GrindsMediaApi.getTags();
      } catch (e) {}
    },

    /**
     * Fetch file tags.
     * @param {number} id
     */
    async fetchFileTags(id) {
      try {
        const tags = await GrindsMediaApi.getTags(id);
        this.metaForm.tags = Array.isArray(tags) ? tags : [];
      } catch (e) {}
    },

    /**
     * Fetch file usage details.
     * @param {number} id
     */
    async fetchFileUsage(id) {
      this.isFetchingUsage = true;
      this.fileUsageList = [];
      try {
        const json = await GrindsMediaApi.getUsage(id);
        if (json.success && Array.isArray(json.usage)) {
          this.fileUsageList = json.usage;
        }
      } catch (e) {
      } finally {
        this.isFetchingUsage = false;
      }
    },

    /**
     * Add tag.
     * @param {string} tag
     */
    addTag(tag) {
      tag = tag || this.tagInput.trim();
      if (tag) {
        if (!Array.isArray(this.metaForm.tags)) this.metaForm.tags = [];
        if (!this.metaForm.tags.includes(tag)) this.metaForm.tags.push(tag);
        this.tagInput = '';
        this.showTagSuggestions = false;
      }
    },

    /**
     * Remove tag.
     * @param {number} index
     */
    removeTag(index) {
      this.metaForm.tags.splice(index, 1);
    },

    /**
     * Filter suggestions.
     */
    filterTagSuggestions() {
      const lower = this.tagInput.toLowerCase();
      this.tagSuggestions = this.allTags.filter(
        (t) => t.toLowerCase().includes(lower) && !this.metaForm.tags.includes(t)
      );
    },

    /**
     * Add search keyword.
     */
    addSearchKeyword() {
      const val = this.searchInput.trim();
      if (val) {
        this.searchKeywords.push(val);
        this.searchInput = '';
        this.search();
      }
    },

    /**
     * Remove search keyword.
     * @param {number} index
     */
    removeSearchKeyword(index) {
      this.searchKeywords.splice(index, 1);
      this.search();
    },

    /**
     * Save metadata.
     */
    async saveMetadata() {
      if (this.tagInput && this.tagInput.trim() !== '') {
        this.addTag();
      }

      if (!this.metaForm.id) return;

      const payload = { ...this.metaForm };
      delete payload.id;

      try {
        const data = await GrindsMediaApi.update(this.metaForm.id, payload, this.metaForm.tags, window.grindsCsrfToken);
        if (data.success) {
          const target = this.files.find((f) => f.id === this.metaForm.id);
          if (target) target.metadata = { ...target.metadata, ...payload };
          window.showToast(this.trans.saved, 'success');
          this.detailModalOpen = false;
        } else {
          window.showToast('Save failed: ' + (data.error || this.trans.error), 'error');
        }
      } catch (e) {
        window.showToast(this.trans.error, 'error');
      }
    },

    /**
     * Upload files.
     * @param {Event} e
     */
    async uploadFiles(e) {
      const inputFiles = e.target.files;
      if (!inputFiles.length) return;

      // Check max size
      this.isUploading = true;
      const preventUnload = (e) => {
        e.preventDefault();
        e.returnValue = '';
        return '';
      };
      window.addEventListener('beforeunload', preventUnload);

      const files = Array.from(inputFiles);
      const total = files.length;
      let current = 0;
      let successCount = 0;
      let errorMessages = [];

      for (const file of files) {
        current++;
        this.uploadProgress = `${current} / ${total}`;
        this.uploadProgressPercent = 0;
        try {
          const uploadedFile = await GrindsMediaHelpers.uploadFile(file, window.grindsCsrfToken, (percent) => {
            this.uploadProgressPercent = Math.round(((current - 1) / total) * 100 + percent / total);
          });
          if (uploadedFile) {
            this.files.unshift(uploadedFile);
            successCount++;
          }
        } catch (e) {
          if (e.message === 'SESSION_EXPIRED') {
            window.showToast('Session expired. Please reload the page and try again.', 'error');
            break;
          }
          errorMessages.push(`Error ${file.name}: ${e.message || 'Upload failed'}`);
          console.error('Upload error:', file.name, e);
        }
      }

      if (errorMessages.length > 0) {
        window.showToast(`Uploaded ${successCount} files. Errors: ` + errorMessages.join(', '), 'error');
      }

      this.isUploading = false;
      this.uploadProgress = '';
      this.uploadProgressPercent = 0;
      window.removeEventListener('beforeunload', preventUnload);
      e.target.value = '';
    },

    /**
     * Handle file drop.
     * @param {Event} e
     */
    handleDrop(e) {
      this.isDragging = false;
      const files = e.dataTransfer.files;
      if (files.length > 0) this.uploadFiles({ target: { files: files } });
    },

    /**
     * Copy URL.
     * @param {string} url
     */
    copyUrl(url) {
      navigator.clipboard.writeText(url).then(() => {
        window.showToast(this.trans.copied + ': ' + url, 'success');
      });
    },

    /**
     * Format file size.
     * @param {number} bytes
     */
    formatSize(bytes) {
      if (!bytes) return '-';
      const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB'];
      const i = Math.floor(Math.log(bytes) / Math.log(1024));
      return parseFloat((bytes / Math.pow(1024, i)).toFixed(1)) + ' ' + sizes[i];
    },

    /**
     * Delete selected files.
     * @param {boolean} force
     */
    async bulkDelete(force = false) {
      if (this.selectedIds.length === 0) return false;
      try {
        const data = await GrindsMediaHelpers.deleteWithConfirmation(
          this.selectedIds,
          window.grindsCsrfToken,
          this.trans
        );
        if (data && data.success) {
          this.selectedIds = [];
          if (data.skipped > 0) {
            let msg = this.trans.msg_delete_skipped || '%s deleted. %s skipped (in use).';
            msg = msg.replace('%s', data.deleted).replace('%s', data.skipped);
            window.showToast(msg, 'warning');
          }

          if (this.files.length <= data.deleted && this.page > 1) {
            this.page--;
          }

          this.fetchFiles();
          return true;
        }
      } catch (e) {
        window.showToast(this.trans.error, 'error');
      }
      return false;
    },

    /**
     * Delete file.
     * @param {object} file
     * @param {boolean} force
     */
    async deleteFile(file, force = false) {
      this.selectedIds = [file.id];
      if (await this.bulkDelete(force)) {
        this.detailModalOpen = false;
      }
    },
  }));
});
