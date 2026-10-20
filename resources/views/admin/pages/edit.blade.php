@push('body-end')
  <input type="hidden" id="page-editor-app-url" value="{{ rtrim(url('/'), '/') }}" />

  <script>
    document.addEventListener('DOMContentLoaded', () => {
      const appUrlEl = document.getElementById('page-editor-app-url');
      const mediaListUrl = document.getElementById('media-list-url')?.value;
      const mediaUploadForm = document.getElementById('media-upload-form');
      const mediaSearch = document.getElementById('media-search');
      if (!appUrlEl || !mediaListUrl || !mediaUploadForm || !mediaSearch) return;

      const appUrl = appUrlEl.value;

      function publicUrlFromPath(path) {
        const p = (path || '').trim();
        if (!p) return '';
        if (/^https?:\/\//i.test(p)) return p;
        const clean = p.replace(/^\/+/, '');
        return `${appUrl}/${clean}`;
      }

      function syncMediaPathInput(input) {
        if (!input || !input.classList.contains('js-media-path-input')) return;
        const p = (input.value || '').trim();
        const readout = document.querySelector(`[data-readout-for="${input.id}"]`);
        if (readout) {
          readout.textContent = p || (readout.dataset.emptyLabel || '—');
        }
        const manual = document.getElementById(`${input.id}-manual`);
        if (manual && manual.value !== input.value) {
          manual.value = input.value;
        }
        const wrap = document.querySelector(`[data-media-preview-wrap="${input.id}"]`);
        if (!wrap) return;
        const img = wrap.querySelector('.js-media-preview-img');
        const ph = wrap.querySelector('.js-media-preview-placeholder');
        const err = wrap.querySelector('.js-media-preview-error');
        if (!img || !ph) return;
        err?.classList.add('hidden');
        if (!p) {
          img.removeAttribute('src');
          img.classList.add('hidden');
          ph.classList.remove('hidden');
          return;
        }
        ph.classList.add('hidden');
        img.classList.remove('hidden');
        img.onload = () => { err?.classList.add('hidden'); };
        img.onerror = () => {
          img.classList.add('hidden');
          ph.classList.add('hidden');
          err?.classList.remove('hidden');
        };
        img.src = publicUrlFromPath(p);
      }

      function syncRepeaterField(field) {
        if (!field) return;
        const input = field.querySelector('.js-repeater-input');
        const wrap = field.querySelector('.js-repeater-items');
        const template = field.querySelector('.js-repeater-item-template');
        if (!input || !wrap || !template) return;

        let data = [];
        try {
          data = JSON.parse(input.value || '[]');
        } catch (e) {
          data = [];
        }
        if (!Array.isArray(data)) data = [];

        wrap.innerHTML = '';

        const syncToInput = () => {
          const items = wrap.querySelectorAll('.js-repeater-item');
          const newData = Array.from(items).map(item => {
            const row = {};
            item.querySelectorAll('[data-name]').forEach(inp => {
              row[inp.getAttribute('data-name')] = inp.value;
            });
            return row;
          });
          input.value = JSON.stringify(newData);
        };

        const addItem = (values = {}) => {
          const clone = template.content.cloneNode(true);
          const item = clone.querySelector('.js-repeater-item');
          
          Object.keys(values).forEach(key => {
            const inp = item.querySelector(`[data-name="${key}"]`);
            if (inp) inp.value = values[key];
          });

          item.querySelector('.js-repeater-remove').addEventListener('click', () => {
            item.remove();
            syncToInput();
          });

          item.querySelectorAll('[data-name]').forEach(inp => {
            inp.addEventListener('input', syncToInput);
          });

          wrap.appendChild(item);
        };

        data.forEach(item => addItem(item));

        const addBtn = field.querySelector('.js-repeater-add');
        if (addBtn && !addBtn.dataset.bound) {
          addBtn.dataset.bound = '1';
          addBtn.addEventListener('click', () => {
            addItem();
            syncToInput();
          });
        }
      }

      function syncGalleryField(inputId) {
        const input = document.getElementById(inputId);
        const previewWrap = document.querySelector(`[data-gallery-preview-wrap="${inputId}"]`);
        if (!input || !previewWrap) return;

        let paths = [];
        try {
          paths = JSON.parse(input.value || '[]');
        } catch (e) {
          paths = [];
        }
        if (!Array.isArray(paths)) paths = [];

        previewWrap.innerHTML = '';
        if (paths.length === 0) {
          previewWrap.classList.add('hidden');
          return;
        }
        previewWrap.classList.remove('hidden');

        paths.forEach((path, idx) => {
          const thumb = document.createElement('div');
          thumb.className = 'group relative aspect-square overflow-hidden rounded-md border border-gray-200 bg-white shadow-sm';
          thumb.innerHTML = `
            <img src="${publicUrlFromPath(path)}" class="h-full w-full object-cover" />
            <button type="button" class="absolute right-1 top-1 flex h-6 w-6 items-center justify-center rounded-full bg-white/90 text-red-600 shadow-sm opacity-0 transition-opacity group-hover:opacity-100 hover:bg-red-50" title="{{ __('Remove image') }}">
              <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
            </button>
          `;
          thumb.querySelector('button').addEventListener('click', () => {
            const newPaths = [...paths];
            newPaths.splice(idx, 1);
            input.value = JSON.stringify(newPaths);
            syncGalleryField(inputId);
          });
          previewWrap.appendChild(thumb);
        });
      }

      let mediaTargetInputId = null;
      let mediaItems = [];
      let mediaSelectedPaths = []; // Changed from single path to array
      let mediaIsMulti = false;
      let shiftAnchor = null;

      function setMediaSelectedPaths(paths) {
        mediaSelectedPaths = Array.isArray(paths) ? paths.map(p => String(p)) : [];
        const insertBtn = document.getElementById('media-modal-insert');
        if (insertBtn) {
          insertBtn.disabled = mediaSelectedPaths.length === 0;
          insertBtn.textContent = mediaSelectedPaths.length > 1 
            ? `{{ __('Use') }} ${mediaSelectedPaths.length} {{ __('selected images') }}`
            : (mediaSelectedPaths.length === 1 ? `{{ __('Use selected image') }}` : `{{ __('Use selected image') }}`);
        }
      }

      function applyMediaSelectionAndClose() {
        if (!mediaTargetInputId || mediaSelectedPaths.length === 0) return;
        const input = document.getElementById(mediaTargetInputId);
        if (input) {
          if (mediaIsMulti) {
            // For gallery fields, we store as JSON array
            input.value = JSON.stringify(mediaSelectedPaths);
          } else {
            input.value = mediaSelectedPaths[0];
          }
          input.dispatchEvent(new Event('input', { bubbles: true }));
        }
        setMediaSelectedPaths([]);
        closeMediaModal();
      }

      function updateMediaModalSizing() {
        const modal = document.getElementById('media-modal');
        const panel = document.getElementById('media-modal-panel');
        if (!modal || !panel) return;
        const shell = document.querySelector('.admin-main-shell');
        const shellRect = shell?.getBoundingClientRect();
        const hasShellRect = !!(shellRect && shellRect.width > 0 && shellRect.height > 0);

        if (hasShellRect) {
          modal.style.top = `${Math.round(shellRect.top)}px`;
          modal.style.left = `${Math.round(shellRect.left)}px`;
          modal.style.width = `${Math.round(shellRect.width)}px`;
          modal.style.height = `${Math.round(shellRect.height)}px`;
        } else {
          modal.style.top = '0';
          modal.style.left = '0';
          modal.style.width = '100vw';
          modal.style.height = '100vh';
        }

        const horizontalGap = 16;
        modal.style.paddingLeft = `${horizontalGap}px`;
        modal.style.paddingRight = `${horizontalGap}px`;
        const usable = Math.max(480, (hasShellRect ? shellRect.width : window.innerWidth) - (horizontalGap * 2));
        panel.style.maxWidth = `min(72rem, ${Math.round(usable)}px)`;
      }
      function closeMediaModal() {
        setMediaSelectedPaths([]);
        const modal = document.getElementById('media-modal');
        if (!modal) return;
        modal.classList.add('hidden');
        modal.classList.remove('flex');
      }
      window.closeMediaModal = closeMediaModal;

      function renderMediaGrid(filter = '') {
        const grid = document.getElementById('media-grid');
        if (!grid) return;
        const q = (filter || '').toLowerCase();
        const list = mediaItems.filter((m) => !q || m.name.toLowerCase().includes(q));
        const esc = (s) => String(s || '').replace(/&/g, '&amp;').replace(/"/g, '&quot;').replace(/</g, '&lt;');
        
        grid.innerHTML = list.map((m, index) => {
          const sel = mediaSelectedPaths.includes(m.path);
          return `
          <button type="button" data-index="${index}" data-path="${esc(m.path)}" class="group relative flex flex-col rounded-lg border p-2 text-left shadow-sm transition-all duration-200 ${sel ? 'border-indigo-600 ring-4 ring-indigo-100 bg-indigo-50' : 'border-gray-200 hover:border-indigo-300 hover:bg-slate-50'}" title="${esc(m.name)}">
            <div class="relative overflow-hidden rounded-md">
              <img src="${String(m.url).replace(/"/g, '&quot;')}" alt="" class="h-24 w-full object-cover transition-transform duration-300 group-hover:scale-110" />
              ${sel ? `
                <div class="absolute inset-0 flex items-center justify-center bg-indigo-600/30 backdrop-blur-[1px]">
                  <div class="rounded-full bg-white p-1 shadow-lg">
                    <svg class="h-5 w-5 text-indigo-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path></svg>
                  </div>
                </div>
              ` : ''}
              <div class="absolute inset-0 bg-indigo-600 opacity-0 transition-opacity group-hover:opacity-10 pointer-events-none"></div>
            </div>
            <p class="mt-2 truncate text-[11px] font-medium ${sel ? 'text-indigo-900' : 'text-gray-600'} transition-colors" title="${esc(m.name)}">${esc(m.name)}</p>
          </button>`;
        }).join('');

        grid.querySelectorAll('button[data-path]').forEach((btn) => {
          const path = btn.getAttribute('data-path');
          const idx = parseInt(btn.getAttribute('data-index'));
          if (!path) return;

          btn.addEventListener('click', (ev) => {
            ev.preventDefault();
            if (!mediaTargetInputId) return;

            if (mediaIsMulti) {
              if (ev.shiftKey && shiftAnchor !== null) {
                const [start, end] = [Math.min(shiftAnchor, idx), Math.max(shiftAnchor, idx)];
                const range = list.slice(start, end + 1).map(item => item.path);
                const unique = new Set([...mediaSelectedPaths, ...range]);
                setMediaSelectedPaths(Array.from(unique));
              } else if (ev.ctrlKey || ev.metaKey) {
                if (mediaSelectedPaths.includes(path)) {
                  setMediaSelectedPaths(mediaSelectedPaths.filter(p => p !== path));
                } else {
                  setMediaSelectedPaths([...mediaSelectedPaths, path]);
                }
                shiftAnchor = idx;
              } else {
                setMediaSelectedPaths([path]);
                shiftAnchor = idx;
              }
            } else {
              setMediaSelectedPaths([path]);
              shiftAnchor = idx;
            }
            renderMediaGrid(document.getElementById('media-search')?.value || '');
          });

          btn.addEventListener('dblclick', (ev) => {
            ev.preventDefault();
            if (!mediaTargetInputId) return;
            if (!mediaIsMulti) {
              setMediaSelectedPaths([path]);
              applyMediaSelectionAndClose();
            }
          });
        });
      }
      async function openMediaModal(targetInputId, isMulti = false) {
        mediaTargetInputId = targetInputId;
        mediaIsMulti = !!isMulti;
        setMediaSelectedPaths([]);
        shiftAnchor = null;
        const modal = document.getElementById('media-modal');
        if (!modal) return;
        
        // Update instruction text for multi-select
        const searchInput = document.getElementById('media-search');
        if (searchInput) {
          searchInput.placeholder = mediaIsMulti 
            ? "{{ __('Search media... (Ctrl/Shift-click to select multiple)') }}"
            : "{{ __('Search media...') }}";
        }

        modal.style.position = 'fixed';
        modal.style.inset = 'auto';
        modal.style.zIndex = '220';
        updateMediaModalSizing();
        modal.classList.remove('hidden');
        modal.classList.add('flex');
        try {
          const res = await fetch(mediaListUrl, { credentials: 'same-origin' });
          const data = await res.json();
          mediaItems = data.media || [];
          renderMediaGrid(mediaSearch.value);
        } catch (_) {
          mediaItems = [];
          renderMediaGrid('');
        }
      }

      document.getElementById('media-modal-insert')?.addEventListener('click', () => {
        applyMediaSelectionAndClose();
      });
      mediaSearch.addEventListener('input', (e) => {
        renderMediaGrid(e.target.value);
      });
      document.querySelectorAll('.js-media-picker').forEach((btn) => {
        btn.addEventListener('click', () => {
          const isMulti = btn.getAttribute('data-media-multi') === '1';
          openMediaModal(btn.getAttribute('data-media-target'), isMulti);
        });
      });
      document.querySelectorAll('.js-media-modal-close').forEach((btn) => {
        btn.addEventListener('click', closeMediaModal);
      });
      document.getElementById('media-modal')?.addEventListener('click', (e) => {
        if (e.target?.id === 'media-modal') closeMediaModal();
      });
      window.addEventListener('resize', () => {
        const modal = document.getElementById('media-modal');
        if (modal && !modal.classList.contains('hidden')) updateMediaModalSizing();
      });
      const shell = document.querySelector('.admin-main-shell');
      if (shell && typeof ResizeObserver !== 'undefined') {
        const ro = new ResizeObserver(() => {
          const modal = document.getElementById('media-modal');
          if (modal && !modal.classList.contains('hidden')) updateMediaModalSizing();
        });
        ro.observe(shell);
      }

      document.querySelectorAll('.js-repeater-field').forEach((field) => {
        syncRepeaterField(field);
      });

      document.querySelectorAll('.js-media-path-input').forEach((input) => {
        input.addEventListener('input', () => {
          if (input.classList.contains('js-gallery-input')) {
            syncGalleryField(input.id);
          } else {
            syncMediaPathInput(input);
          }
        });
        if (input.classList.contains('js-gallery-input')) {
          syncGalleryField(input.id);
        } else {
          syncMediaPathInput(input);
        }
      });

      document.querySelectorAll('.js-media-manual-input').forEach((manual) => {
        manual.addEventListener('input', () => {
          const baseId = manual.id.replace(/-manual$/, '');
          const hidden = document.getElementById(baseId);
          if (!hidden) return;
          hidden.value = manual.value;
          hidden.dispatchEvent(new Event('input', { bubbles: true }));
        });
      });

      document.querySelectorAll('.js-media-copy-path').forEach((btn) => {
        const defaultLabel = btn.dataset.labelCopy || 'Copy';
        btn.addEventListener('click', async () => {
          const id = btn.getAttribute('data-copy-from');
          const el = id ? document.getElementById(id) : null;
          const v = (el?.value || '').trim();
          if (!v) return;
          try {
            await navigator.clipboard.writeText(v);
            const copied = btn.dataset.labelCopied || 'Copied';
            btn.textContent = copied;
            setTimeout(() => {
              btn.textContent = defaultLabel;
            }, 1800);
          } catch (_) {
            // Clipboard may be blocked; ignore.
          }
        });
      });

      document.querySelectorAll('.js-media-clear').forEach((btn) => {
        btn.addEventListener('click', () => {
          const id = btn.getAttribute('data-clear-target');
          const input = id ? document.getElementById(id) : null;
          if (input) {
            input.value = '';
            input.dispatchEvent(new Event('input', { bubbles: true }));
          }
        });
      });

      mediaUploadForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const input = document.getElementById('media-upload-input');
        const submitBtn = document.getElementById('media-upload-submit');
        const statusEl = document.getElementById('media-upload-status');
        if (!input.files || !input.files.length) return;
        const formData = new FormData(mediaUploadForm);
        const token = mediaUploadForm.querySelector('input[name="_token"]').value;
        if (submitBtn) submitBtn.disabled = true;
        input.disabled = true;
        mediaUploadForm.setAttribute('aria-busy', 'true');
        let statusHideMs = 2500;
        if (statusEl) {
          statusEl.classList.remove('hidden');
          const t = statusEl.querySelector('.media-upload-status-text');
          if (t) t.textContent = "{{ addslashes(__('Uploading…')) }}";
        }
        try {
          const res = await fetch(mediaUploadForm.action, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': token, 'X-Requested-With': 'XMLHttpRequest' },
            body: formData,
          });
          if (statusEl) {
            const t = statusEl.querySelector('.media-upload-status-text');
            if (t) {
              const okLabel = "{{ addslashes(__('Upload complete')) }}";
              const failLabel = "{{ addslashes(__('Upload failed')) }}";
              t.textContent = res.ok ? okLabel : `${failLabel} (HTTP ${res.status})`;
            }
          }
          if (res.ok) {
            statusHideMs = 800;
            input.value = '';
            await openMediaModal(mediaTargetInputId, mediaIsMulti);
          }
        } catch (err) {
          if (statusEl) {
            const t = statusEl.querySelector('.media-upload-status-text');
            if (t) t.textContent = "{{ addslashes(__('Upload failed. Check your connection.')) }}";
          }
        } finally {
          if (submitBtn) submitBtn.disabled = false;
          input.disabled = false;
          mediaUploadForm.removeAttribute('aria-busy');
          if (statusEl) {
            setTimeout(() => {
              statusEl.classList.add('hidden');
            }, statusHideMs);
          }
        }
      });
    });
  </script>
@endpush

@php
  $sectionFieldGroups = [];
  foreach ($pageInfo['fields'] as $f) {
    $g = $f['group'] ?? __('General');
    $sectionFieldGroups[$g][] = $f;
  }
@endphp

<x-app-layout>
  <x-admin.page-header :title="__('Edit page')" :subtitle="$pageInfo['label'] ?? null">
    <x-slot name="actions">
      <x-admin.button variant="secondary" :href="route('admin.pages.index')">{{ __('All pages') }}</x-admin.button>
    </x-slot>
  </x-admin.page-header>

  <x-admin.page-content class="pb-8">
    @if (session('status'))
      <div class="rounded border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
        {{ session('status') }}
      </div>
    @endif

    @if ($errors->any())
      <div class="rounded border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800">
        <ul class="list-disc space-y-1 pl-5">
          @foreach ($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="post" action="{{ route('admin.pages.update', ['slug' => $slug]) }}" class="space-y-4">
      @csrf
      @method('PUT')

      <section class="overflow-hidden rounded border border-wp-border bg-white">
        <div class="border-b border-wp-border bg-wp-bg px-4 py-3">
          <h3 class="text-sm font-semibold text-wp-text">{{ __('Page & SEO') }}</h3>
          <p class="mt-0.5 text-xs text-wp-text-muted">{{ __('Title and description used in the browser tab and search snippets.') }}</p>
        </div>
        <div class="space-y-4 p-4">
          <div>
            <x-input-label for="page_title" value="{{ __('Page title') }}" />
            <x-text-input
              id="page_title"
              name="title"
              type="text"
              class="mt-1 block w-full"
              value="{{ old('title', $page->title) }}"
              required
            />
          </div>
          <div>
            <x-input-label for="meta_description" value="{{ __('Meta description') }}" />
            <textarea
              id="meta_description"
              name="meta_description"
              rows="3"
              class="mt-1 block w-full"
            >{{ old('meta_description', $page->meta_description) }}</textarea>
            <p class="mt-1 text-xs text-wp-text-muted">{{ __('Optional. Roughly one or two sentences.') }}</p>
          </div>
        </div>
      </section>

      @if (count($pageInfo['fields']) > 0)
        @foreach ($sectionFieldGroups as $groupTitle => $fieldsInGroup)
          <section class="overflow-hidden rounded border border-wp-border bg-white">
            <div class="border-b border-wp-border bg-wp-bg px-4 py-3">
              <h3 class="text-sm font-semibold text-wp-text">{{ $groupTitle }}</h3>
            </div>
            <div class="grid gap-4 p-4 md:grid-cols-2">
                  @foreach ($fieldsInGroup as $field)
                    @php
                      $value = old('sections.'.$field['name'], $sectionValues[$field['name']] ?? $field['default']);
                      $inputId = 'section-'.$field['name'];
                    @endphp
                    @if ($field['type'] === 'image')
                      @php
                        $isThumbPreview = ($field['preview'] ?? '') === 'thumbnail';
                      @endphp
                      <div class="js-media-field {{ $isThumbPreview ? 'md:col-span-1' : 'md:col-span-2' }}">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                          <span class="text-sm font-medium text-wp-text">{{ $field['label'] }}</span>
                          <div class="flex items-center gap-2">
                            <button
                              type="button"
                              class="admin-luxe-btn-secondary js-media-picker"
                              data-media-target="{{ $inputId }}"
                            >{{ __('Select') }}</button>
                            <button
                              type="button"
                              class="admin-luxe-btn-secondary js-media-clear"
                              data-clear-target="{{ $inputId }}"
                            >{{ __('Clear') }}</button>
                          </div>
                        </div>

                        <input
                          type="hidden"
                          name="sections[{{ $field['name'] }}]"
                          id="{{ $inputId }}"
                          value="{{ $value }}"
                          class="js-media-path-input"
                          autocomplete="off"
                        />

                        <div
                          class="mt-3 overflow-hidden rounded border border-dashed border-wp-border bg-wp-bg"
                          data-media-preview-wrap="{{ $inputId }}"
                        >
                          <div class="relative flex items-center justify-center {{ $isThumbPreview ? 'h-32' : 'min-h-[10rem] max-h-[20rem]' }} w-full bg-gray-50">
                            <img
                              src=""
                              alt=""
                              class="js-media-preview-img hidden {{ $isThumbPreview ? 'h-full w-full object-cover' : 'max-h-full max-w-full object-contain' }}"
                            />
                            <div class="js-media-preview-placeholder pointer-events-none absolute inset-0 flex flex-col items-center justify-center gap-1 p-2 text-center">
                              <svg class="h-6 w-6 text-gray-300" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" /></svg>
                              <span class="text-[10px] font-medium text-gray-400">{{ __('No image') }}</span>
                            </div>
                            <div class="js-media-preview-error absolute inset-0 hidden flex-col items-center justify-center bg-red-50 p-2 text-center">
                              <span class="text-[10px] font-medium text-red-900">{{ __('Load error') }}</span>
                            </div>
                          </div>
                        </div>

                        <div class="mt-3">
                          <code
                            class="js-media-path-readout block truncate rounded border border-gray-100 bg-gray-50 px-2 py-1 text-[10px] text-gray-500"
                            data-readout-for="{{ $inputId }}"
                            data-empty-label="{{ __('No path set') }}"
                          ></code>
                        </div>

                        <details class="mt-2">
                          <summary class="cursor-pointer text-[10px] font-medium text-indigo-600 hover:text-indigo-800">{{ __('Edit path manually') }}</summary>
                          <div class="mt-1">
                            <input
                              type="text"
                              id="{{ $inputId }}-manual"
                              value="{{ $value }}"
                              class="js-media-manual-input block w-full rounded-md border-gray-300 py-1 text-[10px] shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                              autocomplete="off"
                            />
                          </div>
                        </details>
                      </div>
                     @elseif ($field['type'] === 'repeater')
                      <div class="js-repeater-field rounded border border-wp-border bg-wp-bg p-4 md:col-span-2" data-field-name="{{ $field['name'] }}" data-schema='@json($field['schema'])'>
                        <div class="mb-3 flex items-center justify-between gap-3">
                          <span class="text-sm font-medium text-wp-text">{{ $field['label'] }}</span>
                          <button type="button" class="admin-luxe-btn-primary js-repeater-add">
                            <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            {{ __('Add item') }}
                          </button>
                        </div>
                        
                        <input type="hidden" name="sections[{{ $field['name'] }}]" id="{{ $inputId }}" value="{{ $value }}" class="js-repeater-input" />
                        
                        <div class="space-y-4 js-repeater-items">
                          {{-- Items injected by JS --}}
                        </div>

                        <template class="js-repeater-item-template">
                          <div class="js-repeater-item group relative rounded border border-wp-border bg-white p-4">
                            <button type="button" class="js-repeater-remove absolute right-3 top-3 text-wp-text-muted hover:text-rose-600" title="Remove">
                              <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                            </button>
                            <div class="grid grid-cols-1 gap-4 pr-10">
                              @foreach($field['schema'] as $s)
                                <div>
                                  <label class="block text-[11px] font-bold text-slate-500 uppercase tracking-wider mb-1">{{ $s['label'] }}</label>
                                  @if($s['type'] === 'textarea')
                                    <textarea data-name="{{ $s['name'] }}" rows="2" class="w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500"></textarea>
                                  @else
                                    <input type="text" data-name="{{ $s['name'] }}" class="w-full rounded-md border-gray-300 text-sm focus:ring-indigo-500 focus:border-indigo-500" />
                                  @endif
                                </div>
                              @endforeach
                            </div>
                          </div>
                        </template>
                      </div>
                    @elseif ($field['type'] === 'gallery')
                      <div class="js-media-field md:col-span-2">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                          <span class="text-sm font-medium text-wp-text">{{ $field['label'] }}</span>
                          <div class="flex items-center gap-2">
                            <button
                              type="button"
                              class="admin-luxe-btn-secondary js-media-picker"
                              data-media-target="{{ $inputId }}"
                              data-media-multi="1"
                            >{{ __('Select images') }}</button>
                            <button
                              type="button"
                              class="admin-luxe-btn-secondary js-media-clear"
                              data-clear-target="{{ $inputId }}"
                            >{{ __('Clear all') }}</button>
                          </div>
                        </div>

                        <input
                          type="hidden"
                          name="sections[{{ $field['name'] }}]"
                          id="{{ $inputId }}"
                          value="{{ $value }}"
                          class="js-media-path-input js-gallery-input"
                          autocomplete="off"
                        />

                        <div
                          class="mt-4 hidden grid grid-cols-2 gap-3 sm:grid-cols-4 lg:grid-cols-6"
                          data-gallery-preview-wrap="{{ $inputId }}"
                        ></div>
                      </div>
                    @elseif ($field['type'] === 'textarea')
                      <div class="md:col-span-2">
                        <x-input-label :for="$inputId" :value="$field['label']" />
                        <textarea
                          id="{{ $inputId }}"
                          name="sections[{{ $field['name'] }}]"
                          rows="3"
                          class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >{{ $value }}</textarea>
                      </div>
                    @else
                      <div>
                        <x-input-label :for="$inputId" :value="$field['label']" />
                        <x-text-input
                          id="{{ $inputId }}"
                          name="sections[{{ $field['name'] }}]"
                          type="text"
                          class="mt-1 block w-full"
                          value="{{ $value }}"
                        />
                      </div>
                    @endif
                  @endforeach
            </div>
          </section>
        @endforeach
      @endif

      @unless(in_array($slug, ['about', 'listing-detail'], true))
        <details class="overflow-hidden rounded border border-wp-border bg-white">
          <summary class="cursor-pointer select-none border-b border-wp-border bg-wp-bg px-4 py-3">
            <div class="flex items-center justify-between gap-4">
              <div>
                <h3 class="text-sm font-semibold text-wp-text">{{ __('Advanced: Custom HTML') }}</h3>
                <p class="mt-0.5 text-xs text-wp-text-muted">{{ __('Optional extra markup for this page template. Prefer section fields above when possible.') }}</p>
              </div>
              <span class="text-xs text-wp-text-muted">{{ __('Toggle') }}</span>
            </div>
          </summary>
          <div class="p-4">
            <x-input-label for="content_html" value="{{ __('Content HTML') }}" />
            <textarea
              id="content_html"
              name="content_html"
              rows="12"
              class="mt-1 block w-full font-mono text-sm"
            >{{ old('content_html', $page->content_html) }}</textarea>
          </div>
        </details>
      @endunless

      <div class="flex flex-col gap-3 rounded border border-wp-border bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <label class="flex cursor-pointer items-start gap-3">
          <input type="hidden" name="is_active" value="0" />
          <input
            type="checkbox"
            name="is_active"
            value="1"
            class="mt-0.5 rounded border-wp-border text-wp-link focus:ring-wp-link"
            {{ old('is_active', (int) $page->is_active) ? 'checked' : '' }}
          />
          <span>
            <span class="block text-sm font-medium text-wp-text">{{ __('Page is active') }}</span>
            <span class="mt-0.5 block text-xs text-wp-text-muted">{{ __('Inactive pages return 404 on the public site.') }}</span>
          </span>
        </label>
        <div class="flex items-center justify-end gap-2">
          <x-admin.button variant="secondary" :href="route('admin.pages.index')">{{ __('All pages') }}</x-admin.button>
          <button type="submit" class="admin-luxe-btn-primary">{{ __('Save page') }}</button>
        </div>
      </div>
    </form>
  </x-admin.page-content>
</x-app-layout>
