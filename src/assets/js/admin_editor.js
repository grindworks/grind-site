/**
 * admin_editor.js
 * Implements block-based editor functionality using Alpine.js.
 */
document.addEventListener('alpine:init', () => {
  Alpine.data('blockEditor', (initialContent, options = {}) => ({
    blockLibrary: window.grindsBlockLibrary || {},
    seoTitle: options.seoTitle || '',
    seoDesc: options.seoDesc || '',
    seoImage: options.seoImage || '',
    siteDomain: options.siteDomain || window.location.hostname,
    mediaTargetContext: null,

    blocks: [],
    mediaModalOpen: false,
    draftRecoveryOpen: false,
    mediaFiles: [],
    mediaPage: 1,
    mediaLoading: false,
    mediaHasMore: false,
    activeMediaBlockIndex: null,
    activeMediaItemIndex: null,
    activeMediaKey: 'url',
    mediaKeyword: '',
    mediaSort: 'newest',
    recentImages: [],

    // Filter media modal state.
    mediaTypeFilter: 'all',

    templateModalOpen: false,
    tplTab: 'load',
    templates: [],
    newTemplateName: '',
    inserterOpen: false,
    blockSearchTerm: '',
    isSaving: false,
    isDirty: false,
    isSubmitting: false,
    isUploading: false,
    draftKey: '',
    isComposing: false,
    lastAutoSaved: null,
    draftTimeout: null,

    // History state
    history: [],
    future: [],
    lastState: null,
    lastCaret: null,
    isUndoing: false,
    historyTimeout: null,

    // Session handling state
    _sessionExpiryAlertShown: false,
    lastUserActivity: Date.now(),

    headingLevels: [
      { value: 'h2', label: window.grindsTranslations.blk_h2 || 'H2' },
      { value: 'h3', label: window.grindsTranslations.blk_h3 || 'H3' },
      { value: 'h4', label: window.grindsTranslations.blk_h4 || 'H4' },
      { value: 'h5', label: window.grindsTranslations.blk_h5 || 'H5' },
      { value: 'h6', label: window.grindsTranslations.blk_h6 || 'H6' },
    ],

    /**
     * Generate unique ID.
     * @returns {string}
     */
    generateId() {
      return Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
    },

    /**
     * Recursively clones an object and regenerates any 'id' properties.
     * @param {object} obj The object to clone and process.
     * @returns {object} The new object with regenerated IDs.
     */
    recursivelyRegenerateIds(obj) {
      if (obj === null || typeof obj !== 'object') {
        return obj;
      }

      if (Array.isArray(obj)) {
        return obj.map((item) => this.recursivelyRegenerateIds(item));
      }

      const newObj = {};
      for (const key in obj) {
        if (Object.prototype.hasOwnProperty.call(obj, key)) {
          newObj[key] =
            key === 'id' && (typeof obj[key] === 'string' || typeof obj[key] === 'number')
              ? this.generateId()
              : this.recursivelyRegenerateIds(obj[key]);
        }
      }
      return newObj;
    },

    /**
     * Encode string to Base64.
     * @param {string} str
     */
    base64Encode(str) {
      if (typeof TextEncoder !== 'undefined') {
        const bytes = new TextEncoder().encode(str);
        let binString = '';
        const chunkSize = 8192;
        for (let i = 0; i < bytes.length; i += chunkSize) {
          binString += String.fromCharCode.apply(null, bytes.subarray(i, i + chunkSize));
        }
        return btoa(binString);
      }
      return btoa(encodeURIComponent(str).replace(/%([0-9A-F]{2})/g, (match, p1) => String.fromCharCode('0x' + p1)));
    },

    /**
     * Escape HTML characters.
     * @param {string} str
     */
    escapeHtml(str) {
      if (!str) return '';
      return str
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    },

    /**
     * Refresh CSRF token.
     * @returns {Promise<boolean>}
     */
    async refreshCsrfToken() {
      try {
        const res = await fetch(this.getApiUrl('heartbeat.php'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.ok) {
          const data = await res.json();
          if (data.success && data.csrf_token) {
            if (window.grindsCsrfToken !== data.csrf_token) {
              window.grindsCsrfToken = data.csrf_token;
              document.querySelectorAll('input[name="csrf_token"]').forEach((el) => (el.value = data.csrf_token));
              if (window.grindsDebug) console.log('CSRF token refreshed');
            }
            return true;
          }
        }
      } catch (e) {
        if (window.grindsDebug) console.error('Token refresh failed', e);
      }
      return false;
    },

    /**
     * Handle paste event.
     * @param {ClipboardEvent} e
     */
    handlePaste(e) {
      if (!this.$root.contains(e.target)) return;

      // Ignore paste events on input and textarea fields
      if (['INPUT', 'TEXTAREA'].includes(e.target.tagName)) return;

      const clipboardData = e.clipboardData || window.clipboardData;
      const text = clipboardData.getData('text');
      if (!text) return;

      if (this.processMarkdownPaste(text)) {
        e.preventDefault();
      }
    },

    /**
     * Process text as Markdown paste.
     * Returns true if handled (converted to blocks), false otherwise.
     * @param {string} text
     * @returns {boolean}
     */
    processMarkdownPaste(text) {
      // Check for Canva URL
      if (text.includes('canva.com/design/')) {
        const canvaMatch = text.match(
          /(https?:\/\/(?:www\.)?canva\.com\/design\/[a-zA-Z0-9_-]+\/[a-zA-Z0-9_-]+\/view(?:[^\s]*))/
        );
        if (canvaMatch) {
          let msg = window.grindsTranslations?.confirm_embed_canva;
          if (!msg && window.grindsLang === 'ja') {
            msg = 'CanvaのURLを検出しました。埋め込みブロックに変換しますか？';
          }
          if (confirm(msg || 'Canva URL detected. Convert to Embed Block?')) {
            this.addBlock('embed');
            const newBlock = this.blocks[this.blocks.length - 1];
            if (newBlock) {
              newBlock.data.url = canvaMatch[0];
              newBlock.data.align = 'center';
            }
            this.$nextTick(() => {
              window.scrollTo(0, document.body.scrollHeight);
            });
            return true;
          }
        }
      } else if (text.includes('figma.com/')) {
        // Check for Figma URL
        const figmaMatch = text.match(
          /(https?:\/\/(?:www\.)?figma\.com\/(?:file|proto|design|board)\/[a-zA-Z0-9]+(?:[^\s]*))/
        );
        if (figmaMatch) {
          let msg = window.grindsTranslations?.confirm_embed_figma;
          if (!msg && window.grindsLang === 'ja') {
            msg = 'FigmaのURLを検出しました。埋め込みブロックに変換しますか？';
          }
          if (confirm(msg || 'Figma URL detected. Convert to Embed Block?')) {
            this.addBlock('embed');
            const newBlock = this.blocks[this.blocks.length - 1];
            if (newBlock) {
              newBlock.data.url = figmaMatch[0];
              newBlock.data.align = 'center';
            }
            this.$nextTick(() => {
              window.scrollTo(0, document.body.scrollHeight);
            });
            return true;
          }
        }
      }

      // Check for Markdown indicators (with multiline support)
      const isMarkdown =
        /^#+\s/m.test(text) ||
        /^\s*(\*|-|\d+\.)\s/m.test(text) ||
        /^>\s/m.test(text) ||
        /```/m.test(text) ||
        /^---/m.test(text) ||
        /!\[[^\]]*\]\([^)]*\)/m.test(text);

      if (
        isMarkdown &&
        confirm(window.grindsTranslations?.ai_paste_confirm || 'Markdown usage detected. Convert to blocks?')
      ) {
        const newBlocks = this.parseMarkdown(text);
        if (newBlocks.length > 0) {
          this.blocks.push(...newBlocks);
          this.$nextTick(() => {
            window.scrollTo(0, document.body.scrollHeight);
          });
          return true;
        }
      }
      return false;
    },

    /**
     * Parse Markdown to blocks.
     * @param {string} text
     */
    parseMarkdown(text) {
      const lines = text.replace(/\r\n/g, '\n').split('\n');
      const blocks = [];
      let buffer = [];

      // Helper to validate URL protocol to prevent Self-XSS
      const isSafeUrl = (urlStr) => {
        if (!urlStr) return false;
        const cleanStr = urlStr.trim();

        // Allow explicit relative/fragment paths
        if (/^[/.#?]/.test(cleanStr)) return true;

        try {
          const parsed = new URL(cleanStr);
          if (['http:', 'https:', 'mailto:', 'tel:'].includes(parsed.protocol)) {
            return true;
          }
          // Allow base64 inline images
          return parsed.protocol === 'data:' && parsed.pathname.startsWith('image/');
        } catch (e) {
          // Fallback for paths without explicit ./ or /
          const lower = cleanStr.toLowerCase().replace(/[\x00-\x1F\x7F\s\\]+/g, '');
          if (
            lower.startsWith('javascript:') ||
            lower.startsWith('vbscript:') ||
            (lower.startsWith('data:') && !lower.startsWith('data:image/'))
          ) {
            return false;
          }
          return true;
        }
      };

      // Helper to parse inline markdown
      const parseInline = (str) => {
        // Escape HTML tags to prevent XSS (allow <br> for line breaks)
        str = this.escapeHtml(str).replace(/&lt;br&gt;/g, '<br>');

        return str
          .replace(/\*\*(.*?)\*\*/g, '<b>$1</b>')
          .replace(/\*(.*?)\*/g, '<i>$1</i>')
          .replace(/~~(.*?)~~/g, '<s>$1</s>')
          .replace(/`(.*?)`/g, '<code>$1</code>')
          .replace(/!\[([^\]]*)\]\(([^)]*)\)/g, (match, alt, url) => {
            if (!isSafeUrl(url)) {
              return alt;
            }
            const safeUrl = url.trim().replace(/"/g, '&quot;');
            return `<img src="${safeUrl}" alt="${alt}" style="max-width:100%; height:auto;">`;
          })
          .replace(/(^|[^!])\[([^\]]*)\]\(([^)]*)\)/g, (match, prefix, text, url) => {
            if (!isSafeUrl(url)) {
              return prefix + text;
            }
            const safeUrl = url.trim().replace(/"/g, '&quot;');
            let targetAttr = '';
            if (/^https?:\/\//i.test(safeUrl)) {
              targetAttr = ' target="_blank" rel="noopener noreferrer"';
            }
            return `${prefix}<a href="${safeUrl}"${targetAttr}>${text}</a>`;
          });
      };

      const flushBuffer = () => {
        if (buffer.length > 0) {
          const content = buffer.join('<br>');
          if (content.trim()) {
            blocks.push({
              id: this.generateId(),
              type: 'paragraph',
              data: { text: parseInline(content) },
              collapsed: false,
            });
          }
          buffer = [];
        }
      };

      for (let i = 0; i < lines.length; i++) {
        const line = lines[i];
        const trimmed = line.trim();

        // 1. Code Blocks
        if (trimmed.startsWith('```')) {
          flushBuffer();
          // Find end of code block
          let codeContent = [];
          let language = trimmed.replace(/```/, '').trim();
          let j = i + 1;
          while (j < lines.length && !lines[j].trim().startsWith('```')) {
            codeContent.push(lines[j]);
            j++;
          }
          blocks.push({
            id: this.generateId(),
            type: 'code',
            data: { code: codeContent.join('\n'), language: language || 'plaintext' },
            collapsed: false,
          });
          i = j;
          continue;
        }

        // 2. Headers
        const headerMatch = line.match(/^(#{1,6})\s+(.*)/);
        if (headerMatch) {
          flushBuffer();
          blocks.push({
            id: this.generateId(),
            type: 'header',
            data: { level: 'h' + headerMatch[1].length, text: parseInline(headerMatch[2]) },
            collapsed: false,
          });
          continue;
        }

        // 3. Horizontal Rule
        if (trimmed === '---' || trimmed === '***') {
          flushBuffer();
          blocks.push({
            id: this.generateId(),
            type: 'divider',
            data: {},
            collapsed: false,
          });
          continue;
        }

        // 4. Blockquotes
        if (line.trim().startsWith('>')) {
          flushBuffer();
          let quoteContent = [];
          let j = i;
          while (j < lines.length && lines[j].trim().startsWith('>')) {
            quoteContent.push(lines[j].trim().replace(/^>\s*/, ''));
            j++;
          }
          blocks.push({
            id: this.generateId(),
            type: 'quote',
            data: { text: parseInline(quoteContent.join('\n')), cite: '' },
            collapsed: false,
          });
          i = j - 1;
          continue;
        }

        // 5. Images
        const imgMatch = trimmed.match(/^!\[([^\]]*)\]\(([^)]+)\)$/);
        if (imgMatch) {
          flushBuffer();
          let imgUrl = imgMatch[2].trim();
          if (!isSafeUrl(imgUrl)) {
            imgUrl = '';
          }

          const altText = this.escapeHtml(imgMatch[1] || '');

          blocks.push({
            id: this.generateId(),
            type: 'image',
            data: { url: imgUrl, alt: altText, caption: altText },
            collapsed: false,
          });
          continue;
        }

        // 6. Lists (Ordered & Unordered)
        const listMatch = line.match(/^\s*(\*|-|\d+\.)\s+(.*)/);
        if (listMatch) {
          flushBuffer();
          const isOrdered = /\d+\./.test(listMatch[1]);
          const listType = isOrdered ? 'ordered' : 'unordered';
          let listItems = [];
          let j = i;

          while (j < lines.length) {
            const currentMatch = lines[j].match(/^\s*(\*|-|\d+\.)\s+(.*)/);
            if (currentMatch) {
              listItems.push(parseInline(currentMatch[2]));
              j++;
            } else if (lines[j].trim() === '') {
              // Ignore empty lines within lists
              j++;
            } else {
              break;
            }
          }

          blocks.push({
            id: this.generateId(),
            type: 'list',
            data: { style: listType, items: listItems },
            collapsed: false,
          });
          i = j - 1;
          continue;
        }

        // 7. Tables
        const isTableLine = (l) => /\|.*\|/.test(l.trim());
        // Handles separators like |---|:---|---:|:---:|
        const isTableSeparator = (l) => {
          const trimmed = l.trim();
          return trimmed.includes('-') && /^[\s|:-]+$/.test(trimmed);
        };

        if (isTableLine(line) && i + 1 < lines.length && isTableSeparator(lines[i + 1])) {
          flushBuffer();
          const tableContent = [];
          let j = i;

          // Helper to parse a table row, including inline markdown in cells
          const parseRow = (rowLine) => {
            return rowLine
              .trim()
              .replace(/^\||\|$/g, '')
              .split(/(?<!\\)\|/)
              .map((cell) => parseInline(cell.trim().replace(/\\\|/g, '|')));
          };

          // Process header and subsequent rows
          while (j < lines.length && isTableLine(lines[j])) {
            // Skip the separator line itself
            if (isTableSeparator(lines[j])) {
              j++;
              continue;
            }
            tableContent.push(parseRow(lines[j]));
            j++;
          }

          if (tableContent.length > 0) {
            blocks.push({
              id: this.generateId(),
              type: 'table',
              data: { content: tableContent, withHeadings: true },
              collapsed: false,
            });
          }
          i = j - 1;
          continue;
        }

        // Empty lines flush paragraph
        if (trimmed === '') {
          flushBuffer();
          continue;
        }

        buffer.push(line);
      }
      flushBuffer();

      return blocks;
    },

    /**
     * Save draft to local storage.
     */
    saveLocalDraft() {
      clearTimeout(this.draftTimeout);
      const delay = window.grindsEditorDebounce ? parseInt(window.grindsEditorDebounce) : 1000;

      this.draftTimeout = setTimeout(() => {
        if (this.blocks.length > 0 || this.seoTitle || this.seoDesc) {
          const json = JSON.stringify({
            blocks: this.blocks,
            seoTitle: this.seoTitle,
            seoDesc: this.seoDesc,
          });
          try {
            localStorage.setItem(this.draftKey, json);
            localStorage.setItem(this.draftKey + '_time', Date.now());
          } catch (e) {
            console.warn('GrindSite: LocalStorage limit exceeded. Draft not saved.', e);
          }
        }
        const now = new Date();
        this.lastAutoSaved =
          now.getHours().toString().padStart(2, '0') + ':' + now.getMinutes().toString().padStart(2, '0');
      }, delay);
    },

    /**
     * Parse content into blocks structure.
     * @param {any} content
     */
    parseContent(content) {
      if (!content) return [];

      let parsed = null;
      if (typeof content === 'object') {
        parsed = content;
      }

      if (parsed && Array.isArray(parsed.blocks)) {
        return parsed.blocks.map((b) => {
          const block = {
            ...b,
            id: b.id || this.generateId(),
            collapsed: !!b.collapsed,
          };
          // header levels and price items default structure
          if (block.type === 'header' && !block.data.level) block.data.level = 'h2';
          if (block.type === 'price' && !block.data.items) block.data = { items: [{ ...block.data }] };
          return block;
        });
      }

      if (typeof content === 'string' && content.length > 0) {
        return [
          {
            id: this.generateId(),
            type: 'paragraph',
            data: { text: content },
            collapsed: false,
          },
        ];
      }

      return [];
    },

    /**
     * Initialize component.
     */
    init() {
      // Garbage collection for old drafts (older than 24h)
      const nowTime = Date.now();
      for (let i = localStorage.length - 1; i >= 0; i--) {
        const key = localStorage.key(i);
        if (key && key.startsWith('grinds_draft_') && !key.endsWith('_time')) {
          const timeStr = localStorage.getItem(key + '_time');
          if (timeStr && nowTime - parseInt(timeStr) > 24 * 60 * 60 * 1000) {
            localStorage.removeItem(key);
            localStorage.removeItem(key + '_time');
          }
        }
      }

      const urlParams = new URLSearchParams(window.location.search);
      const postId = urlParams.get('id') || 'new';
      const userId = window.grindsUserId || '0';

      if (postId === 'new') {
        let tabId = sessionStorage.getItem('grinds_tab_id');
        if (!tabId) {
          tabId = Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
          sessionStorage.setItem('grinds_tab_id', tabId);
        }
        this.draftKey = `grinds_draft_${userId}_new_${tabId}`;
      } else {
        this.draftKey = `grinds_draft_${userId}_${postId}`;
      }

      // Initialize blocks
      try {
        this.blocks = this.parseContent(initialContent);
      } catch (e) {
        if (window.grindsDebug) console.error('BlockEditor Init Error:', e);
        this.blocks = [];
      }

      this.checkLocalDraft();

      // Initialize history
      this.lastState = JSON.stringify(this.blocks);
      this.lastCaret = null;

      // Handle IME composition
      window.addEventListener('compositionstart', () => {
        this.isComposing = true;
      });
      window.addEventListener('compositionend', () => {
        // Delay the release of the composition flag to ensure DOM and data bindings are fully synced
        requestAnimationFrame(() => {
          this.isComposing = false;
          // Ensure final state is recorded
          if (!this.isSubmitting) {
            this.isDirty = true;
            this.saveLocalDraft();
            clearTimeout(this.historyTimeout);
            const delay = window.grindsEditorDebounce ? parseInt(window.grindsEditorDebounce) : 1000;
            this.historyTimeout = setTimeout(() => {
              this.recordHistory();
            }, delay);
          }
        });
      });

      // Watch blocks for changes
      this.$watch('blocks', (value) => {
        // Skip dirty flag during submission
        if (this.isSubmitting || this.isComposing) return;

        this.isDirty = true;

        // Save draft to local storage
        this.saveLocalDraft();

        // Record history with debounce
        if (!this.isUndoing) {
          clearTimeout(this.historyTimeout);
          const delay = window.grindsEditorDebounce ? parseInt(window.grindsEditorDebounce) : 1000;
          this.historyTimeout = setTimeout(() => {
            this.recordHistory();
          }, delay);
        }
      });

      // Watch SEO fields
      this.$watch('seoTitle', () => {
        if (!this.isSubmitting && !this.isComposing) {
          this.isDirty = true;
          this.saveLocalDraft();
        }
      });
      this.$watch('seoDesc', () => {
        if (!this.isSubmitting && !this.isComposing) {
          this.isDirty = true;
          this.saveLocalDraft();
        }
      });

      // Handle keyboard shortcuts
      window.addEventListener('keydown', (e) => {
        // Prevent accidental form submission on Enter in text inputs
        if (
          e.key === 'Enter' &&
          e.target.tagName === 'INPUT' &&
          !['submit', 'button', 'checkbox', 'radio', 'file'].includes(e.target.type)
        ) {
          e.preventDefault();
        }

        if ((e.metaKey || e.ctrlKey) && e.key === 's') {
          e.preventDefault();
          const form = document.getElementById('post-form');
          if (form && !this.isSubmitting && !this.isUploading) {
            form.requestSubmit();
          }
          return;
        }

        const isInput = e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.isContentEditable;

        if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !isInput) {
          e.preventDefault();
          if (e.shiftKey) {
            this.redo();
          } else {
            this.undo();
          }
        }
        if ((e.metaKey || e.ctrlKey) && e.key === 'y' && !isInput) {
          e.preventDefault();
          this.redo();
        }
      });

      // Handle paste
      window.addEventListener('paste', this.handlePaste.bind(this));

      // Prevent accidental navigation
      window.addEventListener('beforeunload', (e) => {
        if (this.isDirty && !this.isSubmitting && !window.grindsBypassUnload) {
          e.preventDefault();
          e.returnValue = '';
        }
      });

      // Handle form submission
      this.$nextTick(() => {
        const form = document.getElementById('post-form');
        if (form) {
          // Handle submit event
          form.addEventListener('submit', async (e) => {
            e.preventDefault();

            // Refresh CSRF token
            const tokenRefreshed = await this.refreshCsrfToken();
            if (!tokenRefreshed) {
              // Handle session expiry
              this.handleSessionExpiry();
              return;
            }

            // Check for sensitive content
            if (this.checkSensitiveContent()) {
              const msg = window.grindsTranslations.js_warn_script_tag || 'Warning: <script> tag detected.';
              if (!confirm(msg)) {
                return;
              }
            }

            // Check for absolute paths
            if (this.checkAbsolutePaths()) {
              const msg = window.grindsTranslations.js_warn_absolute_path || 'Warning: Absolute paths detected.';
              if (!confirm(msg)) {
                return;
              }
            }

            // Set submission flag
            this.isSubmitting = true;

            // Prepare JSON structure
            // Filter empty list items and remove empty blocks
            const cleanBlocks = this.blocks.reduce((acc, block) => {
              if (block.type === 'list' && Array.isArray(block.data.items)) {
                const cleanItems = block.data.items.filter((item) => typeof item === 'string' && item.trim() !== '');
                // Only keep list block if it has items
                if (cleanItems.length > 0) {
                  acc.push({
                    ...block,
                    data: { ...block.data, items: cleanItems },
                  });
                }
              } else {
                acc.push(block);
              }
              return acc;
            }, []);

            const contentStructure = { blocks: cleanBlocks };
            const contentJson = JSON.stringify(contentStructure);

            // Encode content to Base64
            const encodedContent = this.base64Encode(contentJson);

            // Set hidden input value
            // Find or create content input
            const contentInput =
              document.getElementById('grinds_content_input') || form.querySelector('input[name="content"]');

            if (contentInput) {
              contentInput.value = encodedContent;
            }

            // Set Base64 flag
            const base64Flag = form.querySelector('input[name="content_is_base64"]');
            if (base64Flag) {
              base64Flag.value = '1';
            }

            await this.submitViaAjax(form);
          });

          // Monitor input changes
          const otherInputs = form.querySelectorAll('input:not([type="hidden"]), textarea, select');
          otherInputs.forEach((el) => {
            if (el.name === 'content') return;

            const markDirty = () => {
              if (!this.isSubmitting) this.isDirty = true;
            };

            el.addEventListener('input', markDirty);
            el.addEventListener('change', markDirty);
          });
        }
      });

      // Start session heartbeat
      this.startHeartbeat();

      // Track user activity
      ['mousemove', 'keydown', 'click', 'scroll', 'touchstart'].forEach((evt) => {
        window.addEventListener(
          evt,
          () => {
            this.lastUserActivity = Date.now();
          },
          { passive: true }
        );
      });

      const toggleBodyScroll = (val) => {
        if (val) {
          const scrollbarWidth = window.innerWidth - document.documentElement.clientWidth;
          document.body.style.paddingRight = `${scrollbarWidth}px`;
          document.body.classList.add('overflow-hidden');
        } else {
          document.body.style.paddingRight = '';
          document.body.classList.remove('overflow-hidden');
        }
      };

      this.$watch('inserterOpen', toggleBodyScroll);
      this.$watch('mediaModalOpen', toggleBodyScroll);
      this.$watch('templateModalOpen', toggleBodyScroll);
    },

    /**
     * Start session heartbeat.
     */
    startHeartbeat() {
      // Send heartbeat every 5 minutes
      setInterval(
        () => {
          // Skip heartbeat if user is idle (allow session timeout)
          if (Date.now() - this.lastUserActivity > 5 * 60 * 1000) {
            return;
          }

          // Refresh session and token
          fetch(this.getApiUrl('heartbeat.php'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(async (response) => {
              if (response.status === 401 || response.status === 403) {
                this.handleSessionExpiry();
              } else if (response.ok) {
                const data = await response.json();
                if (data.success && data.csrf_token) {
                  if (window.grindsCsrfToken !== data.csrf_token) {
                    window.grindsCsrfToken = data.csrf_token;
                    document.querySelectorAll('input[name="csrf_token"]').forEach((el) => (el.value = data.csrf_token));
                  }
                }
              }
            })
            .catch((e) => {
              if (window.grindsDebug) console.error('Session heartbeat error:', e);
            });
        },
        5 * 60 * 1000
      );
    },

    /**
     * Handle session expiry.
     */
    handleSessionExpiry() {
      // Prevent alert stacking
      if (this._sessionExpiryAlertShown) return;
      this._sessionExpiryAlertShown = true;

      const msg = window.grindsTranslations.err_session_expired || 'Session expired. Please login again.';
      const loginUrl = (window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/login.php';
      const reLoginMsg =
        'Click OK to open the login page in a new window.\nAfter logging in, you can close the window and try saving again.';

      if (confirm(msg + '\n\n' + reLoginMsg)) {
        // Open login popup
        const win = window.open(loginUrl, '_blank', 'width=500,height=600,resizable=yes,scrollbars=yes');
        if (!win || win.closed || typeof win.closed === 'undefined') {
          alert(
            (window.grindsTranslations.err_popup_blocked || 'Popup blocked.') +
              '\n\n' +
              'Please right-click the dashboard link, open it in a new tab, log in, and then return here to save.'
          );
        }
        this.waitForRelogin();
      }

      // Reset alert flag
      setTimeout(() => {
        this._sessionExpiryAlertShown = false;
      }, 30000);
    },

    /**
     * Check for sensitive content like script tags.
     * @returns {boolean}
     */
    checkSensitiveContent() {
      for (const block of this.blocks) {
        if ((block.type === 'html' || block.type === 'map') && block.data.code) {
          if (block.data.code.toLowerCase().includes('<script')) {
            return true;
          }
        }
      }
      return false;
    },

    /**
     * Check for absolute paths in content.
     * @returns {boolean}
     */
    checkAbsolutePaths() {
      const baseUrl = (window.grindsBaseUrl || '').replace(/\/$/, '');

      for (const block of this.blocks) {
        if ((block.type === 'html' || block.type === 'map') && block.data.code) {
          // Check for full absolute URL
          if (baseUrl && baseUrl !== '/' && block.data.code.includes(baseUrl)) {
            return true;
          }
          // Check for root-relative paths
          if (/(href|src)=["']\/(?!\/)/i.test(block.data.code)) {
            return true;
          }
        }
      }
      return false;
    },

    /**
     * Wait for user to re-login.
     */
    waitForRelogin() {
      // Poll for session restoration
      const interval = setInterval(async () => {
        const success = await this.refreshCsrfToken();
        if (success) {
          if (window.grindsDebug) console.log('Session restored via heartbeat.');
          clearInterval(interval);
        }
      }, 3000);

      // Stop polling after 5 minutes
      setTimeout(() => clearInterval(interval), 300000);
    },

    /**
     * Check for local draft.
     */
    checkLocalDraft() {
      const localData = localStorage.getItem(this.draftKey);
      if (!localData) return;

      try {
        const parsed = JSON.parse(localData);
        if (parsed && (Array.isArray(parsed.blocks) || parsed.seoTitle || parsed.seoDesc)) {
          // Compare server data with draft
          const currentJson = JSON.stringify({
            blocks: this.blocks,
            seoTitle: this.seoTitle,
            seoDesc: this.seoDesc,
          });
          const draftJson = JSON.stringify({
            blocks: parsed.blocks || [],
            seoTitle: parsed.seoTitle || '',
            seoDesc: parsed.seoDesc || '',
          });

          if (currentJson === draftJson) {
            this.clearLocalDraft();
            return;
          }

          this.draftRecoveryOpen = true;
        }
      } catch (e) {
        if (window.grindsDebug) console.error('Draft restore error', e);
      }
    },

    /**
     * Restore draft from local storage.
     */
    restoreDraft() {
      const localData = localStorage.getItem(this.draftKey);
      if (!localData) return;
      try {
        const parsed = JSON.parse(localData);
        this.blocks = [];
        this.$nextTick(() => {
          this.blocks = parsed.blocks || [];
          if (parsed.seoTitle !== undefined) this.seoTitle = parsed.seoTitle;
          if (parsed.seoDesc !== undefined) this.seoDesc = parsed.seoDesc;
          this.isDirty = true;
          this.draftRecoveryOpen = false;
          // Translation applied
          window.showToast(window.grindsTranslations.draft_restored || 'Draft restored.', 'success');
        });
      } catch (e) {
        console.error(e);
      }
    },

    /**
     * Discard local draft.
     */
    discardDraft() {
      if (confirm(window.grindsTranslations.confirm_discard_draft || 'Discard draft?')) {
        this.clearLocalDraft();
        this.draftRecoveryOpen = false;
      }
    },

    /**
     * Record current state to history.
     */
    recordHistory() {
      if (this.isUndoing) return;

      const currentState = JSON.stringify(this.blocks);

      let currentCaret = null;
      const activeEl = document.activeElement;
      if (activeEl && (activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'INPUT') && activeEl.id) {
        currentCaret = {
          id: activeEl.id,
          start: activeEl.selectionStart,
          end: activeEl.selectionEnd,
        };
      }

      if (this.lastState && this.lastState !== currentState) {
        this.history.push({
          blocks: JSON.parse(this.lastState),
          caret: this.lastCaret,
        });
        if (this.history.length > 50) this.history.shift();
        this.future = [];
      }
      this.lastState = currentState;
      this.lastCaret = currentCaret;
    },

    /**
     * Undo last change.
     */
    undo() {
      if (this.history.length === 0) return;

      this.isUndoing = true;

      let currentCaret = null;
      const activeEl = document.activeElement;
      if (activeEl && (activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'INPUT') && activeEl.id) {
        currentCaret = {
          id: activeEl.id,
          start: activeEl.selectionStart,
          end: activeEl.selectionEnd,
        };
      }

      // Push current state to future
      this.future.push({
        blocks: JSON.parse(JSON.stringify(this.blocks)),
        caret: currentCaret,
      });

      // Restore previous state
      const prevState = this.history.pop();
      this.blocks = prevState.blocks;
      this.lastState = JSON.stringify(prevState.blocks);
      this.lastCaret = prevState.caret;

      this.$nextTick(() => {
        this.isUndoing = false;
        if (prevState.caret && prevState.caret.id) {
          const el = document.getElementById(prevState.caret.id);
          if (el) {
            el.focus();
            try {
              el.setSelectionRange(prevState.caret.start, prevState.caret.end);
            } catch (e) {}
          }
        } else if (activeEl && activeEl.id) {
          // Fallback
          const el = document.getElementById(activeEl.id);
          if (el) el.focus();
        }
      });
    },

    /**
     * Redo last undone change.
     */
    redo() {
      if (this.future.length === 0) return;

      this.isUndoing = true;

      let currentCaret = null;
      const activeEl = document.activeElement;
      if (activeEl && (activeEl.tagName === 'TEXTAREA' || activeEl.tagName === 'INPUT') && activeEl.id) {
        currentCaret = {
          id: activeEl.id,
          start: activeEl.selectionStart,
          end: activeEl.selectionEnd,
        };
      }

      // Push current state to history
      this.history.push({
        blocks: JSON.parse(JSON.stringify(this.blocks)),
        caret: currentCaret,
      });

      // Restore next state
      const nextState = this.future.pop();
      this.blocks = nextState.blocks;
      this.lastState = JSON.stringify(nextState.blocks);
      this.lastCaret = nextState.caret;

      this.$nextTick(() => {
        this.isUndoing = false;
        if (nextState.caret && nextState.caret.id) {
          const el = document.getElementById(nextState.caret.id);
          if (el) {
            el.focus();
            try {
              el.setSelectionRange(nextState.caret.start, nextState.caret.end);
            } catch (e) {}
          }
        } else if (activeEl && activeEl.id) {
          // Fallback
          const el = document.getElementById(activeEl.id);
          if (el) el.focus();
        }
      });
    },

    /**
     * Reset content to initial state.
     */
    resetContent() {
      if (
        !confirm(
          window.grindsTranslations.confirm_reset ||
            'Are you sure you want to reset all changes to the last saved state?'
        )
      )
        return;

      // Re-parse initialContent
      try {
        this.blocks = this.parseContent(initialContent);
        this.seoTitle = options.seoTitle || '';
        this.seoDesc = options.seoDesc || '';

        // Reset history
        this.history = [];
        this.future = [];
        this.lastState = JSON.stringify(this.blocks);
        this.lastCaret = null;
        this.isDirty = false;

        // Clear local draft
        this.clearLocalDraft();
      } catch (e) {
        if (window.grindsDebug) console.error('Reset error', e);
      }
    },

    /**
     * Clear local draft from storage.
     */
    clearLocalDraft() {
      localStorage.removeItem(this.draftKey);
      localStorage.removeItem(this.draftKey + '_time');
      this.isDirty = false;
    },

    /**
     * Open block inserter.
     */
    openInserter() {
      this.inserterOpen = true;
      this.blockSearchTerm = '';
      this.fetchRecentImages();
      this.$nextTick(() => {
        const input = this.$refs.blockSearch;
        if (input && window.innerWidth >= 768) {
          input.focus();
        }
      });
    },

    /**
     * Normalize URL.
     * @param {string} url
     */
    normalizeUrl(url) {
      if (!url) return '';

      // Normalize slashes
      url = url.replace(/\\/g, '/');

      const basePath = (window.grindsBasePath || '/').replace(/\/$/, '');
      const fullBaseUrl = (window.grindsBaseUrl || '').replace(/\/$/, '');

      // Remove domain
      if (fullBaseUrl && url.startsWith(fullBaseUrl)) {
        let relative = url.substring(fullBaseUrl.length);
        if (!relative.startsWith('/')) relative = '/' + relative;
        return relative;
      }

      // Remove base path
      if (url.startsWith('/') && !url.startsWith('//')) {
        if (basePath && basePath !== '/' && url.startsWith(basePath)) {
          return url.substring(basePath.length);
        }
      }

      return url;
    },

    /**
     * Resolve URL for preview.
     * @param {string} url
     */
    resolvePreviewUrl(url) {
      if (!url) return window.grindsPlaceholderImg || '';

      url = url.replace(/\\/g, '/');

      // Check for absolute or data URI
      if (/^(https?:|data:|blob:|\/\/)/.test(url)) {
        return url;
      }

      // Prepare base URL
      let baseUrl = (window.grindsBaseUrl || '').replace(/\/+$/, '');

      // Normalize input URL
      let cleanPath = url.startsWith('/') ? url : '/' + url;

      // Remove duplicate base path
      const basePath = (window.grindsBasePath || '').replace(/\/+$/, '');
      if (basePath && cleanPath.startsWith(basePath)) {
        cleanPath = cleanPath.substring(basePath.length);
      }

      return baseUrl + cleanPath;
    },

    /**
     * Get API URL.
     * @param {string} endpoint
     */
    getApiUrl(endpoint) {
      const base = (window.grindsBaseUrl || '').replace(/\/$/, '');
      return `${base}/admin/api/${endpoint}`;
    },

    /**
     * Save draft and open preview.
     */
    async saveDraftAndPreview() {
      if (this.isSaving) return;

      const form = document.getElementById('post-form');
      if (!form) return;

      // Open preview window
      const previewWindow = window.open('', '_blank');
      if (previewWindow) {
        // Translation applied
        previewWindow.document.write(window.grindsTranslations.js_preview_loading || 'Loading preview...');
        previewWindow.document.close();
      }

      // Refresh CSRF token
      const tokenValid = await this.refreshCsrfToken();
      if (!tokenValid) {
        if (previewWindow) previewWindow.close();
        this.handleSessionExpiry();
        return;
      }

      // Check for sensitive content
      if (this.checkSensitiveContent()) {
        const msg = window.grindsTranslations.js_warn_script_tag || 'Warning: <script> tag detected.';
        if (!confirm(msg)) {
          if (previewWindow) previewWindow.close();
          return;
        }
      }

      // Check for absolute paths
      if (this.checkAbsolutePaths()) {
        const msg = window.grindsTranslations.js_warn_absolute_path || 'Warning: Absolute paths detected.';
        if (!confirm(msg)) {
          if (previewWindow) previewWindow.close();
          return;
        }
      }

      this.isSaving = true;

      const contentJson = JSON.stringify({ blocks: this.blocks });

      // Encode content to Base64
      const encodedContent = this.base64Encode(contentJson);

      // Check content size
      if (encodedContent.length > 5 * 1024 * 1024) {
        const sizeMB = (encodedContent.length / 1024 / 1024).toFixed(2);
        if (!confirm(window.grindsTranslations.warn_post_too_large.replace('%s', sizeMB))) {
          this.isSaving = false;
          if (previewWindow) previewWindow.close();
          return;
        }
      }

      const formData = new FormData(form);
      formData.set('content', encodedContent);
      formData.set('content_is_base64', '1');
      formData.set('csrf_token', window.grindsCsrfToken);

      try {
        const response = await fetch(this.getApiUrl('preview.php'), {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData,
        });

        // Handle auth errors
        if (response.status === 401 || response.status === 403) {
          if (previewWindow) previewWindow.close();
          // Delay alert to ensure window closes visually
          setTimeout(() => this.handleSessionExpiry(), 100);
          return;
        }

        const result = await response.json();

        if (result.success && result.token) {
          const previewUrl = (window.grindsBaseUrl || '').replace(/\/$/, '') + '/?preview=' + result.token;
          if (previewWindow) {
            previewWindow.location.href = previewUrl;
          } else {
            window.location.href = previewUrl;
          }
        } else {
          if (previewWindow) previewWindow.close();
          // Translation applied
          const msg = window.grindsTranslations.js_preview_error || 'Preview Error: ';
          window.showToast(msg + (result.error || 'Unknown'), 'error');
        }
      } catch (e) {
        if (previewWindow) previewWindow.close();
        if (window.grindsDebug) console.error(e);
        // Alert already handled if it was session expiry
        if (!this._sessionExpiryAlertShown) {
          window.showToast(window.grindsTranslations.js_preview_net_err || 'Preview Failed: Network Error', 'error');
        }
      } finally {
        this.isSaving = false;
      }
    },

    /**
     * Submit form via AJAX (handles conflict).
     * @param {HTMLFormElement} form
     * @param {boolean} force
     */
    async submitViaAjax(form, force = false) {
      const formData = new FormData(form);
      formData.append('ajax_mode', '1');
      if (force) {
        formData.append('force_overwrite', '1');
      }

      try {
        const res = await fetch(form.action, {
          method: 'POST',
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
          body: formData,
        });

        if (res.status === 409) {
          const data = await res.json();
          const msg =
            window.grindsTranslations.err_conflict_confirm ||
            'Content has been modified by another user. Do you want to force overwrite?';
          if (confirm(msg)) {
            await this.submitViaAjax(form, true);
          } else {
            this.isSubmitting = false;
            this.isDirty = true;
          }
          return;
        }

        const data = await res.json();
        if (data.success) {
          this.isDirty = false;
          localStorage.removeItem(this.draftKey);
          localStorage.removeItem(this.draftKey + '_time');

          if (!form.querySelector('input[name="id"]')?.value) {
            const base = (window.grindsBaseUrl || '').replace(/\/$/, '');
            window.location.href = `${base}/admin/posts.php?action=edit&id=${data.id}&saved=1`;
          } else {
            this.isSubmitting = false;

            if (data.version) {
              const vInput = form.querySelector('input[name="original_version"]');
              if (vInput) vInput.value = data.version;
            }
            if (data.updated_at) {
              const uInput = form.querySelector('input[name="original_updated_at"]');
              if (uInput) uInput.value = data.updated_at;
            }

            const msg = data.message || 'Saved successfully';
            if (typeof window.showToast === 'function') {
              window.showToast(msg, 'success');
            } else {
              alert(msg);
            }
          }
        } else {
          window.showToast(data.error || 'Error', 'error');
          this.isSubmitting = false;
          this.isDirty = true;
        }
      } catch (e) {
        console.error(e);
        window.showToast('Network Error', 'error');
        this.isSubmitting = false;
        this.isDirty = true;
      }
    },

    /**
     * Add new block.
     * @param {string} type
     */
    addBlock(type) {
      if (type === 'template') {
        this.openTemplateModal();
        return;
      }

      let blockDef = null;
      for (const catKey in window.grindsBlockLibrary) {
        const cat = window.grindsBlockLibrary[catKey];
        if (cat.items && cat.items[type]) {
          blockDef = cat.items[type];
          break;
        }
      }

      const defaultData = blockDef && blockDef.default ? JSON.parse(JSON.stringify(blockDef.default)) : {};

      const newBlock = {
        id: this.generateId(),
        type: type,
        data: defaultData,
        collapsed: false,
      };

      this.blocks.push(newBlock);

      this.$nextTick(() => {
        const newBlockEl = document.getElementById('block-wrapper-' + newBlock.id);
        if (newBlockEl) {
          newBlockEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
          const firstInput = newBlockEl.querySelector('textarea, input[type="text"]');
          if (firstInput && window.innerWidth >= 768) {
            setTimeout(() => firstInput.focus(), 300);
          }
        } else {
          window.scrollTo({ top: document.body.scrollHeight, behavior: 'smooth' });
        }
      });
    },

    /**
     * Add image block.
     * @param {object} img
     */
    addImageBlock(img) {
      this.addBlock('image');
      const newBlock = this.blocks[this.blocks.length - 1];
      if (newBlock) {
        if (!newBlock.data) newBlock.data = {};
        newBlock.data.url = img.url;
        const meta = img.metadata || {};
        newBlock.data.alt = meta.alt || '';
        newBlock.data.caption = meta.caption || '';
        newBlock.data.id = img.id;
      }
      this.inserterOpen = false;
      this.blockSearchTerm = '';
    },

    /**
     * Duplicate block.
     * @param {number} index
     */
    duplicateBlock(index) {
      const originalBlock = this.blocks[index];
      const newBlock = this.recursivelyRegenerateIds(originalBlock);
      this.blocks.splice(index + 1, 0, newBlock);
    },

    /**
     * Remove block.
     * @param {number} index
     */
    removeBlock(index) {
      const block = this.blocks[index];
      const d = block.data || {};

      let isEmpty = true;

      switch (block.type) {
        case 'paragraph':
        case 'header':
        case 'html':
        case 'quote':
        case 'callout':
        case 'section':
          isEmpty = !d.text || String(d.text).trim().length === 0;
          break;
        case 'image':
        case 'video':
        case 'audio':
        case 'pdf':
        case 'download':
        case 'embed':
        case 'card':
          isEmpty = !d.url || String(d.url).trim().length === 0;
          break;
        case 'list':
        case 'accordion':
          isEmpty =
            !d.items ||
            d.items.length === 0 ||
            (d.items.length === 1 &&
              (!d.items[0] || (typeof d.items[0] === 'string' ? d.items[0].trim() === '' : !d.items[0].title)));
          break;
        case 'code':
        case 'math':
          isEmpty = !d.code || String(d.code).trim().length === 0;
          break;
        default:
          const dataString = JSON.stringify(d);
          isEmpty = dataString === '{}' || dataString.length < 20;
          break;
      }

      if (isEmpty || confirm(window.grindsTranslations.confirm_delete || 'Are you sure?')) {
        this.blocks.splice(index, 1);
      }
    },

    /**
     * Move block up or down.
     * @param {number} index
     * @param {number} dir
     */
    moveBlock(index, dir) {
      const targetIndex = index + dir;
      if (targetIndex >= 0 && targetIndex < this.blocks.length) {
        this.moveBlockTo(index, targetIndex, true);
      }
    },

    /**
     * Move block to specific position.
     * @param {number} fromIndex
     * @param {number} toIndex
     * @param {boolean} shouldScroll
     */
    moveBlockTo(fromIndex, toIndex, shouldScroll = true) {
      if (isNaN(toIndex)) return;
      if (toIndex < 0) toIndex = 0;
      if (toIndex >= this.blocks.length) toIndex = this.blocks.length - 1;
      if (fromIndex === toIndex) return;
      const item = this.blocks.splice(fromIndex, 1)[0];
      this.blocks.splice(toIndex, 0, item);

      if (shouldScroll) {
        this.$nextTick(() => {
          const el = document.getElementById('block-wrapper-' + item.id);
          if (el) {
            el.scrollIntoView({ behavior: 'smooth', block: 'center' });
          }
        });
      }
    },

    /**
     * Toggle block collapse state.
     * @param {number} index
     */
    toggleCollapse(index) {
      const block = this.blocks[index];
      if (!block) return;

      block.collapsed = !block.collapsed;

      // If expanding, recalculate textarea heights to fix collapse bug
      if (!block.collapsed) {
        this.$nextTick(() => {
          const blockEl = document.getElementById('block-wrapper-' + block.id);
          if (blockEl) {
            const textareas = blockEl.querySelectorAll('textarea');
            textareas.forEach((ta) => {
              // Only target textareas with auto-height behavior (identified by overflow:hidden)
              if (ta.style.overflow === 'hidden') {
                ta.style.height = 'auto';
                ta.style.height = ta.scrollHeight + 'px';
              }
            });
          }
        });
      }
    },

    /**
     * Collapse all blocks.
     */
    collapseAll() {
      this.blocks.forEach((b) => (b.collapsed = true));
    },

    /**
     * Expand all blocks.
     */
    expandAll() {
      this.blocks.forEach((b) => (b.collapsed = false));
    },

    /**
     * Get block label.
     * @param {string} type
     */
    getBlockLabel(type) {
      let label = null;
      if (window.grindsBlockLibrary) {
        for (const catKey in window.grindsBlockLibrary) {
          const cat = window.grindsBlockLibrary[catKey];
          if (cat.items && cat.items[type]) {
            label = cat.items[type].label;
            break;
          }
        }
      }
      return label || type.charAt(0).toUpperCase() + type.slice(1);
    },

    /**
     * Get block summary text.
     * @param {object} block
     */
    getBlockSummary(block) {
      const d = block.data;
      let text = '';

      if (d.text) {
        // Strip HTML tags to prevent XSS in summary view
        text = d.text
          .replace(/<[^>]*>?/gm, ' ')
          .replace(/\s+/g, ' ')
          .trim();
      } else if (d.title) {
        text = d.title;
      } else if (d.caption) {
        text = d.caption;
      } else if (d.code) {
        return window.grindsTranslations.js_code_snippet || 'Code snippet';
      } else if (d.images && Array.isArray(d.images) && d.images.length > 0) {
        const count = d.images.length;
        const fmt = window.grindsTranslations.js_images_count || '%d images';
        return fmt.replace('%d', count);
      }

      return text ? text.substring(0, 50) + (text.length > 50 ? '...' : '') : '';
    },

    /**
     * Insert HTML tag into block text.
     * @param {number} blockIndex
     * @param {string} tag
     */
    insertTag(blockIndex, tag) {
      const blockId = this.blocks[blockIndex].id;
      const textarea = document.getElementById('block-' + blockId + '-text');
      if (!textarea) return;

      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      const text = textarea.value;

      let open = `<${tag}>`;
      let close = `</${tag}>`;
      let selectedText = text.substring(start, end);

      const stripRegex = new RegExp(`<\/?${tag}>`, 'gi');
      selectedText = selectedText.replace(stripRegex, '');

      const replacement = open + selectedText + close;

      this.blocks[blockIndex].data.text = text.substring(0, start) + replacement + text.substring(end);

      this.$nextTick(() => {
        textarea.focus();
        textarea.setSelectionRange(start + open.length, end + open.length);
      });
    },

    /**
     * Show a custom prompt dialog to replace native prompt().
     * @param {string} message
     * @param {string} defaultValue
     * @returns {Promise<string|null>}
     */
    customPrompt(message, defaultValue = '') {
      return new Promise((resolve) => {
        const overlay = document.createElement('div');
        overlay.className =
          'fixed inset-0 z-[200] flex items-center justify-center p-4 bg-black/50 backdrop-blur-sm transition-opacity opacity-0';

        const dialog = document.createElement('div');
        dialog.className =
          'bg-theme-surface shadow-theme border border-theme-border rounded-theme p-6 w-full max-w-md transform scale-95 opacity-0 transition-all duration-200';

        const title = document.createElement('h3');
        title.className = 'font-bold text-theme-text mb-4 text-lg';
        title.textContent = message;

        const input = document.createElement('input');
        input.type = 'text';
        input.className =
          'w-full form-control mb-6 text-sm bg-theme-bg text-theme-text border-theme-border focus:border-theme-primary focus:ring-theme-primary';
        input.value = defaultValue;
        input.placeholder = 'https://...';

        const btnContainer = document.createElement('div');
        btnContainer.className = 'flex justify-end gap-3';

        const btnCancel = document.createElement('button');
        btnCancel.type = 'button';
        btnCancel.className = 'px-4 py-2 rounded-theme text-sm btn-secondary';
        btnCancel.textContent = window.grindsTranslations?.cancel || 'Cancel';

        const btnOk = document.createElement('button');
        btnOk.type = 'button';
        btnOk.className = 'px-4 py-2 rounded-theme font-bold text-sm btn-primary shadow-theme flex items-center gap-2';
        btnOk.innerHTML =
          '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg> OK';

        btnContainer.appendChild(btnCancel);
        btnContainer.appendChild(btnOk);

        dialog.appendChild(title);
        dialog.appendChild(input);
        dialog.appendChild(btnContainer);
        overlay.appendChild(dialog);
        document.body.appendChild(overlay);

        requestAnimationFrame(() => {
          overlay.classList.remove('opacity-0');
          dialog.classList.remove('scale-95', 'opacity-0');
          dialog.classList.add('scale-100', 'opacity-100');
        });

        setTimeout(() => {
          input.focus();
          input.select();
        }, 100);

        const cleanup = () => {
          dialog.classList.remove('scale-100', 'opacity-100');
          dialog.classList.add('scale-95', 'opacity-0');
          overlay.classList.add('opacity-0');
          setTimeout(() => overlay.remove(), 200);
        };

        btnCancel.addEventListener('click', () => {
          cleanup();
          resolve(null);
        });
        btnOk.addEventListener('click', () => {
          cleanup();
          resolve(input.value);
        });

        input.addEventListener('keydown', (e) => {
          if (e.key === 'Enter') {
            e.preventDefault();
            btnOk.click();
          } else if (e.key === 'Escape') {
            e.preventDefault();
            btnCancel.click();
          }
        });
      });
    },

    /**
     * Insert link into block text.
     * @param {number} blockIndex
     */
    async insertLink(blockIndex) {
      const blockId = this.blocks[blockIndex].id;
      const textarea = document.getElementById('block-' + blockId + '-text');
      if (!textarea) return;

      const start = textarea.selectionStart;
      const end = textarea.selectionEnd;
      const text = textarea.value;
      const selectedText = text.substring(start, end) || 'link';

      const url = await this.customPrompt(window.grindsTranslations.ph_link_url || 'Enter Link URL:');
      if (url === null || url.trim() === '') return;

      let finalUrl = this.normalizeUrl(url);

      const lowerUrl = finalUrl.toLowerCase().trim();
      if (lowerUrl.startsWith('javascript:') || lowerUrl.startsWith('vbscript:') || lowerUrl.startsWith('data:')) {
        return;
      }

      // Escape double quotes to prevent attribute breakout
      finalUrl = finalUrl.replace(/"/g, '&quot;');

      // Set target and rel attributes
      let targetAttr = '';
      if (/^https?:\/\//i.test(finalUrl)) {
        targetAttr = ' target="_blank" rel="noopener noreferrer"';
      }

      selectedText = selectedText.replace(/<\/?a[^>]*>/gi, '');

      const replacement = `<a href="${finalUrl}"${targetAttr}>${selectedText}</a>`;

      this.blocks[blockIndex].data.text = text.substring(0, start) + replacement + text.substring(end);

      this.$nextTick(() => {
        textarea.focus();
      });
    },

    /**
     * Add item to list block.
     * @param {number} blockIndex
     * @param {number|null} insertAt
     */
    addListItem(blockIndex, insertAt = null) {
      const block = this.blocks[blockIndex];
      if (!block.data.items) block.data.items = [];

      if (insertAt !== null) {
        block.data.items.splice(insertAt, 0, '');
      } else {
        block.data.items.push('');
      }
    },

    /**
     * Remove item from list block.
     * @param {number} blockIndex
     * @param {number} itemIndex
     */
    removeListItem(blockIndex, itemIndex) {
      const block = this.blocks[blockIndex];
      if (block.data.items && block.data.items.length > itemIndex) {
        block.data.items.splice(itemIndex, 1);
      }
    },

    /**
     * Add row to table block.
     * @param {number} blockIndex
     */
    addTableRow(blockIndex) {
      const block = this.blocks[blockIndex];
      const cols = block.data.content[0].length;
      const newRow = new Array(cols).fill('');
      block.data.content.push(newRow);
    },

    /**
     * Add column to table block.
     * @param {number} blockIndex
     */
    addTableCol(blockIndex) {
      const block = this.blocks[blockIndex];
      block.data.content.forEach((row) => row.push(''));
    },

    /**
     * Remove row from table block.
     * @param {number} blockIndex
     */
    removeTableRow(blockIndex) {
      const block = this.blocks[blockIndex];
      if (block.data.content.length > 1) {
        block.data.content.pop();
      }
    },

    /**
     * Remove column from table block.
     * @param {number} blockIndex
     */
    removeTableCol(blockIndex) {
      const block = this.blocks[blockIndex];
      if (block.data.content[0].length > 1) {
        block.data.content.forEach((row) => row.pop());
      }
    },

    /**
     * Open media library modal.
     * @param {number} index
     * @param {number|null} itemIndex
     * @param {string} key
     */
    openMediaLibrary(index, itemIndex = null, key = 'url') {
      this.activeMediaBlockIndex = index;
      this.activeMediaItemIndex = itemIndex;
      this.activeMediaKey = key;

      // Switch filter based on context
      const blockType = this.blocks[index].type;
      if (key === 'url') {
        if (blockType === 'image') {
          this.mediaTypeFilter = 'image';
        } else if (blockType === 'audio') {
          this.mediaTypeFilter = 'audio';
        } else {
          this.mediaTypeFilter = 'document';
        }
      } else {
        this.mediaTypeFilter = 'all';
      }

      this.mediaModalOpen = true;
      this.mediaKeyword = '';
      this.loadMedia(1);
    },

    /**
     * Search media files.
     */
    searchMedia() {
      this.loadMedia(1);
    },

    /**
     * Load media files from API.
     * @param {number} page
     */
    async loadMedia(page = 1) {
      if (this.mediaLoading && page > 1) return;
      this.mediaLoading = true;

      try {
        const params = {
          keyword: this.mediaKeyword,
          sort: this.mediaSort,
          type: this.mediaTypeFilter,
        };

        const data = await GrindsMediaApi.list(page, params);
        if (data.success) {
          this.mediaFiles = data.files;
          this.mediaPage = page;
          this.mediaHasMore = data.has_more;
          const container = document.querySelector('.overflow-y-auto');
          if (container) container.scrollTop = 0;
        }
      } catch (e) {
        if (e.message === 'SESSION_EXPIRED') this.handleSessionExpiry();
        if (window.grindsDebug) console.error(e);
      } finally {
        this.mediaLoading = false;
      }
    },

    /**
     * Fetch recent images.
     */
    async fetchRecentImages() {
      try {
        const data = await GrindsMediaApi.list(1, { sort: 'newest', type: 'image' });
        if (data.success) {
          this.recentImages = data.files.slice(0, 8);
        }
      } catch (e) {
        if (window.grindsDebug) console.error(e);
      }
    },

    /**
     * Open media library for a specific context.
     * @param {string} context
     */
    openLibrary(context) {
      this.mediaModalOpen = true;
      this.mediaTargetContext = context;
      this.loadMedia(1);
    },

    /**
     * Select media file.
     * @param {object} file
     */
    selectMedia(file) {
      if (this.mediaTargetContext === 'hero_image') {
        window.dispatchEvent(new CustomEvent('set-hero-image', { detail: { url: file.url, mobile: false } }));
        this.mediaModalOpen = false;
        this.mediaTargetContext = null;
      } else if (this.mediaTargetContext === 'hero_image_mobile') {
        window.dispatchEvent(new CustomEvent('set-hero-image', { detail: { url: file.url, mobile: true } }));
        this.mediaModalOpen = false;
        this.mediaTargetContext = null;
      } else if (this.activeMediaBlockIndex !== null) {
        const block = this.blocks[this.activeMediaBlockIndex];
        const targetArray = block.data.images || block.data.items;

        if (this.activeMediaItemIndex === 'add' && targetArray) {
          // Add item to gallery
          const meta = file.metadata || {};
          targetArray.push({
            url: file.url,
            caption: meta.caption || '',
          });
        } else if (this.activeMediaItemIndex !== null && targetArray && targetArray[this.activeMediaItemIndex]) {
          // Update existing item
          if (targetArray[this.activeMediaItemIndex]) {
            targetArray[this.activeMediaItemIndex][this.activeMediaKey] = file.url;
          }
        } else {
          // Update block property
          block.data[this.activeMediaKey] = file.url;

          // Auto-fill file size
          if (block.type === 'download' && file.size) {
            const size =
              file.size < 1024 * 1024
                ? (file.size / 1024).toFixed(1) + ' KB'
                : (file.size / (1024 * 1024)).toFixed(1) + ' MB';
            block.data.fileSize = size;
          }

          // Auto-fill title for audio/pdf/download
          if (['audio', 'pdf', 'download'].includes(block.type) && !block.data.title) {
            const meta = file.metadata || {};
            block.data.title = meta.original_name || file.filename || '';
          }

          // Auto-fill metadata
          if (block.type === 'image') {
            const meta = file.metadata || {};
            if (meta.alt) block.data.alt = meta.alt;
            if (meta.caption) block.data.caption = meta.caption;
          }
        }
      }
      this.mediaModalOpen = false;
    },

    /**
     * Delete media file.
     * @param {object} file
     * @param {number} index
     * @param {boolean} force
     */
    async deleteMedia(file, index, force = false) {
      try {
        const data = await GrindsMediaHelpers.deleteWithConfirmation(
          file.id,
          window.grindsCsrfToken,
          window.grindsTranslations
        );
        if (data && data.success) {
          this.mediaFiles.splice(index, 1);
          if (this.mediaFiles.length === 0 && this.mediaPage > 1) {
            this.mediaPage--;
            this.loadMedia(this.mediaPage);
          }
        }
      } catch (e) {
        if (e.message === 'SESSION_EXPIRED') {
          this.handleSessionExpiry();
          return;
        }
        if (!this._sessionExpiryAlertShown) window.showToast('Delete error occurred.', 'error');
      }
    },

    /**
     * Upload files to library.
     * @param {Event} event
     */
    async uploadToLibrary(event) {
      const files = event.target.files;
      if (!files || files.length === 0) return;

      this.isUploading = true;

      try {
        let successCount = 0;

        for (let i = 0; i < files.length; i++) {
          try {
            const result = await GrindsMediaHelpers.uploadFile(files[i], window.grindsCsrfToken);
            if (result) successCount++;
          } catch (e) {
            if (e.message === 'SESSION_EXPIRED') {
              this.handleSessionExpiry();
              break;
            }
            if (window.grindsDebug) console.error(e);
          }
        }

        if (successCount > 0) {
          this.mediaPage = 1;
          this.loadMedia(1);
        }
      } finally {
        this.isUploading = false;
        event.target.value = '';
      }
    },

    /**
     * Upload image for specific block.
     * @param {Event} event
     * @param {number} index
     * @param {string} key
     */
    async uploadImage(event, index, key = 'url') {
      const file = event.target.files[0];
      if (!file) return;

      this.isUploading = true;

      try {
        const uploadedFile = await GrindsMediaHelpers.uploadFile(file, window.grindsCsrfToken);
        if (uploadedFile) {
          this.blocks[index].data[key] = uploadedFile.url;

          if (this.blocks[index].type === 'download' && uploadedFile.size) {
            const size =
              uploadedFile.size < 1024 * 1024
                ? (uploadedFile.size / 1024).toFixed(1) + ' KB'
                : (uploadedFile.size / (1024 * 1024)).toFixed(1) + ' MB';
            this.blocks[index].data.fileSize = size;
          }

          // Auto-fill title for audio/pdf/download
          if (['audio', 'pdf', 'download'].includes(this.blocks[index].type) && !this.blocks[index].data.title) {
            const meta = uploadedFile.metadata || {};
            this.blocks[index].data.title = meta.original_name || uploadedFile.filename || '';
          }

          // Auto-fill metadata
          if (this.blocks[index].type === 'image') {
            const meta = uploadedFile.metadata || {};
            if (meta.alt) this.blocks[index].data.alt = meta.alt;
            if (meta.caption) this.blocks[index].data.caption = meta.caption;
          }
        }
      } catch (e) {
        if (e.message === 'SESSION_EXPIRED') {
          this.handleSessionExpiry();
        } else if (window.grindsDebug) {
          console.error('Upload error:', e);
        }
      } finally {
        this.isUploading = false;
        event.target.value = '';
      }
    },

    /**
     * Upload images for gallery block.
     * @param {Event} event
     * @param {number} blockIndex
     */
    async uploadGalleryImages(event, blockIndex) {
      const inputFiles = event.target.files;
      if (!inputFiles || inputFiles.length === 0) return;

      this.isUploading = true;

      try {
        const files = Array.from(inputFiles);

        // Upload files in parallel but wait for all to complete to maintain order.
        const uploadPromises = files.map((file) =>
          GrindsMediaHelpers.uploadFile(file, window.grindsCsrfToken).catch((error) => {
            if (error.message === 'SESSION_EXPIRED') {
              this.handleSessionExpiry();
            }
            // Return an object to identify the error source
            return { error: true, message: error.message || 'Upload failed', name: file.name };
          })
        );

        const results = await Promise.all(uploadPromises);

        const successfulUploads = [];
        const errorMessages = [];

        results.forEach((result) => {
          if (result && !result.error) {
            const meta = result.metadata || {};
            successfulUploads.push({
              url: result.url,
              caption: meta.caption || '',
            });
          } else if (result && result.error) {
            errorMessages.push(`Error ${result.name}: ${result.message}`);
          }
        });

        if (successfulUploads.length > 0) {
          // Push all at once to maintain order and improve reactivity performance.
          this.blocks[blockIndex].data.images.push(...successfulUploads);
        }

        if (errorMessages.length > 0) {
          window.showToast(`Uploaded ${successfulUploads.length} files. Errors: ` + errorMessages.join(', '), 'error');
        }
      } finally {
        this.isUploading = false;
        event.target.value = '';
      }
    },

    /**
     * Fetch metadata for URL.
     * @param {number} index
     */
    async fetchMeta(index) {
      const block = this.blocks[index];
      const url = block.data.url;
      if (!url) return;

      const originalTitle = block.data.title;
      // Translation applied
      block.data.title = window.grindsTranslations.loading || 'Loading...';

      try {
        const apiUrl = `${this.getApiUrl('meta_fetch.php')}?url=${encodeURIComponent(url)}`;
        const res = await fetch(apiUrl, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.status === 401) {
          this.handleSessionExpiry();
          throw new Error('Session Expired');
        }
        const json = await res.json();

        if (json.success) {
          block.data.title = json.data.title;
          block.data.description = json.data.description;
          block.data.image = this.normalizeUrl(json.data.image);
        } else {
          // Translation applied
          window.showToast(window.grindsTranslations.fetch_failed || 'Fetch failed', 'error');
          block.data.title = originalTitle || '';
        }
      } catch (e) {
        if (window.grindsDebug) console.error(e);
        block.data.title = originalTitle || '';
      }
    },

    /**
     * Open template modal.
     */
    openTemplateModal() {
      this.templateModalOpen = true;
      this.fetchTemplates();
      const titleInput = document.querySelector('input[name="title"]');
      this.newTemplateName = titleInput ? titleInput.value : '';
    },

    /**
     * Fetch templates from API.
     */
    async fetchTemplates() {
      try {
        const res = await fetch(this.getApiUrl('templates.php'), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
        if (res.status === 401) {
          this.handleSessionExpiry();
          throw new Error('Session Expired');
        }
        const data = await res.json();
        if (data.success) {
          this.templates = data.list;
        }
      } catch (e) {
        if (window.grindsDebug) console.error(e);
      }
    },

    /**
     * Load template content.
     * @param {string} id
     */
    async loadTemplate(id) {
      if (!confirm(window.grindsTranslations.tpl_confirm_load)) return;
      try {
        const res = await fetch(`${this.getApiUrl('templates.php')}?id=${id}`, {
          headers: { 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (res.status === 401) {
          this.handleSessionExpiry();
          throw new Error('Session Expired');
        }
        const data = await res.json();
        if (data.success && data.data.content) {
          let content = JSON.parse(data.data.content);
          const newBlocks = content.blocks.map((b) => ({
            ...b,
            id: this.generateId(),
            collapsed: false,
          }));
          this.blocks = [...this.blocks, ...newBlocks];
          this.templateModalOpen = false;
        }
      } catch (e) {
        if (window.grindsDebug) console.error(e);
      }
    },

    /**
     * Save current content as template.
     */
    async saveCurrentAsTemplate() {
      if (!this.newTemplateName) return;

      const tokenValid = await this.refreshCsrfToken();
      if (!tokenValid) {
        this.handleSessionExpiry();
        return;
      }

      const payload = {
        title: this.newTemplateName,
        content: this.base64Encode(
          JSON.stringify({
            blocks: this.blocks,
          })
        ),
        content_is_base64: 1,
        csrf_token: window.grindsCsrfToken,
      };
      try {
        const res = await fetch(this.getApiUrl('templates.php'), {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
          },
          body: JSON.stringify(payload),
        });

        if (res.status === 401) {
          this.handleSessionExpiry();
          throw new Error('Session Expired');
        }

        const data = await res.json();
        if (data.success) {
          this.newTemplateName = '';
          this.tplTab = 'load';
          this.fetchTemplates();
          // Translation applied
          window.showToast(window.grindsTranslations.template_saved || 'Template saved', 'success');
        } else {
          // Translation applied
          const msg = window.grindsTranslations.error || 'Error: %s';
          window.showToast(msg.replace('%s', data.error), 'error');
        }
      } catch (e) {
        if (window.grindsDebug) console.error(e);
        if (!this._sessionExpiryAlertShown) {
          // Translation applied
          window.showToast(window.grindsTranslations.system_error || 'System Error', 'error');
        }
      }
    },
  }));

  Alpine.data('heroSettings', () => ({
    buttons: window.grindsHeroSettings.buttons || [],
    searchResults: [],
    searchingIndex: null,

    init() {
      // Ensure robust initialization
      if (!this.buttons) this.buttons = [];
    },

    addBtn() {
      this.buttons.push({
        text: '',
        url: '',
        style: 'primary',
      });
    },

    removeBtn(index) {
      this.buttons.splice(index, 1);
    },

    selectPage(index, val) {
      if (val) {
        this.buttons[index].url = val;
        this.searchingIndex = null;
        this.searchResults = [];
      }
    },

    async searchContent(query) {
      if (!query) {
        this.searchResults = [];
        return;
      }
      try {
        // Attempt to fetch from API for scalability
        const res = await fetch(
          (window.grindsBaseUrl || '').replace(/\/$/, '') + '/admin/api/post_search.php?q=' + encodeURIComponent(query)
        );
        if (res.ok) {
          this.searchResults = await res.json();
          return;
        }
        throw new Error('API not available');
      } catch (e) {
        // Fallback to local filtering (limited to recent pages)
        const q = query.toLowerCase();
        this.searchResults = window.grindsLinkablePages
          .filter((p) => p.title.toLowerCase().includes(q) || p.slug.toLowerCase().includes(q))
          .slice(0, 20)
          .map((p) => ({
            title: p.title,
            url: '/' + p.slug,
            type: p.type,
          }));
      }
    },
  }));
});

document.addEventListener('DOMContentLoaded', function () {
  if (typeof flatpickr !== 'undefined') {
    const lang = window.grindsLang || 'en';
    flatpickr('#published_at', {
      enableTime: true,
      dateFormat: 'Y-m-d H:i',
      time_24hr: true,
      locale: lang === 'ja' ? 'ja' : 'en',
      onChange: function (selectedDates) {
        const now = new Date();
        const statusSelect = document.getElementById('hidden_post_status');
        const msgBox = document.getElementById('scheduled-message');
        const actionLabel = document.getElementById('main-action-label');
        if (selectedDates[0] > now) {
          if (statusSelect) statusSelect.value = 'published';
          if (msgBox) msgBox.classList.remove('hidden');
          if (actionLabel) actionLabel.textContent = window.grindsTranslations.action_schedule || 'Schedule';
        } else {
          if (msgBox) msgBox.classList.add('hidden');
          if (actionLabel) {
            const isUpdate =
              document.getElementById('main-action-is-update') &&
              document.getElementById('main-action-is-update').value === '1';
            actionLabel.textContent = isUpdate
              ? window.grindsTranslations.update || 'Update'
              : window.grindsTranslations.action_publish || 'Publish';
          }
        }
      },
    });
  }
});
