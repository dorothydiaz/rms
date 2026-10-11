/**
 * ==========================================================================
 * RMS UNIVERSAL UOM REGISTRY & CUSTOM DROPDOWN ENGINE (V2)
 * ==========================================================================
 * Provides centralized, cached UoM catalog resolution, intra-category conversions,
 * and standard custom dropdown rendering across all ERP modules (Item Master,
 * RFQ, Purchase Orders, BOM, Stock movements, etc.).
 *
 * FEATURES:
 *  - Prominent UOM Acronym (Short code pill in monospace)
 *  - Full Descriptive Name + Base/Conversion formula hints
 *  - Categorized Sectioning (COUNT, WEIGHT, VOLUME, PORTION, LENGTH, AREA, TIME, OVERHEAD)
 *  - Quick Category Filter Pills along the top
 *  - Fuzzy Search (Typo-tolerant Levenshtein distance + acronym prefix + substring match)
 *  - Matched Substring Highlighting
 *  - Full Keyboard Navigation (Arrow keys, Enter to select, Escape to close)
 *  - Seamless two-way sync with underlying native <select> / <input> for HTML forms
 */
(function(window) {
    'use strict';

    const CACHE_KEY = 'rms_uom_registry_cache_v3';
    const CACHE_TTL_MS = 10 * 1000; // 10 seconds (short-lived to ensure immediate visibility of active/inactive toggles)

    // Category Metadata: Icons, Labels, Accent Colors
    const CATEGORY_META = {
        'COUNT':    { label: 'Count & Packaging', icon: 'ph-package', color: '#1e40af', bg: '#dbeafe' },
        'WEIGHT':   { label: 'Mass & Dry Goods', icon: 'ph-scales', color: '#065f46', bg: '#d1fae5' },
        'VOLUME':   { label: 'Volume & Liquids', icon: 'ph-drop', color: '#075985', bg: '#e0f2fe' },
        'PORTION':  { label: 'Portion & Recipes', icon: 'ph-fork-knife', color: '#9a3412', bg: '#ffedd5' },
        'LENGTH':   { label: 'Length & Linear', icon: 'ph-ruler', color: '#5b21b6', bg: '#ede9fe' },
        'AREA':     { label: 'Surface & Area', icon: 'ph-bounding-box', color: '#115e59', bg: '#ccfbf1' },
        'TIME':     { label: 'Time & Labor', icon: 'ph-clock', color: '#334155', bg: '#f1f5f9' },
        'OVERHEAD': { label: 'Overhead & Utility', icon: 'ph-lightning', color: '#86198f', bg: '#fae8ff' },
        'OTHER':    { label: 'General Units', icon: 'ph-tag', color: '#475569', bg: '#f8fafc' }
    };

    /**
     * Strictly verify if a unit is active AND its parent category is active.
     */
    function isUnitActive(u, categories) {
        if (!u) return false;
        const a = u.uom_is_active;
        const isSelfActive = (a === true || a === 1 || a === '1' || a === 'true');
        if (!isSelfActive) return false;

        if (categories && Array.isArray(categories) && categories.length > 0) {
            const cat = categories.find(c => String(c.category_code).toUpperCase() === String(u.uom_category).toUpperCase());
            if (cat) {
                const ca = cat.category_is_active;
                const isCatActive = (ca === true || ca === 1 || ca === '1' || ca === 'true' || ca === undefined);
                if (!isCatActive) return false;
            }
        }
        return true;
    }

    /**
     * Compute Levenshtein distance between two strings (for typo tolerance)
     */
    function levenshtein(a, b) {
        if (a === b) return 0;
        if (!a.length) return b.length;
        if (!b.length) return a.length;

        const matrix = [];
        for (let i = 0; i <= b.length; i++) matrix[i] = [i];
        for (let j = 0; j <= a.length; j++) matrix[0][j] = j;

        for (let i = 1; i <= b.length; i++) {
            for (let j = 1; j <= a.length; j++) {
                if (b.charAt(i - 1) === a.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1, // substitution
                        matrix[i][j - 1] + 1,     // insertion
                        matrix[i - 1][j] + 1      // deletion
                    );
                }
            }
        }
        return matrix[b.length][a.length];
    }

    /**
     * Escape special HTML characters
     */
    function escapeHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

    /**
     * Format numbers cleanly without ugly trailing zeros
     */
    function formatMultiplier(num) {
        const n = Number(num);
        if (isNaN(n)) return '1';
        if (n % 1 === 0) return String(n);
        if (n < 0.001) return n.toFixed(6).replace(/\.?0+$/, '');
        return n.toFixed(4).replace(/\.?0+$/, '');
    }

    const UomRegistry = {
        data: {
            categories: [],
            units: []
        },
        isLoaded: false,
        loadingPromise: null,

        /**
         * Invalidate memory and session cache so any subsequent lookup fetches fresh data from server
         */
        invalidateCache() {
            try {
                sessionStorage.removeItem(CACHE_KEY);
                sessionStorage.removeItem('rms_uom_registry_cache_v2');
                sessionStorage.removeItem('rms_uom_registry_cache_v1');
            } catch(e) {}
            this.isLoaded = false;
            this.loadingPromise = null;
        },

        /**
         * Load active UoMs and categories from server or local session cache
         */
        async load(forceRefresh = false) {
            if (this.isLoaded && !forceRefresh) {
                return this.data;
            }

            if (this.loadingPromise) {
                return this.loadingPromise;
            }

            // Check sessionStorage
            if (!forceRefresh) {
                const cached = sessionStorage.getItem(CACHE_KEY);
                if (cached) {
                    try {
                        const parsed = JSON.parse(cached);
                        if (Date.now() - parsed.timestamp < CACHE_TTL_MS) {
                            this.data = parsed.data;
                            this.isLoaded = true;
                            return this.data;
                        }
                    } catch(e) {
                        // ignore parse error and fetch fresh
                    }
                }
            }

            this.loadingPromise = (async () => {
                try {
                    const res = await fetch('/inventory/api/uoms', {
                        headers: { 
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache'
                        }
                    });
                    if (!res.ok) throw new Error('Failed to fetch UOM data');
                    const json = await res.json();
                    if (json.success) {
                        this.data = {
                            categories: json.categories || [],
                            units: json.units || []
                        };
                        this.isLoaded = true;
                        sessionStorage.setItem(CACHE_KEY, JSON.stringify({
                            timestamp: Date.now(),
                            data: this.data
                        }));
                    }
                } catch(err) {
                    console.warn('[UomRegistry] Error loading UOMs from server:', err);
                } finally {
                    this.loadingPromise = null;
                }
                return this.data;
            })();

            return this.loadingPromise;
        },

        /**
         * Get a single Unit by its code
         */
        get(uomCode) {
            if (!uomCode) return null;
            const code = String(uomCode).toUpperCase().trim();
            return this.data.units.find(u => u.uom_code.toUpperCase() === code) || null;
        },

        /**
         * Get all active units whose categories are also active
         */
        getUnits(category = null) {
            const activeOnly = this.data.units.filter(u => isUnitActive(u, this.data.categories));
            if (!category || category === 'ALL') {
                return activeOnly;
            }
            const cat = String(category).toUpperCase();
            return activeOnly.filter(u => String(u.uom_category).toUpperCase() === cat);
        },

        /**
         * Convert a quantity from one UOM to another within the same category
         */
        convert(quantity, fromCode, toCode) {
            const qty = parseFloat(quantity) || 0;
            if (fromCode === toCode) return qty;

            const fromUnit = this.get(fromCode);
            const toUnit = this.get(toCode);

            if (!fromUnit || !toUnit) return null;
            if (fromUnit.uom_category !== toUnit.uom_category) {
                console.warn(`[UomRegistry] Incompatible dimensional conversion: ${fromUnit.uom_category} vs ${toUnit.uom_category}`);
                return null;
            }

            // Convert to base unit, then to target unit
            const baseQty = qty * Number(fromUnit.base_ratio || 1);
            const targetRatio = Number(toUnit.base_ratio || 1);
            if (targetRatio === 0) return 0;

            return baseQty / targetRatio;
        },

        /**
         * Hydrate standard HTML <select> options as fallback
         */
        populateSelect(selectEl, selectedValue = null, options = {}) {
            if (!selectEl) return;
            const currentVal = selectedValue !== null ? selectedValue : selectEl.value;
            const categoryFilter = options.category || null;
            const includeArchived = options.includeArchived !== false;

            const units = this.getUnits(categoryFilter);
            const categories = this.data.categories;

            // Group by category
            const grouped = {};
            units.forEach(u => {
                const cat = u.uom_category || 'OTHER';
                if (!grouped[cat]) grouped[cat] = [];
                grouped[cat].push(u);
            });

            let html = '';
            if (options.placeholder) {
                html += `<option value="">${options.placeholder}</option>`;
            }

            let hasSelected = false;

            Object.keys(grouped).forEach(catCode => {
                const catObj = categories.find(c => c.category_code === catCode);
                const catLabel = catObj ? `${catObj.category_code} (${catObj.category_name})` : catCode;

                html += `<optgroup label="${catLabel}">`;
                grouped[catCode].forEach(u => {
                    const isSelected = String(u.uom_code).toUpperCase() === String(currentVal).toUpperCase();
                    if (isSelected) hasSelected = true;
                    html += `<option value="${u.uom_code}" ${isSelected ? 'selected' : ''}>${u.uom_code} - ${u.uom_name}</option>`;
                });
                html += `</optgroup>`;
            });

            if (currentVal && !hasSelected && includeArchived) {
                html += `<optgroup label="Historical / Legacy Value">`;
                html += `<option value="${currentVal}" selected>${currentVal} (Archived / Custom)</option>`;
                html += `</optgroup>`;
            }

            selectEl.innerHTML = html;
        },

        /**
         * Fuzzy search match between a query and a UOM object.
         * Returns { match: boolean, score: number, matchedCode: boolean, matchedName: boolean }
         */
        fuzzyMatch(query, uom) {
            if (!query) return { match: true, score: 1, matchedCode: false, matchedName: false };
            const q = query.toLowerCase().trim();
            const code = (uom.uom_code || '').toLowerCase();
            const name = (uom.uom_name || '').toLowerCase();
            const cat = (uom.uom_category || '').toLowerCase();

            // 1. Exact match on acronym
            if (code === q) {
                return { match: true, score: 100, matchedCode: true, matchedName: false };
            }
            // 2. Acronym starts with query (e.g. "pc" -> "PCS", "kg" -> "KG")
            if (code.startsWith(q)) {
                return { match: true, score: 90, matchedCode: true, matchedName: false };
            }
            // 3. Acronym contains query
            if (code.includes(q)) {
                return { match: true, score: 80, matchedCode: true, matchedName: false };
            }
            // 4. Exact word match in name
            const words = name.split(/[\s,()/-]+/);
            for (const w of words) {
                if (w === q) return { match: true, score: 75, matchedCode: false, matchedName: true };
                if (w.startsWith(q)) return { match: true, score: 70, matchedCode: false, matchedName: true };
            }
            // 5. Name contains query substring
            if (name.includes(q)) {
                return { match: true, score: 65, matchedCode: false, matchedName: true };
            }
            // 6. Category matches query
            if (cat.includes(q)) {
                return { match: true, score: 55, matchedCode: false, matchedName: false };
            }
            // 7. Typo Tolerance via Levenshtein Distance
            if (q.length >= 3) {
                if (levenshtein(q, code) <= 1) {
                    return { match: true, score: 50, matchedCode: true, matchedName: false };
                }
                for (const w of words) {
                    if (w.length >= 3) {
                        const maxDist = w.length > 5 ? 2 : 1;
                        if (levenshtein(q, w) <= maxDist) {
                            return { match: true, score: 45, matchedCode: false, matchedName: true };
                        }
                    }
                }
            }

            return { match: false, score: 0, matchedCode: false, matchedName: false };
        },

        /**
         * Highlight matched text substring with <span class="uom-option-highlight">
         */
        highlightText(text, query) {
            if (!query || !text) return escapeHtml(text);
            const q = query.trim();
            const idx = text.toLowerCase().indexOf(q.toLowerCase());
            if (idx === -1) return escapeHtml(text);

            const before = escapeHtml(text.substring(0, idx));
            const match = escapeHtml(text.substring(idx, idx + q.length));
            const after = escapeHtml(text.substring(idx + q.length));
            return `${before}<span class="uom-option-highlight">${match}</span>${after}`;
        },

        /**
         * Transform a container or native <select> into a custom accessible UOM dropdown
         */
        createCustomDropdown(target, config = {}) {
            let container = null;
            let nativeSelect = null;

            if (typeof target === 'string') {
                target = document.querySelector(target);
            }
            if (!target) return null;

            if (target.tagName === 'SELECT') {
                nativeSelect = target;
                // Wrap native select if not already wrapped
                if (!target.parentElement.classList.contains('uom-dropdown-wrapper')) {
                    container = document.createElement('div');
                    container.className = 'uom-dropdown-wrapper';
                    target.parentNode.insertBefore(container, target);
                    container.appendChild(target);
                } else {
                    container = target.parentElement;
                }
            } else {
                container = target;
                nativeSelect = container.querySelector('select');
            }

            // Read configuration from data attributes or config object
            const currentVal = config.value !== undefined ? config.value : (nativeSelect ? nativeSelect.value : (container.getAttribute('data-uom-current') || ''));
            const categoryFilter = config.category || container.getAttribute('data-uom-category') || null;
            const placeholder = config.placeholder || container.getAttribute('data-uom-placeholder') || 'Select Unit of Measure';
            const isDisabled = config.disabled || (nativeSelect ? nativeSelect.disabled : false);

            // Populate fallback native select
            if (nativeSelect) {
                this.populateSelect(nativeSelect, currentVal, { category: categoryFilter, placeholder });
                nativeSelect.classList.add('uom-native-select');
            }

            // Remove existing custom UI if re-initializing
            const existingTrigger = container.querySelector('.uom-dropdown-trigger');
            if (existingTrigger) existingTrigger.remove();
            const existingMenu = container.querySelector('.uom-dropdown-menu');
            if (existingMenu) existingMenu.remove();

            // 1. Build Trigger Element
            const trigger = document.createElement('div');
            trigger.className = `uom-dropdown-trigger ${isDisabled ? 'is-disabled' : ''}`;
            trigger.setAttribute('tabindex', isDisabled ? '-1' : '0');
            trigger.setAttribute('role', 'combobox');
            trigger.setAttribute('aria-expanded', 'false');
            trigger.setAttribute('aria-haspopup', 'listbox');

            // 2. Build Floating Menu
            const menu = document.createElement('div');
            menu.className = 'uom-dropdown-menu';
            menu.setAttribute('role', 'listbox');

            // Menu Header with Search & Category Chips (Only active categories present in units)
            const activeUnitsForChips = this.getUnits(categoryFilter);
            const activeCategoriesPresent = Array.from(new Set(activeUnitsForChips.map(u => u.uom_category)));

            let categoryChipsHtml = `<button type="button" class="uom-category-chip is-active" data-cat="ALL"><i class="ph ph-squares-four"></i> ALL</button>`;
            activeCategoriesPresent.forEach(catCode => {
                const meta = CATEGORY_META[catCode] || CATEGORY_META['OTHER'];
                categoryChipsHtml += `<button type="button" class="uom-category-chip" data-cat="${escapeHtml(catCode)}"><i class="ph ${meta.icon}"></i> ${escapeHtml(catCode)}</button>`;
            });

            const header = document.createElement('div');
            header.className = 'uom-menu-header';
            header.innerHTML = `
                <div class="uom-search-bar">
                    <i class="ph ph-magnifying-glass uom-search-icon"></i>
                    <input type="text" class="uom-search-input" placeholder="Search code, name, category (e.g. PCS, KG, Portion)..." autocomplete="off" spellcheck="false">
                    <button type="button" class="uom-search-clear" title="Clear search"><i class="ph ph-x"></i></button>
                </div>
                <div class="uom-category-chips">
                    ${categoryChipsHtml}
                </div>
            `;

            // Options List Container
            const optionsList = document.createElement('div');
            optionsList.className = 'uom-options-list';

            // Menu Footer Help
            const footer = document.createElement('div');
            footer.className = 'uom-menu-footer';
            footer.innerHTML = `
                <span><i class="ph ph-arrows-down-up"></i> ↑/↓ Navigate • ↵ Select</span>
                <span>ESC to Close</span>
            `;

            menu.appendChild(header);
            menu.appendChild(optionsList);
            menu.appendChild(footer);

            container.appendChild(trigger);
            container.appendChild(menu);

            // State variables
            let selectedCode = currentVal;
            let activeCategoryChip = 'ALL';
            let searchQuery = '';
            let focusedOptionIndex = -1;

            const self = this;

            /**
             * Palette of solid light pastel colors (100% opaque, no transparency)
             */
            const PASTEL_PALETTES = [
                { bg: '#eff6ff', text: '#1e40af', border: '#3b82f6' }, // Light Blue (Count)
                { bg: '#ecfdf5', text: '#065f46', border: '#10b981' }, // Light Emerald (Weight)
                { bg: '#f0f9ff', text: '#075985', border: '#0ea5e9' }, // Light Sky (Volume)
                { bg: '#fff7ed', text: '#9a3412', border: '#f97316' }, // Light Orange (Portion)
                { bg: '#f5f3ff', text: '#5b21b6', border: '#8b5cf6' }, // Light Purple (Length)
                { bg: '#f0fdfa', text: '#115e59', border: '#14b8a6' }, // Light Teal (Area)
                { bg: '#fdf4ff', text: '#86198f', border: '#d946ef' }, // Light Fuchsia (Overhead)
                { bg: '#fefce8', text: '#854d0e', border: '#eab308' }, // Light Amber
                { bg: '#eef2ff', text: '#3730a3', border: '#6366f1' }, // Light Indigo
                { bg: '#f8fafc', text: '#334155', border: '#64748b' }  // Light Slate (Time)
            ];

            function getCategoryTheme(catCode) {
                if (!catCode) return PASTEL_PALETTES[0];
                const upper = String(catCode).toUpperCase();
                if (upper === 'COUNT') return PASTEL_PALETTES[0];
                if (upper === 'WEIGHT') return PASTEL_PALETTES[1];
                if (upper === 'VOLUME') return PASTEL_PALETTES[2];
                if (upper === 'PORTION') return PASTEL_PALETTES[3];
                if (upper === 'LENGTH') return PASTEL_PALETTES[4];
                if (upper === 'AREA') return PASTEL_PALETTES[5];
                if (upper === 'OVERHEAD') return PASTEL_PALETTES[6];
                if (upper === 'TIME') return PASTEL_PALETTES[9];

                // Deterministic hash for custom categories (e.g. CONT_2KF2, CONT_ECUZ, CONT_FPCC)
                let hash = 0;
                for (let i = 0; i < upper.length; i++) {
                    hash = (hash << 5) - hash + upper.charCodeAt(i);
                    hash |= 0;
                }
                const idx = Math.abs(hash) % (PASTEL_PALETTES.length - 1);
                return PASTEL_PALETTES[idx];
            }

            /**
             * Update trigger UI display (No clear (X) button, preserving full input width)
             */
            function updateTriggerDisplay() {
                const uom = self.get(selectedCode);
                if (!selectedCode || !uom) {
                    trigger.innerHTML = `
                        <div class="uom-trigger-content">
                            <span class="uom-trigger-placeholder">${escapeHtml(placeholder)}</span>
                        </div>
                        <i class="ph ph-caret-down uom-trigger-arrow"></i>
                    `;
                    return;
                }

                trigger.innerHTML = `
                    <div class="uom-trigger-content">
                        <span class="uom-code-pill cat-${escapeHtml(uom.uom_category)}">${escapeHtml(uom.uom_code)}</span>
                        <span class="uom-name-text" title="${escapeHtml(uom.uom_name)}">${escapeHtml(uom.uom_name)}</span>
                        <span class="uom-trigger-hint">${escapeHtml(uom.uom_category)}</span>
                    </div>
                    <i class="ph ph-caret-down uom-trigger-arrow"></i>
                `;
            }

            /**
             * Render options list with filtering and sectioning
             */
            function renderOptions() {
                const allUnits = self.getUnits(categoryFilter);
                const categories = self.data.categories;

                // Filter by chip category
                let filtered = allUnits;
                if (activeCategoryChip !== 'ALL') {
                    filtered = filtered.filter(u => u.uom_category === activeCategoryChip);
                }

                // Filter by search query
                let scored = [];
                if (searchQuery) {
                    filtered.forEach(u => {
                        const matchInfo = self.fuzzyMatch(searchQuery, u);
                        if (matchInfo.match) {
                            scored.push({ uom: u, score: matchInfo.score });
                        }
                    });
                    scored.sort((a, b) => b.score - a.score);
                } else {
                    scored = filtered.map(u => ({ uom: u, score: 1 }));
                }

                if (scored.length === 0) {
                    optionsList.innerHTML = `
                        <div class="uom-empty-state">
                            <i class="ph ph-magnifying-glass"></i>
                            <div>No measurement units match "<strong>${escapeHtml(searchQuery)}</strong>"</div>
                            <div style="font-size: 0.70rem; color: #94a3b8; margin-top: 4px;">Try searching by acronym (e.g. PCS, KG, PORT, BOX) or category.</div>
                        </div>
                    `;
                    focusedOptionIndex = -1;
                    return;
                }

                // Group scored units by category
                const groups = {};
                scored.forEach(item => {
                    const cat = item.uom.uom_category || 'OTHER';
                    if (!groups[cat]) groups[cat] = [];
                    groups[cat].push(item.uom);
                });

                let html = '';
                let itemIdx = 0;

                Object.keys(groups).forEach(catCode => {
                    const catUnits = groups[catCode];
                    const meta = CATEGORY_META[catCode] || CATEGORY_META['OTHER'];
                    const catObj = categories.find(c => c.category_code === catCode);
                    const catName = catObj ? catObj.category_name : meta.label;
                    const catTheme = getCategoryTheme(catCode);

                    html += `
                        <div class="uom-section-header cat-${escapeHtml(catCode)}" 
                             style="background-color: ${catTheme.bg} !important; color: ${catTheme.text} !important; border-left: 4px solid ${catTheme.border} !important; opacity: 1 !important; backdrop-filter: none !important; -webkit-backdrop-filter: none !important;">
                            <div class="uom-section-title">
                                <i class="ph ${meta.icon}"></i>
                                <span>${escapeHtml(catCode)} — ${escapeHtml(catName)}</span>
                            </div>
                            <span class="uom-section-count" style="background-color: #ffffff; color: ${catTheme.text}; border: 1px solid ${catTheme.border}; font-weight: 700;">${catUnits.length}</span>
                        </div>
                    `;

                    catUnits.forEach(u => {
                        const isSelected = String(u.uom_code).toUpperCase() === String(selectedCode).toUpperCase();
                        const isFocused = itemIdx === focusedOptionIndex;
                        const codeHtml = searchQuery ? self.highlightText(u.uom_code, searchQuery) : escapeHtml(u.uom_code);
                        const nameHtml = searchQuery ? self.highlightText(u.uom_name, searchQuery) : escapeHtml(u.uom_name);

                        const refCode = catObj ? (catObj.base_uom_code || 'Base') : 'Base';
                        const formula = u.is_base_unit 
                            ? `Reference Base Unit (1.0 ${u.uom_code})` 
                            : `1 ${u.uom_code} = ${formatMultiplier(u.base_ratio)} ${refCode}`;

                        html += `
                            <div class="uom-option-item ${isSelected ? 'is-selected' : ''} ${isFocused ? 'is-focused' : ''}" 
                                 data-uom-code="${escapeHtml(u.uom_code)}" 
                                 data-option-index="${itemIdx}" 
                                 role="option" 
                                 aria-selected="${isSelected ? 'true' : 'false'}">
                                
                                <!-- Code Column: 30% Width -->
                                <div class="uom-option-code-col">
                                    <span class="uom-code-pill cat-${escapeHtml(u.uom_category)}">${codeHtml}</span>
                                </div>

                                <!-- UOM Name Column: 70% Width with Word Wrap -->
                                <div class="uom-option-name-col">
                                    <span class="uom-option-name">${nameHtml}</span>
                                </div>

                                <!-- Conversion '?' Tooltip & Checkmark -->
                                <div class="uom-option-actions">
                                    <button type="button" 
                                            class="uom-convert-info-btn" 
                                            title="Conversion: ${escapeHtml(formula)}"
                                            onclick="event.stopPropagation();" 
                                            tabindex="-1">
                                        ?
                                        <span class="uom-tooltip-box">${escapeHtml(formula)}</span>
                                    </button>
                                    <i class="ph ph-check uom-check-icon"></i>
                                </div>
                            </div>
                        `;
                        itemIdx++;
                    });
                });

                optionsList.innerHTML = html;
            }

            /**
             * Select a value and synchronize
             */
            function selectValue(uomCode) {
                selectedCode = uomCode ? String(uomCode).toUpperCase() : '';
                updateTriggerDisplay();

                // Synchronize native select
                if (nativeSelect) {
                    nativeSelect.value = selectedCode;
                    nativeSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    nativeSelect.dispatchEvent(new Event('input', { bubbles: true }));
                }

                // Fire custom callback
                if (typeof config.onChange === 'function') {
                    config.onChange(selectedCode, self.get(selectedCode));
                }

                closeMenu();
            }

            /**
             * Open floating dropdown menu
             */
            function openMenu() {
                if (isDisabled) return;
                // Close any other open dropdowns
                document.querySelectorAll('.uom-dropdown-wrapper.is-open').forEach(w => {
                    if (w !== container) w.classList.remove('is-open');
                });

                // Smart positioning: flip upward if near window bottom
                const rect = trigger.getBoundingClientRect();
                const spaceBelow = window.innerHeight - rect.bottom;
                if (spaceBelow < 340 && rect.top > 340) {
                    container.classList.add('flip-up');
                } else {
                    container.classList.remove('flip-up');
                }

                container.classList.add('is-open');
                trigger.setAttribute('aria-expanded', 'true');

                // Render options and autofocus search input
                renderOptions();
                const searchInput = header.querySelector('.uom-search-input');
                if (searchInput) {
                    setTimeout(() => searchInput.focus(), 30);
                }
            }

            /**
             * Close dropdown menu
             */
            function closeMenu() {
                container.classList.remove('is-open');
                trigger.setAttribute('aria-expanded', 'false');
                focusedOptionIndex = -1;
            }

            // Toggle menu on trigger click
            trigger.addEventListener('click', (e) => {
                if (container.classList.contains('is-open')) {
                    closeMenu();
                } else {
                    openMenu();
                }
            });

            // Trigger keyboard navigation
            trigger.addEventListener('keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') {
                    e.preventDefault();
                    openMenu();
                }
            });

            // Search input handling
            const searchInput = header.querySelector('.uom-search-input');
            const searchClear = header.querySelector('.uom-search-clear');

            searchInput.addEventListener('input', (e) => {
                searchQuery = e.target.value;
                if (searchQuery) {
                    searchClear.classList.add('is-visible');
                } else {
                    searchClear.classList.remove('is-visible');
                }
                focusedOptionIndex = 0;
                renderOptions();
            });

            searchClear.addEventListener('click', () => {
                searchInput.value = '';
                searchQuery = '';
                searchClear.classList.remove('is-visible');
                focusedOptionIndex = -1;
                renderOptions();
                searchInput.focus();
            });

            // Keyboard navigation inside search input
            searchInput.addEventListener('keydown', (e) => {
                const items = optionsList.querySelectorAll('.uom-option-item');
                if (items.length === 0) return;

                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    focusedOptionIndex = (focusedOptionIndex + 1) % items.length;
                    highlightFocusedOption(items);
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    focusedOptionIndex = (focusedOptionIndex - 1 + items.length) % items.length;
                    highlightFocusedOption(items);
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    if (focusedOptionIndex >= 0 && items[focusedOptionIndex]) {
                        const code = items[focusedOptionIndex].getAttribute('data-uom-code');
                        selectValue(code);
                    }
                } else if (e.key === 'Escape') {
                    e.preventDefault();
                    closeMenu();
                    trigger.focus();
                }
            });

            function highlightFocusedOption(items) {
                items.forEach((item, idx) => {
                    if (idx === focusedOptionIndex) {
                        item.classList.add('is-focused');
                        item.scrollIntoView({ block: 'nearest' });
                    } else {
                        item.classList.remove('is-focused');
                    }
                });
            }

            // Category Filter Chips
            header.querySelectorAll('.uom-category-chip').forEach(chip => {
                chip.addEventListener('click', (e) => {
                    header.querySelectorAll('.uom-category-chip').forEach(c => c.classList.remove('is-active'));
                    chip.classList.add('is-active');
                    activeCategoryChip = chip.getAttribute('data-cat') || 'ALL';
                    focusedOptionIndex = -1;
                    renderOptions();
                });
            });

            // Option item selection via event delegation
            optionsList.addEventListener('click', (e) => {
                const item = e.target.closest('.uom-option-item');
                if (item) {
                    const code = item.getAttribute('data-uom-code');
                    selectValue(code);
                }
            });

            // Listen for programmatic value changes on native select
            if (nativeSelect) {
                nativeSelect.addEventListener('change', () => {
                    const valUpper = (nativeSelect.value || '').toUpperCase();
                    if (valUpper !== selectedCode) {
                        selectedCode = valUpper;
                        updateTriggerDisplay();
                    }
                });
            }

            // Initial trigger view
            updateTriggerDisplay();

            const instance = {
                setValue: (val) => selectValue(val),
                getValue: () => selectedCode,
                open: () => openMenu(),
                close: () => closeMenu(),
            };

            container._uomDropdown = instance;
            if (nativeSelect) nativeSelect._uomDropdown = instance;

            return instance;
        },

        /**
         * Automatically hydrate all standard and custom UoM dropdowns across the application
         */
        async autoHydrate() {
            await this.load();

            // 1. Hydrate declared custom dropdown wrappers [data-uom-dropdown]
            document.querySelectorAll('[data-uom-dropdown]').forEach(container => {
                this.createCustomDropdown(container);
            });

            // 2. Transform standard selects with [data-uom-select] into custom dropdowns if not already wrapped
            document.querySelectorAll('select[data-uom-select]').forEach(sel => {
                if (!sel.closest('.uom-dropdown-wrapper')) {
                    this.createCustomDropdown(sel);
                }
            });
        }
    };

    // Close any open dropdowns when clicking outside
    document.addEventListener('click', (e) => {
        if (!e.target.closest('.uom-dropdown-wrapper')) {
            document.querySelectorAll('.uom-dropdown-wrapper.is-open').forEach(w => {
                w.classList.remove('is-open');
                const trigger = w.querySelector('.uom-dropdown-trigger');
                if (trigger) trigger.setAttribute('aria-expanded', 'false');
            });
        }
    });

    // Listen for cross-module UOM registry invalidation events
    window.addEventListener('rms:uom-registry-invalidated', async () => {
        UomRegistry.invalidateCache();
        await UomRegistry.load(true);
        document.querySelectorAll('.uom-dropdown-wrapper').forEach(container => {
            UomRegistry.createCustomDropdown(container);
        });
    });

    window.UomRegistry = UomRegistry;

    // Auto-load and hydrate on DOMContentLoaded
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => UomRegistry.autoHydrate());
    } else {
        UomRegistry.autoHydrate();
    }
})(window);
