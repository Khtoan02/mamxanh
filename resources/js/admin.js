import '@hotwired/turbo';

// Lucide path data for the "file" icon drawn directly in JS (media detail
// modal's generic-file preview placeholder) — kept in sync by hand with the
// matching entry in resources/views/components/icon.blade.php since there's
// no shared source between a Blade component and a JS module.
const ICONS = {
    file: '<path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7z"/><path d="M14 2v4a2 2 0 0 0 2 2h4"/>',
};

// Elements are re-queried on every `turbo:load` (fires on the initial load
// AND every subsequent Turbo Drive navigation) because Turbo swaps <body>
// content per-visit — binding once at module load would leave these
// listeners attached to DOM nodes Turbo already discarded.
function initSidebarToggle() {
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebar-overlay');
    const openBtn = document.getElementById('sidebar-open');
    const closeBtn = document.getElementById('sidebar-close');

    function openSidebar() {
        sidebar?.classList.remove('-translate-x-full');
        overlay?.classList.remove('hidden');
    }

    function closeSidebar() {
        sidebar?.classList.add('-translate-x-full');
        overlay?.classList.add('hidden');
    }

    openBtn?.addEventListener('click', openSidebar);
    closeBtn?.addEventListener('click', closeSidebar);
    overlay?.addEventListener('click', closeSidebar);
}

// Collapse-to-icons toggle (desktop only — see the `.sidebar-collapsed`
// rules in resources/css/app.css). State lives in localStorage since it's
// a pure client-side display preference, not something worth a DB round
// trip. The <html> class itself already survives Turbo navigations
// untouched (Turbo only swaps <body>), so this only needs to run once per
// hard load in practice — re-running it on every turbo:load is just a
// cheap safety net, not load-bearing.
function initSidebarCollapse() {
    const toggle = document.getElementById('sidebar-collapse-toggle');
    const icon = toggle?.querySelector('svg');
    const collapsed = localStorage.getItem('mxd_sidebar_collapsed') === '1';

    function applyState(isCollapsed) {
        document.documentElement.classList.toggle('sidebar-collapsed', isCollapsed);
        if (toggle) toggle.title = isCollapsed ? 'Mở rộng menu' : 'Thu gọn menu';
        icon?.classList.toggle('rotate-180', isCollapsed);
    }

    applyState(collapsed);

    toggle?.addEventListener('click', () => {
        applyState(!document.documentElement.classList.contains('sidebar-collapsed'));
        localStorage.setItem('mxd_sidebar_collapsed', document.documentElement.classList.contains('sidebar-collapsed') ? '1' : '0');
    });
}

// Nội dung / Sản phẩm... accordion groups in the sidebar. Each group's
// open/closed state is purely visual (not persisted) — groups already
// auto-open server-side (via the `:active` prop) when the current page is
// inside them, so a fresh page load always shows the right group expanded.
function initNavGroups() {
    document.querySelectorAll('#sidebar .nav-group-toggle').forEach((toggleBtn) => {
        toggleBtn.addEventListener('click', () => {
            const children = toggleBtn.nextElementSibling;
            const chevron = toggleBtn.querySelector('.nav-group-chevron');
            const isHidden = children?.classList.toggle('hidden');
            chevron?.classList.toggle('-rotate-90', isHidden);
        });
    });
}

// Media Library page (admin/media/index.blade.php) — drag-and-drop upload
// and the detail modal. No-ops on every other admin page since it checks
// for #media-dropzone-overlay first. Bound to #admin-main (swapped fresh by
// Turbo on every navigation, unlike `document`) so listeners never pile up
// across repeat visits to this page.
function initMediaLibrary() {
    const dropzoneOverlay = document.getElementById('media-dropzone-overlay');
    if (!dropzoneOverlay) return;

    const main = document.getElementById('admin-main');
    const fileInput = document.getElementById('media-file-input');
    const uploadForm = document.getElementById('media-upload-form');
    const progress = document.getElementById('media-upload-progress');
    const csrfToken = uploadForm.querySelector('input[name=_token]').value;

    function uploadFiles(files) {
        if (!files.length) return;

        const formData = new FormData();
        formData.append('_token', csrfToken);
        Array.from(files).forEach((file) => formData.append('files[]', file));

        progress.textContent = `Đang tải lên ${files.length} tệp…`;
        progress.classList.remove('hidden');

        fetch(uploadForm.action, { method: 'POST', body: formData })
            .then((res) => {
                if (!res.ok) throw new Error('upload failed');
                window.location.reload();
            })
            .catch(() => {
                progress.textContent = 'Tải lên thất bại. Vui lòng thử lại.';
            });
    }

    fileInput?.addEventListener('change', () => uploadFiles(fileInput.files));

    // The overlay only appears while a file is actually being dragged over
    // the page — the whole #admin-main area already accepts a drop, so a
    // permanently-visible "drop here" bar would just be redundant chrome
    // the rest of the time.
    let dragCounter = 0;
    main?.addEventListener('dragover', (e) => e.preventDefault());
    main?.addEventListener('dragenter', (e) => {
        e.preventDefault();
        dragCounter++;
        dropzoneOverlay.classList.remove('hidden');
        dropzoneOverlay.classList.add('flex');
    });
    main?.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dragCounter--;
        if (dragCounter <= 0) {
            dragCounter = 0;
            dropzoneOverlay.classList.add('hidden');
            dropzoneOverlay.classList.remove('flex');
        }
    });
    main?.addEventListener('drop', (e) => {
        e.preventDefault();
        dragCounter = 0;
        dropzoneOverlay.classList.add('hidden');
        dropzoneOverlay.classList.remove('flex');
        if (e.dataTransfer?.files?.length) uploadFiles(e.dataTransfer.files);
    });

    // Bulk selection — a checkbox sibling overlays each card (not nested
    // inside the <button>, which would be invalid HTML), so toggling one
    // never bubbles into the card's own click-to-open-detail handler.
    const bulkCheckboxes = Array.from(document.querySelectorAll('.media-select-checkbox'));
    const bulkToolbar = document.getElementById('media-bulk-toolbar');
    const bulkCount = document.getElementById('media-bulk-count');
    const bulkClearBtn = document.getElementById('media-bulk-clear');
    const bulkDeleteForm = document.getElementById('media-bulk-delete-form');
    const selectAllBtn = document.getElementById('media-select-all');

    function selectedIds() {
        return bulkCheckboxes.filter((cb) => cb.checked).map((cb) => cb.dataset.mediaId);
    }

    function refreshBulkToolbar() {
        const ids = selectedIds();
        if (ids.length) {
            bulkToolbar.classList.remove('hidden');
            bulkToolbar.classList.add('flex');
            bulkCount.textContent = String(ids.length);
        } else {
            bulkToolbar.classList.add('hidden');
            bulkToolbar.classList.remove('flex');
        }
        if (selectAllBtn) {
            selectAllBtn.textContent = ids.length === bulkCheckboxes.length && bulkCheckboxes.length > 0
                ? 'Bỏ chọn tất cả'
                : 'Chọn tất cả';
        }
    }

    bulkCheckboxes.forEach((cb) => cb.addEventListener('change', refreshBulkToolbar));

    bulkClearBtn?.addEventListener('click', () => {
        bulkCheckboxes.forEach((cb) => { cb.checked = false; });
        refreshBulkToolbar();
    });

    selectAllBtn?.addEventListener('click', () => {
        const allSelected = selectedIds().length === bulkCheckboxes.length && bulkCheckboxes.length > 0;
        bulkCheckboxes.forEach((cb) => { cb.checked = !allSelected; });
        refreshBulkToolbar();
    });

    bulkDeleteForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const ids = selectedIds();
        if (!ids.length) return;
        if (!confirm(`Xoá ${ids.length} mục đã chọn? Không thể hoàn tác.`)) return;

        const formData = new FormData(bulkDeleteForm);
        ids.forEach((id) => formData.append('ids[]', id));

        const submitBtn = document.getElementById('media-bulk-delete');
        if (submitBtn) submitBtn.disabled = true;

        fetch(bulkDeleteForm.action, { method: 'POST', body: formData })
            .then((res) => {
                if (!res.ok) throw new Error('bulk delete failed');
                window.location.reload();
            })
            .catch(() => {
                alert('Xoá thất bại. Vui lòng thử lại.');
                if (submitBtn) submitBtn.disabled = false;
            });
    });

    // Detail modal — populated straight from the clicked card's data-*
    // attributes (already rendered server-side), no extra fetch needed.
    const overlay = document.getElementById('media-detail-overlay');
    const preview = document.getElementById('media-detail-preview');
    const altForm = document.getElementById('media-detail-alt-form');
    const altInput = document.getElementById('media-detail-alt-input');
    const deleteForm = document.getElementById('media-detail-delete-form');
    const urlInput = document.getElementById('media-detail-url');
    const openLink = document.getElementById('media-detail-open');
    const copyBtn = document.getElementById('media-detail-copy');
    const dimensionsLabel = document.getElementById('media-detail-dimensions-label');
    const dimensionsValue = document.getElementById('media-detail-dimensions');
    const mimeLabel = document.getElementById('media-detail-mime-label');
    const altLabel = document.getElementById('media-detail-alt-label');
    const altSaveBtn = document.getElementById('media-detail-alt-save');
    const altStatus = document.getElementById('media-detail-alt-status');
    const titleInput = document.getElementById('media-detail-title-input');
    const slugField = document.getElementById('media-detail-slug-field');
    const slugInput = document.getElementById('media-detail-slug-input');
    const slugSuffix = document.getElementById('media-detail-slug-suffix');
    const uploaderSelect = document.getElementById('media-detail-uploader-select');
    const updatedLabel = document.getElementById('media-detail-updated-label');
    const usageList = document.getElementById('media-detail-usage-list');
    const usageEmpty = document.getElementById('media-detail-usage-empty');

    let currentCard = null;

    function renderUsages(usages) {
        usageList.innerHTML = '';
        if (usages.length) {
            usageEmpty.classList.add('hidden');
            usages.forEach((u) => {
                const li = document.createElement('li');
                const a = document.createElement('a');
                a.href = u.url;
                a.target = '_blank';
                a.className = 'text-primary hover:underline';
                a.textContent = u.label;
                li.appendChild(a);
                usageList.appendChild(li);
            });
        } else {
            usageEmpty.classList.remove('hidden');
        }
    }

    // The real full URL is already shown (and copyable) next to the
    // preview image — the slug field only needs the file extension, shown
    // as plain text after the input rather than crammed inside it.
    function urlExtension(url) {
        const lastSlash = url.lastIndexOf('/');
        const lastDot = url.lastIndexOf('.');
        return lastDot > lastSlash ? url.slice(lastDot) : '';
    }

    function openDetail(card) {
        currentCard = card;
        altStatus.classList.add('invisible');
        const d = card.dataset;

        preview.innerHTML = '';
        if (d.kind === 'embed') {
            // A real embedded player (YouTube/Vimeo iframe), not just the
            // static oEmbed thumbnail — checked before the image branch
            // below, since embeds also carry a real d.thumb that would
            // otherwise win and leave it non-playable.
            const iframe = document.createElement('iframe');
            iframe.src = d.url;
            iframe.className = 'w-full aspect-video';
            iframe.allowFullscreen = true;
            preview.appendChild(iframe);
        } else if (d.kind === 'image') {
            // No fixed height on the image itself — the container has no
            // forced height either (only min/max), so it shrink-wraps to
            // the image's real rendered height instead of leaving empty
            // letterboxing bars above/below for non-square photos.
            const img = document.createElement('img');
            img.src = d.thumb;
            img.alt = d.altText || d.name;
            img.className = 'w-full h-auto max-h-[500px] object-contain';
            preview.appendChild(img);
        } else if (d.kind === 'video') {
            // Real HTML5 playback of the actual uploaded file — browsers
            // render native controls, no fabricated preview needed.
            const video = document.createElement('video');
            video.src = d.url;
            video.controls = true;
            video.className = 'w-full h-auto max-h-[500px]';
            preview.appendChild(video);
        } else if (d.kind === 'pdf') {
            // Browsers render PDFs natively inside an iframe pointed at
            // the real file — no PDF.js or extra dependency needed.
            const iframe = document.createElement('iframe');
            iframe.src = d.url;
            iframe.title = d.name;
            iframe.className = 'w-full h-[500px]';
            preview.appendChild(iframe);
        } else {
            // Only the generic "file" kind (docx/xlsx/zip/csv/txt/...)
            // still falls here — no browser-native way to preview those.
            const wrap = document.createElement('div');
            wrap.className = 'flex flex-col items-center gap-2 py-10 text-muted-foreground';
            wrap.innerHTML = `<svg class="h-10 w-10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">${ICONS.file}</svg>`;
            const label = document.createElement('span');
            label.className = 'text-sm';
            label.textContent = d.kindLabel;
            wrap.appendChild(label);
            preview.appendChild(wrap);
        }

        document.getElementById('media-detail-kind').textContent = d.provider ? `${d.kindLabel} (${d.provider})` : d.kindLabel;
        document.getElementById('media-detail-size').textContent = d.size;
        document.getElementById('media-detail-created').textContent = d.created;
        document.getElementById('media-detail-id').textContent = `#${d.mediaId}`;

        if (uploaderSelect) uploaderSelect.value = d.uploaderId || '';

        updatedLabel.classList.toggle('hidden', d.changed !== '1');
        document.getElementById('media-detail-updated').textContent = d.updated || '';

        if (d.kind === 'embed') {
            mimeLabel.classList.add('hidden');
            document.getElementById('media-detail-mime').classList.add('hidden');
        } else {
            mimeLabel.classList.remove('hidden');
            document.getElementById('media-detail-mime').classList.remove('hidden');
            document.getElementById('media-detail-mime').textContent = d.mime;
        }

        if (d.dimensions) {
            dimensionsLabel.classList.remove('hidden');
            dimensionsValue.classList.remove('hidden');
            dimensionsValue.textContent = d.dimensions;
        } else {
            dimensionsLabel.classList.add('hidden');
            dimensionsValue.classList.add('hidden');
        }

        urlInput.value = d.url;
        openLink.href = d.url;

        titleInput.value = d.name || '';

        if (d.slug) {
            slugField.classList.remove('hidden');
            slugSuffix.textContent = urlExtension(d.url);
            slugInput.value = d.slug;
        } else {
            slugField.classList.add('hidden');
            slugInput.value = '';
        }

        altLabel.textContent = d.kind === 'image' ? 'Mô tả ảnh (alt text)' : 'Mô tả (SEO)';
        altInput.value = d.altText || '';
        altForm.action = d.updateUrl;

        renderUsages(d.usedIn ? JSON.parse(d.usedIn) : []);

        deleteForm.action = d.destroyUrl;

        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        overlay.focus();
        // A plain listener on `overlay` would stop catching Escape the
        // moment focus moves off it (e.g. the Save button loses focus by
        // being disabled mid-request, which drops focus to <body>) — bind
        // to `document` instead, but only for the lifetime of this one
        // open modal (added here, removed in closeDetail) so it never
        // accumulates across repeat opens or leaks past this page visit.
        document.addEventListener('keydown', onDetailKeydown);
    }

    function onDetailKeydown(e) {
        if (e.key === 'Escape') closeDetail();
    }

    function closeDetail() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        preview.innerHTML = '';
        currentCard = null;
        document.removeEventListener('keydown', onDetailKeydown);
    }

    document.querySelectorAll('.media-card').forEach((card) => {
        card.addEventListener('click', () => openDetail(card));
    });

    document.getElementById('media-detail-close')?.addEventListener('click', closeDetail);
    overlay?.addEventListener('click', (e) => {
        if (e.target === overlay) closeDetail();
    });

    copyBtn?.addEventListener('click', () => {
        navigator.clipboard.writeText(urlInput.value);
        copyBtn.textContent = 'Đã copy!';
        setTimeout(() => {
            copyBtn.innerHTML = '<svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="14" height="14" x="8" y="8" rx="2" ry="2"/><path d="M4 16c-1.1 0-2-.9-2-2V4c0-1.1.9-2 2-2h10c1.1 0 2 .9 2 2"/></svg> Copy';
        }, 1500);
    });

    altForm?.addEventListener('submit', (e) => {
        e.preventDefault();
        const card = currentCard;
        altSaveBtn.disabled = true;
        altStatus.classList.add('invisible');

        // The form already carries `_token` (@csrf) and `_method=PATCH`
        // (@method) hidden fields, so a plain POST with its own FormData
        // is all Laravel needs to route it as the PATCH update. Applied in
        // place (no page reload) so the modal stays open — the controller
        // returns JSON (forced via the Accept header, since a plain fetch's
        // default Accept won't trip Laravel's wantsJson()) with the final
        // name/slug/URL/usage list, since renaming the slug really renames
        // the file and may not land on the exact slug typed (collisions
        // get a `-2` suffix), so the UI must reflect what the server did,
        // not just echo back what was typed.
        fetch(altForm.action, {
            method: 'POST',
            headers: { Accept: 'application/json' },
            body: new FormData(altForm),
        })
            .then((res) => {
                if (!res.ok) throw new Error('save failed');

                return res.json();
            })
            .then((json) => {
                altStatus.textContent = 'Đã lưu.';
                altStatus.classList.remove('invisible', 'text-destructive');
                altStatus.classList.add('text-brand-green');

                titleInput.value = json.original_name || '';
                urlInput.value = json.url || '';
                openLink.href = json.url || '#';

                if (json.slug) {
                    slugInput.value = json.slug;
                    slugSuffix.textContent = urlExtension(json.url);
                }

                if (uploaderSelect && json.uploader_id) uploaderSelect.value = String(json.uploader_id);

                if (json.updated_at) {
                    updatedLabel.classList.remove('hidden');
                    document.getElementById('media-detail-updated').textContent = json.updated_at;
                }

                renderUsages(json.used_in || []);

                const previewImg = preview.querySelector('img');
                if (previewImg && json.thumbnail_url) previewImg.src = json.thumbnail_url;

                if (card) {
                    const hasSeo = altInput.value.trim().length > 0;
                    card.dataset.altText = altInput.value;
                    card.dataset.hasSeo = hasSeo ? '1' : '0';
                    card.dataset.name = json.original_name || card.dataset.name;
                    card.dataset.slug = json.slug || card.dataset.slug;
                    card.dataset.url = json.url || card.dataset.url;
                    card.dataset.thumb = json.thumbnail_url || card.dataset.thumb;
                    card.dataset.uploader = json.uploader_name || card.dataset.uploader;
                    card.dataset.uploaderId = json.uploader_id || card.dataset.uploaderId;
                    card.dataset.updated = json.updated_at || card.dataset.updated;
                    card.dataset.changed = '1';

                    const dot = card.querySelector('.media-seo-dot');
                    if (dot) {
                        dot.classList.toggle('bg-brand-green', hasSeo);
                        dot.classList.toggle('bg-destructive', !hasSeo);
                        dot.title = hasSeo ? 'Đã có mô tả SEO' : 'Thiếu mô tả SEO';
                    }

                    const cardImg = card.querySelector('img');
                    if (cardImg && json.thumbnail_url) cardImg.src = json.thumbnail_url;

                    const nameEl = card.querySelector('p.truncate');
                    if (nameEl && json.original_name) {
                        nameEl.textContent = json.original_name;
                        nameEl.title = json.original_name;
                    }
                }
            })
            .catch(() => {
                altStatus.textContent = 'Lưu thất bại, thử lại.';
                altStatus.classList.remove('invisible', 'text-brand-green');
                altStatus.classList.add('text-destructive');
            })
            .finally(() => {
                altSaveBtn.disabled = false;
            });
    });
}

// Generic tab toggle — any `[data-tabs="name"]` button bar paired with a
// `[data-tab-panel-group="name"]` container of `[data-tab-panel="key"]`
// panels. Used by the content editor's SEO card (Chung/Nâng cao) today;
// written generic enough to drop into any future admin page unchanged.
function initTabGroups() {
    document.querySelectorAll('[data-tabs]').forEach((nav) => {
        const group = nav.dataset.tabs;
        const panelGroup = document.querySelector(`[data-tab-panel-group="${group}"]`);
        if (!panelGroup) return;

        const buttons = nav.querySelectorAll('[data-tab]');

        buttons.forEach((btn) => {
            btn.addEventListener('click', () => {
                buttons.forEach((b) => {
                    b.classList.toggle('bg-card', b === btn);
                    b.classList.toggle('shadow-sm', b === btn);
                    b.classList.toggle('text-foreground', b === btn);
                    b.classList.toggle('text-muted-foreground', b !== btn);
                });
                panelGroup.querySelectorAll('[data-tab-panel]').forEach((panel) => {
                    panel.classList.toggle('hidden', panel.dataset.tabPanel !== btn.dataset.tab);
                });
            });
        });
    });
}

// Danh mục/Thẻ page (admin/taxonomies/index.blade.php) — 1 modal dùng
// chung cho mọi hàng, repopulated from the clicked "Sửa" button's data-*
// attributes. No-ops elsewhere since it checks for the overlay first.
function initTaxonomyEditModal() {
    const overlay = document.getElementById('term-edit-overlay');
    if (!overlay) return;

    const form = document.getElementById('term-edit-form');
    const nameInput = document.getElementById('term-edit-name');
    const slugInput = document.getElementById('term-edit-slug');
    const descInput = document.getElementById('term-edit-description');

    function open(trigger) {
        form.action = trigger.dataset.action;
        nameInput.value = trigger.dataset.name || '';
        slugInput.value = trigger.dataset.slug || '';
        descInput.value = trigger.dataset.description || '';
        overlay.classList.remove('hidden');
        overlay.classList.add('flex');
        overlay.focus();
        document.addEventListener('keydown', onKeydown);
    }

    function close() {
        overlay.classList.add('hidden');
        overlay.classList.remove('flex');
        document.removeEventListener('keydown', onKeydown);
    }

    function onKeydown(e) {
        if (e.key === 'Escape') close();
    }

    document.querySelectorAll('[data-term-edit-trigger]').forEach((btn) => {
        btn.addEventListener('click', () => open(btn));
    });

    document.getElementById('term-edit-close')?.addEventListener('click', close);
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) close();
    });
}

// Content list page (admin/content/index.blade.php) — bulk select/delete/
// edit (same sibling-checkbox + fetch pattern as Media Library's bulk
// toolbar) plus the Bảng/Lưới (table/grid) view toggle, remembered per
// content type in localStorage like the sidebar-collapse preference.
function initContentList() {
    const toolbar = document.getElementById('content-bulk-toolbar');
    if (!toolbar) return;

    const csrfToken = document.querySelector('input[name=_token]')?.value;

    function checkboxes() {
        return Array.from(document.querySelectorAll('.content-select-checkbox'));
    }

    function selectedIds() {
        return checkboxes().filter((cb) => cb.checked).map((cb) => cb.dataset.postId);
    }

    const countEl = document.getElementById('content-bulk-count');

    function refreshToolbar() {
        const ids = selectedIds();
        if (ids.length) {
            toolbar.classList.remove('hidden');
            toolbar.classList.add('flex');
            if (countEl) countEl.textContent = String(ids.length);
        } else {
            toolbar.classList.add('hidden');
            toolbar.classList.remove('flex');
        }
    }

    checkboxes().forEach((cb) => cb.addEventListener('change', refreshToolbar));

    document.getElementById('content-bulk-clear')?.addEventListener('click', () => {
        checkboxes().forEach((cb) => { cb.checked = false; });
        refreshToolbar();
    });

    const deleteBtn = document.getElementById('content-bulk-delete');
    deleteBtn?.addEventListener('click', () => {
        const ids = selectedIds();
        if (!ids.length) return;
        if (!confirm(`Xoá ${ids.length} mục đã chọn? Không thể hoàn tác.`)) return;

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'DELETE');
        ids.forEach((id) => formData.append('ids[]', id));

        deleteBtn.disabled = true;
        fetch(toolbar.dataset.destroyUrl, { method: 'POST', body: formData })
            .then((res) => { if (!res.ok) throw new Error('bulk destroy failed'); window.location.reload(); })
            .catch(() => {
                alert('Xoá thất bại. Vui lòng thử lại.');
                deleteBtn.disabled = false;
            });
    });

    // Bulk edit modal
    const editOverlay = document.getElementById('content-bulk-edit-overlay');
    const editCountEl = document.getElementById('content-bulk-edit-count');
    const editSubmitBtn = document.getElementById('content-bulk-edit-submit');
    const statusSelect = document.getElementById('bulk-edit-status');
    const termSelect = document.getElementById('bulk-edit-term');

    function onEditKeydown(e) {
        if (e.key === 'Escape') closeEdit();
    }

    function openEdit() {
        if (editCountEl) editCountEl.textContent = String(selectedIds().length);
        editOverlay.classList.remove('hidden');
        editOverlay.classList.add('flex');
        editOverlay.focus();
        document.addEventListener('keydown', onEditKeydown);
    }

    function closeEdit() {
        editOverlay.classList.add('hidden');
        editOverlay.classList.remove('flex');
        document.removeEventListener('keydown', onEditKeydown);
    }

    document.getElementById('content-bulk-edit-open')?.addEventListener('click', openEdit);
    document.getElementById('content-bulk-edit-close')?.addEventListener('click', closeEdit);
    editOverlay?.addEventListener('click', (e) => {
        if (e.target === editOverlay) closeEdit();
    });

    editSubmitBtn?.addEventListener('click', () => {
        const ids = selectedIds();
        if (!ids.length) return;

        const formData = new FormData();
        formData.append('_token', csrfToken);
        formData.append('_method', 'PATCH');
        ids.forEach((id) => formData.append('ids[]', id));
        if (statusSelect?.value) formData.append('status', statusSelect.value);
        if (termSelect?.value) formData.append('term_id', termSelect.value);

        editSubmitBtn.disabled = true;
        fetch(toolbar.dataset.updateUrl, { method: 'POST', body: formData })
            .then((res) => { if (!res.ok) throw new Error('bulk update failed'); window.location.reload(); })
            .catch(() => {
                alert('Cập nhật thất bại. Vui lòng thử lại.');
                editSubmitBtn.disabled = false;
            });
    });

    // Bảng/Lưới view toggle
    const viewToggle = document.getElementById('content-view-toggle');
    if (!viewToggle) return;

    const storageKey = `mxd_content_view_${viewToggle.dataset.type}`;
    const tableView = document.getElementById('content-view-table');
    const gridView = document.getElementById('content-view-grid');
    const viewButtons = viewToggle.querySelectorAll('.content-view-btn');

    function applyView(view) {
        const showGrid = view === 'grid' && gridView;
        tableView?.classList.toggle('hidden', showGrid);
        gridView?.classList.toggle('hidden', !showGrid);
        viewButtons.forEach((btn) => {
            const active = btn.dataset.view === (showGrid ? 'grid' : 'table');
            btn.classList.toggle('bg-card', active);
            btn.classList.toggle('shadow-sm', active);
            btn.classList.toggle('text-foreground', active);
        });
    }

    viewButtons.forEach((btn) => {
        btn.addEventListener('click', () => {
            localStorage.setItem(storageKey, btn.dataset.view);
            applyView(btn.dataset.view);
        });
    });

    applyView(localStorage.getItem(storageKey) || 'table');
}

document.addEventListener('turbo:load', () => {
    initSidebarToggle();
    initSidebarCollapse();
    initNavGroups();
    initMediaLibrary();
    initTaxonomyEditModal();
    initTabGroups();
    initContentList();
});
