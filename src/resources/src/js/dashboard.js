import Sortable from 'sortablejs';
import Masonry from 'masonry-layout';

document.addEventListener('DOMContentLoaded', () => {
    const grid = document.getElementById('cw-dashboard-grid');
    if (!grid) return;

    const pageId = grid.dataset.pageId;

    // Init Masonry
    const msnry = new Masonry(grid, {
        itemSelector: '.cw-dashboard-widget',
        columnWidth: '.cw-grid-sizer',
        gutter: '.cw-gutter-sizer',
        percentPosition: true,
        transitionDuration: '0.2s',
    });

    // Re-layout when widget content changes size (e.g. charts rendering)
    let layoutTimer = null;
    const observer = new ResizeObserver(() => {
        clearTimeout(layoutTimer);
        layoutTimer = setTimeout(() => msnry.layout(), 100);
    });
    grid.querySelectorAll('.cw-dashboard-widget').forEach(el => observer.observe(el));

    // Init SortableJS
    Sortable.create(grid, {
        animation: 150,
        draggable: '.cw-dashboard-widget',
        handle: '.cw-widget-title',
        ghostClass: 'cw-sortable-ghost',
        onEnd: () => {
            msnry.reloadItems();
            msnry.layout();

            const widgetIds = [...grid.querySelectorAll('.cw-dashboard-widget')]
                .map(el => el.dataset.id);

            Craft.sendActionRequest('POST', 'commerce-widgets/pages/reorder-widgets', {
                data: { widgetIds }
            });
        }
    });

    // Add Widget menu
    const addBtn = document.getElementById('cw-add-widget-btn');
    if (addBtn) {
        const menu = addBtn.nextElementSibling;
        if (menu) {
            menu.querySelectorAll('a[data-type]').forEach(link => {
                link.addEventListener('click', (e) => {
                    e.preventDefault();
                    const type = link.dataset.type;

                    Craft.sendActionRequest('POST', 'commerce-widgets/pages/add-widget', {
                        data: { type, pageId }
                    }).then(response => {
                        if (response.data.success && response.data.widget) {
                            const widget = response.data.widget;
                            const temp = document.createElement('div');
                            temp.innerHTML = buildWidgetHtml(widget);
                            const el = temp.firstElementChild;
                            grid.appendChild(el);
                            bindWidgetActions(el);
                            observer.observe(el);
                            msnry.appended(el);
                            msnry.layout();
                            execWidgetJs(widget.bodyJs);
                        }
                    }).catch(() => {
                        Craft.cp.displayError('Could not add widget.');
                    });
                });
            });
        }
    }

    // Page management
    const addPageBtn = document.getElementById('cw-add-page');
    if (addPageBtn) {
        addPageBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const name = prompt('Enter page name:', 'New Page');
            if (name === null || name.trim() === '') return;

            Craft.sendActionRequest('POST', 'commerce-widgets/pages/add-page', {
                data: { name: name.trim() }
            }).then(response => {
                if (response.data.success) {
                    window.location.href = Craft.getCpUrl(response.data.page.url);
                }
            });
        });
    }

    const renamePageBtn = document.getElementById('cw-rename-page');
    if (renamePageBtn) {
        renamePageBtn.addEventListener('click', (e) => {
            e.preventDefault();
            const currentName = document.querySelector('#main-content h1')?.textContent || '';
            const name = prompt('Enter new page name:', currentName);
            if (name === null || name.trim() === '') return;

            Craft.sendActionRequest('POST', 'commerce-widgets/pages/rename-page', {
                data: { pageId, name: name.trim() }
            }).then(response => {
                if (response.data.success) {
                    window.location.reload();
                }
            });
        });
    }

    const deletePageBtn = document.getElementById('cw-delete-page');
    if (deletePageBtn) {
        deletePageBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (!confirm('Are you sure you want to delete this page and all its widgets?')) return;

            Craft.sendActionRequest('POST', 'commerce-widgets/pages/delete-page', {
                data: { pageId }
            }).then(response => {
                if (response.data.success) {
                    window.location.href = Craft.getCpUrl(response.data.redirectUrl);
                } else if (response.data.error) {
                    Craft.cp.displayError(response.data.error);
                }
            });
        });
    }

    // Bind actions on existing widgets
    grid.querySelectorAll('.cw-dashboard-widget').forEach(bindWidgetActions);

    function bindWidgetActions(el) {
        const settingsBtn = el.querySelector('.cw-widget-settings-btn');
        if (settingsBtn) {
            settingsBtn.addEventListener('click', () => {
                openSettingsModal(el);
            });
        }
    }

    function openSettingsModal(widgetEl) {
        const widgetId = widgetEl.dataset.id;
        const widgetTitle = widgetEl.querySelector('.cw-widget-title')?.textContent || 'Widget';

        // Create overlay
        const overlay = document.createElement('div');
        overlay.className = 'cw-modal-overlay';
        overlay.innerHTML = `
            <div class="cw-modal">
                <div class="cw-modal-header">
                    <h2>${escapeHtml(widgetTitle)} Settings</h2>
                    <button type="button" class="cw-modal-close" title="Close">
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                        </svg>
                    </button>
                </div>
                <div class="cw-modal-body">
                    <div class="cw-modal-loading">
                        <div class="spinner"></div>
                    </div>
                </div>
                <div class="cw-modal-footer" style="display: none;">
                    <a href="#" class="cw-modal-remove" style="color: #dc2626; text-decoration: none;">Remove</a>
                    <div style="display: flex; gap: 8px;">
                        <button type="button" class="btn cw-modal-cancel">Cancel</button>
                        <button type="button" class="btn submit cw-modal-save">Save</button>
                    </div>
                </div>
            </div>
        `;

        document.body.appendChild(overlay);

        const closeModal = () => {
            document.removeEventListener('keydown', escHandler);
            overlay.remove();
        };

        overlay.querySelector('.cw-modal-close').addEventListener('click', closeModal);
        overlay.querySelector('.cw-modal-cancel').addEventListener('click', closeModal);
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal();
        });

        const escHandler = (e) => {
            if (e.key === 'Escape') closeModal();
        };
        document.addEventListener('keydown', escHandler);

        // Fetch settings from server
        Craft.sendActionRequest('GET', 'commerce-widgets/pages/get-widget-settings', {
            params: { widgetId }
        }).then(response => {
            if (!response.data.success) {
                closeModal();
                return;
            }

            const modalBody = overlay.querySelector('.cw-modal-body');
            const modalFooter = overlay.querySelector('.cw-modal-footer');
            const currentColspan = response.data.colspan || 1;

            let html = '';

            // Widget-specific settings
            if (response.data.settingsHtml) {
                html += '<div class="cw-modal-widget-settings">';
                html += response.data.settingsHtml;
                html += '</div>';
                html += '<hr style="margin: 16px 0; border: none; border-top: 1px solid #e5e7eb;">';
            }

            // Colspan selector
            html += `
                <div class="field">
                    <div class="heading">
                        <label>Widget Width</label>
                    </div>
                    <div class="input">
                        <div class="select">
                            <select class="cw-colspan-select">
                                <option value="1"${currentColspan === 1 ? ' selected' : ''}>1</option>
                                <option value="2"${currentColspan === 2 ? ' selected' : ''}>2</option>
                                <option value="3"${currentColspan === 3 ? ' selected' : ''}>3</option>
                                <option value="4"${currentColspan === 4 ? ' selected' : ''}>4</option>
                                <option value="5"${currentColspan === 5 ? ' selected' : ''}>5</option>
                                <option value="6"${currentColspan === 6 ? ' selected' : ''}>6</option>
                                <option value="7"${currentColspan === 7 ? ' selected' : ''}>7</option>
                            </select>
                        </div>
                    </div>
                </div>
            `;

            modalBody.innerHTML = html;
            modalFooter.style.display = '';

            // Init Craft UI elements (lightswitches, etc.)
            if (typeof Craft.initUiElements === 'function') {
                Craft.initUiElements(modalBody);
            }

            // Execute any captured JS from widget settings
            if (response.data.settingsJs) {
                try {
                    eval(response.data.settingsJs);
                } catch (e) {
                    // Silently ignore JS init errors
                }
            }

            // Colspan select
            let selectedColspan = currentColspan;
            const colspanSelect = modalBody.querySelector('.cw-colspan-select');
            if (colspanSelect) {
                colspanSelect.addEventListener('change', () => {
                    selectedColspan = parseInt(colspanSelect.value);
                });
            }

            // Remove handler (two-step confirmation)
            const removeBtn = overlay.querySelector('.cw-modal-remove');
            let removeConfirmed = false;
            let removeTimer = null;
            removeBtn.addEventListener('click', (e) => {
                e.preventDefault();
                if (!removeConfirmed) {
                    removeConfirmed = true;
                    removeBtn.textContent = 'Click again to confirm';
                    removeTimer = setTimeout(() => {
                        removeConfirmed = false;
                        removeBtn.textContent = 'Remove';
                    }, 3000);
                    return;
                }
                clearTimeout(removeTimer);
                removeBtn.style.pointerEvents = 'none';
                removeBtn.style.opacity = '0.5';
                Craft.sendActionRequest('POST', 'commerce-widgets/pages/remove-widget', {
                    data: { widgetId }
                }).then(resp => {
                    if (resp.data.success) {
                        msnry.remove(widgetEl);
                        msnry.layout();
                        closeModal();
                    }
                });
            });

            // Save handler
            overlay.querySelector('.cw-modal-save').addEventListener('click', () => {
                const saveBtn = overlay.querySelector('.cw-modal-save');
                saveBtn.classList.add('loading');
                saveBtn.disabled = true;

                const settings = collectSettings(modalBody);

                Craft.sendActionRequest('POST', 'commerce-widgets/pages/save-widget-settings', {
                    data: {
                        widgetId,
                        colspan: selectedColspan,
                        settings
                    }
                }).then(resp => {
                    if (resp.data.success && resp.data.widget) {
                        const w = resp.data.widget;

                        // Update colspan class
                        widgetEl.dataset.colspan = w.colspan;
                        for (let i = 1; i <= 7; i++) {
                            widgetEl.classList.remove('cw-colspan-' + i);
                        }
                        widgetEl.classList.add('cw-colspan-' + w.colspan);

                        // Update title
                        const titleEl = widgetEl.querySelector('.cw-widget-title');
                        if (titleEl) titleEl.textContent = w.title;

                        // Update subtitle
                        const subtitleEl = widgetEl.querySelector('.cw-widget-subtitle');
                        if (subtitleEl && w.subtitle) {
                            subtitleEl.textContent = w.subtitle;
                            subtitleEl.style.display = '';
                        } else if (subtitleEl && !w.subtitle) {
                            subtitleEl.style.display = 'none';
                        }

                        // Update body
                        const bodyEl = widgetEl.querySelector('.cw-widget-body');
                        if (bodyEl) bodyEl.innerHTML = w.html;

                        execWidgetJs(w.bodyJs);
                        closeModal();
                        msnry.layout();
                    }
                }).catch(() => {
                    saveBtn.classList.remove('loading');
                    saveBtn.disabled = false;
                });
            });

        }).catch(() => {
            closeModal();
        });
    }

    function collectSettings(modalBody) {
        const settings = {};
        const container = modalBody.querySelector('.cw-modal-widget-settings');
        if (!container) return settings;

        // Collect standard inputs (text, select, hidden, etc.)
        container.querySelectorAll('select[name], input[name], textarea[name]').forEach(input => {
            if (input.type === 'checkbox') return; // handled by lightswitch logic
            settings[input.name] = input.value;
        });

        // Handle Craft lightswitch fields
        container.querySelectorAll('.lightswitch').forEach(ls => {
            const hiddenInput = ls.closest('.field')?.querySelector('input[type="hidden"][name]');
            if (hiddenInput) {
                settings[hiddenInput.name] = ls.classList.contains('on') ? 1 : 0;
            }
        });

        return settings;
    }

    function execWidgetJs(js) {
        if (!js) return;
        try { eval(js); } catch (e) { console.error('Widget JS error:', e); }
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    const GEAR_SVG = '<svg width="16" height="16" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M11.49 3.17c-.38-1.56-2.6-1.56-2.98 0a1.532 1.532 0 01-2.286.948c-1.372-.836-2.942.734-2.106 2.106.54.886.061 2.042-.947 2.287-1.561.379-1.561 2.6 0 2.978a1.532 1.532 0 01.947 2.287c-.836 1.372.734 2.942 2.106 2.106a1.532 1.532 0 012.287.947c.379 1.561 2.6 1.561 2.978 0a1.533 1.533 0 012.287-.947c1.372.836 2.942-.734 2.106-2.106a1.533 1.533 0 01.947-2.287c1.561-.379 1.561-2.6 0-2.978a1.532 1.532 0 01-.947-2.287c.836-1.372-.734-2.942-2.106-2.106a1.532 1.532 0 01-2.287-.947zM10 13a3 3 0 100-6 3 3 0 000 6z" clip-rule="evenodd"/></svg>';

    function buildWidgetHtml(widget) {
        const colspan = widget.colspan || 1;
        const subtitle = widget.subtitle
            ? `<p class="light cw-widget-subtitle" style="margin: 0;">${escapeHtml(widget.subtitle)}</p>`
            : '';

        return `<div class="cw-dashboard-widget cw-colspan-${colspan}" data-id="${widget.id}" data-type="${escapeHtml(widget.type)}" data-colspan="${colspan}">
            <div style="background: #fff; border-radius: 6px; padding: 1.25rem; box-shadow: 0 1px 3px rgba(0,0,0,0.08); overflow: hidden;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 12px;">
                    <div>
                        <h2 class="cw-widget-title" style="margin: 0;">${escapeHtml(widget.title)}</h2>
                        ${subtitle}
                    </div>
                    <button type="button" class="cw-widget-settings-btn" title="Widget Settings">
                        ${GEAR_SVG}
                    </button>
                </div>
                <div class="cw-widget-body">${widget.html}</div>
            </div>
        </div>`;
    }
});
