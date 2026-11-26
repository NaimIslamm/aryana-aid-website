/**
 * Aryana Aid Donation System - Slide-Out Donation Drawer Engine
 * Manages the slide-out mini-cart, persistent multi-item basket,
 * quantity steppers, item removal, live totals, and checkout flow.
 */

(function ($) {
    'use strict';

    window.adsDrawer = window.adsDrawer || {};

    const config = window.adsDrawerConfig || {
        enabled: true,
        title: 'Your donations',
        checkoutText: 'Proceed To Checkout',
        checkoutUrl: '/donate/',
        emptyText: 'Your donation basket is currently empty.',
        continueText: 'Continue Browsing',
        showQty: true,
        showRemove: true,
        showThumbnails: true,
        showFloatingIcon: true,
        closeOnBackdrop: true,
        currency: '£',
        currencyCode: 'GBP',
        defaultImages: {},
        ajaxUrl: '/wp-admin/admin-ajax.php',
        nonce: ''
    };

    const currency = config.currency || '£';

    // Format Currency Helper
    function formatMoney(amount) {
        const num = parseFloat(amount) || 0;
        return currency + num.toFixed(2);
    }

    // Resolve Best Thumbnail Image for a Project
    function resolveProjectImage(project, pkg) {
        const str = ((project || '') + ' ' + (pkg || '')).toLowerCase();
        const imgs = config.defaultImages || {};

        if (str.includes('orphan') || str.includes('child')) {
            return imgs.orphan || imgs.fallback;
        }
        if (str.includes('widow')) {
            return imgs.widow || imgs.fallback;
        }
        if (str.includes('winter') || str.includes('shelter') || str.includes('blanket') || str.includes('emergency')) {
            return imgs.winter || imgs.fallback;
        }
        if (str.includes('water') || str.includes('well') || str.includes('pump') || str.includes('tank')) {
            return imgs.water || imgs.fallback;
        }
        if (str.includes('food') || str.includes('pack') || str.includes('ramadan') || str.includes('iftar')) {
            return imgs.food || imgs.fallback;
        }
        return imgs.fallback || imgs.orphan || '';
    }

    // =========================================================================
    // STORAGE HELPERS
    // =========================================================================
    function getStorageKey(freq) {
        return (freq === 'monthly') ? 'ads_persistent_basket_monthly' : 'ads_persistent_basket_single';
    }

    function getActiveBasket() {
        let singleItems = [];
        let monthlyItems = [];

        try {
            const rawSingle = localStorage.getItem('ads_persistent_basket_single');
            if (rawSingle) singleItems = JSON.parse(rawSingle) || [];
        } catch (e) {}

        try {
            const rawMonthly = localStorage.getItem('ads_persistent_basket_monthly');
            if (rawMonthly) monthlyItems = JSON.parse(rawMonthly) || [];
        } catch (e) {}

        if (!Array.isArray(singleItems)) singleItems = [];
        if (!Array.isArray(monthlyItems)) monthlyItems = [];

        // Normalize items with quantity and unit_amount (strictly reject £0 placeholder rows)
        const normalize = function (item, freq) {
            if (!item) return null;
            const unit = parseFloat(item.unit_amount || item.amount) || 0;
            if (unit <= 0) return null; // Reject 0 or negative items!
            
            const pkgStr = String(item.package || '').toLowerCase();
            if (pkgStr.includes('£0') || pkgStr === '£0.00' || pkgStr.includes(' 0 ')) return null;

            const qty = parseInt(item.quantity, 10) || 1;
            return {
                project: item.project || 'General Donation',
                location: item.location || 'Afghanistan',
                type: item.type || 'Sadaqah',
                package: item.package || (item.project ? `${currency}${unit} ${item.project}` : 'Donation'),
                unit_amount: unit,
                amount: unit * qty,
                quantity: qty,
                frequency: item.frequency || freq || 'single',
                is_explicit: true
            };
        };

        const validSingle = singleItems.map(it => normalize(it, 'single')).filter(Boolean);
        const validMonthly = monthlyItems.map(it => normalize(it, 'monthly')).filter(Boolean);

        // Auto-purge £0 rows from localStorage if any were cleaned
        if (validSingle.length !== singleItems.length || validMonthly.length !== monthlyItems.length) {
            try {
                localStorage.setItem('ads_persistent_basket_single', JSON.stringify(validSingle));
                localStorage.setItem('ads_persistent_basket_monthly', JSON.stringify(validMonthly));
            } catch (e) {}
        }

        return {
            single: validSingle,
            monthly: validMonthly
        };
    }

    function saveBaskets(baskets) {
        try {
            localStorage.setItem('ads_persistent_basket_single', JSON.stringify(baskets.single || []));
            localStorage.setItem('ads_persistent_basket_monthly', JSON.stringify(baskets.monthly || []));
        } catch (e) {}
    }

    // Sync with WooCommerce Cart Session via Background AJAX
    function syncWooCommerceCart() {
        if (!config.ajaxUrl || !config.nonce) return;

        const baskets = getActiveBasket();
        const allItems = [].concat(baskets.single, baskets.monthly);

        $.ajax({
            url: config.ajaxUrl,
            type: 'POST',
            dataType: 'json',
            data: {
                action: 'ads_sync_drawer_cart',
                nonce: config.nonce,
                items: allItems
            },
            success: function (res) {
                if (res && res.success && res.data) {
                    // Update header WooCommerce cart badges if present on page
                    $('.cart-contents .count, .menu-cart__count, .woocommerce-cart-count, .cart-count').text(res.data.cart_count);
                }
            }
        });
    }

    // =========================================================================
    // DRAWER CONTROLLER
    // =========================================================================
    const Drawer = {
        $root: null,
        $panel: null,
        $backdrop: null,
        $itemsList: null,
        $emptyState: null,
        $footer: null,
        $singleTotal: null,
        $monthlyTotal: null,
        $dueTotal: null,
        $launcher: null,
        $launcherBadge: null,

        init: function () {
            this.$root = $('#ads-donation-drawer-root');
            if (!this.$root.length) return;

            this.$panel = $('#ads-drawer-panel');
            this.$backdrop = $('#ads-drawer-backdrop');
            this.$itemsList = $('#ads-drawer-items-list');
            this.$emptyState = $('#ads-drawer-empty');
            this.$footer = $('#ads-drawer-footer');
            this.$singleTotal = $('#ads-drawer-single-total');
            this.$monthlyTotal = $('#ads-drawer-monthly-total');
            this.$dueTotal = $('#ads-drawer-due-total');
            this.$launcher = $('#ads-drawer-launcher');
            this.$launcherBadge = $('#ads-drawer-launcher-badge');

            this.bindEvents();
            if (!this.isOpen()) {
                this.$root.hide();
            }
            this.render();
        },

        bindEvents: function () {
            const self = this;

            // Close Button
            $('#ads-drawer-close').on('click', function (e) {
                e.preventDefault();
                self.close();
            });

            // Backdrop Click
            if (config.closeOnBackdrop) {
                this.$backdrop.on('click', function (e) {
                    e.preventDefault();
                    self.close();
                });
            }

            // ESC Key Closes Drawer
            $(document).on('keydown', function (e) {
                if (e.key === 'Escape' && self.isOpen()) {
                    self.close();
                }
            });

            // Empty Continue Button
            $('#ads-drawer-empty-continue').on('click', function (e) {
                e.preventDefault();
                self.close();
            });

            // Stepper Minus Click
            this.$itemsList.on('click', '.ads-drawer-step-minus', function (e) {
                e.preventDefault();
                const freq = $(this).data('freq');
                const idx = parseInt($(this).data('idx'), 10);
                self.changeQuantity(freq, idx, -1);
            });

            // Stepper Plus Click
            this.$itemsList.on('click', '.ads-drawer-step-plus', function (e) {
                e.preventDefault();
                const freq = $(this).data('freq');
                const idx = parseInt($(this).data('idx'), 10);
                self.changeQuantity(freq, idx, 1);
            });

            // Remove Link Click
            this.$itemsList.on('click', '.ads-drawer-remove-link', function (e) {
                e.preventDefault();
                const freq = $(this).data('freq');
                const idx = parseInt($(this).data('idx'), 10);
                self.removeItem(freq, idx);
            });

            // Checkout CTA Button Click
            $('#ads-drawer-checkout-btn').on('click', function (e) {
                e.preventDefault();
                self.proceedToCheckout();
            });

            // Floating Launcher Click
            if (this.$launcher) {
                this.$launcher.on('click', function (e) {
                    e.preventDefault();
                    self.open();
                });
            }

            // Global Hook to open drawer from any custom trigger
            $(document).on('click', '[data-ads-open-drawer], a[href="#ads-cart"]', function (e) {
                e.preventDefault();
                self.open();
            });
        },

        isOpen: function () {
            return this.$root.hasClass('ads-drawer-open');
        },

        open: function () {
            this.$root.show().addClass('ads-drawer-open');
            this.$root.attr('aria-hidden', 'false');
            $('body').addClass('ads-drawer-active');
            this.render();
        },

        close: function () {
            this.$root.removeClass('ads-drawer-open');
            this.$root.attr('aria-hidden', 'true');
            $('body').removeClass('ads-drawer-active');
            setTimeout(() => {
                if (!this.isOpen()) {
                    this.$root.hide();
                }
            }, 360);
        },

        addItem: function (item) {
            // Rapid-fire debounce guard (prevents duplicate event firing within 500ms)
            this._lastAdd = this._lastAdd || { key: '', time: 0 };
            const now = Date.now();
            const unitAmtCheck = parseFloat(item.amount || item.unit_amount) || 0;
            const itemKey = `${item.project || ''}|${item.package || ''}|${unitAmtCheck}|${item.frequency || 'single'}`;
            if (this._lastAdd.key === itemKey && (now - this._lastAdd.time) < 500) {
                this.open();
                return;
            }
            this._lastAdd = { key: itemKey, time: now };

            const freq = (item.frequency === 'monthly') ? 'monthly' : 'single';
            const baskets = getActiveBasket();
            const list = baskets[freq];

            const unitAmt = parseFloat(item.amount || item.unit_amount) || 0;
            const incQty = parseInt(item.quantity, 10) || 1;
            const projName = item.project || 'General Donation';
            const pkgName = item.package || `${currency}${unitAmt} ${projName}`;

            // Check if exact same project & package already exists in basket
            let existingIdx = list.findIndex(function (it) {
                return (it.project === projName && it.package === pkgName);
            });

            if (existingIdx !== -1) {
                // Increment quantity
                list[existingIdx].quantity = (parseInt(list[existingIdx].quantity, 10) || 1) + incQty;
                list[existingIdx].amount = list[existingIdx].unit_amount * list[existingIdx].quantity;
                if (item.type) list[existingIdx].type = item.type;
                if (item.location) list[existingIdx].location = item.location;
            } else if (list.length === 1 && (!list[0].package || !list[0].is_explicit)) {
                // Replace default empty placeholder row
                list[0] = {
                    project: projName,
                    location: item.location || 'Afghanistan',
                    type: item.type || 'Sadaqah',
                    package: pkgName,
                    unit_amount: unitAmt,
                    amount: unitAmt * incQty,
                    quantity: incQty,
                    frequency: freq,
                    image: item.image || resolveProjectImage(projName, pkgName),
                    is_explicit: true
                };
            } else {
                // Add new item
                list.push({
                    project: projName,
                    location: item.location || 'Afghanistan',
                    type: item.type || 'Sadaqah',
                    package: pkgName,
                    unit_amount: unitAmt,
                    amount: unitAmt * incQty,
                    quantity: incQty,
                    frequency: freq,
                    image: item.image || resolveProjectImage(projName, pkgName),
                    is_explicit: true
                });
            }

            baskets[freq] = list;
            saveBaskets(baskets);
            syncWooCommerceCart();

            // Open the drawer smoothly
            this.open();
        },

        changeQuantity: function (freq, idx, delta) {
            const baskets = getActiveBasket();
            const list = baskets[freq];
            if (!list || !list[idx]) return;

            let currentQty = parseInt(list[idx].quantity, 10) || 1;
            let newQty = currentQty + delta;

            if (newQty <= 0) {
                this.removeItem(freq, idx);
                return;
            }

            list[idx].quantity = newQty;
            list[idx].amount = list[idx].unit_amount * newQty;
            baskets[freq] = list;
            saveBaskets(baskets);
            syncWooCommerceCart();
            this.render();
        },

        removeItem: function (freq, idx) {
            const self = this;
            const $itemRow = this.$itemsList.find(`.ads-drawer-item[data-freq="${freq}"][data-idx="${idx}"]`);

            $itemRow.addClass('ads-item-removing');

            setTimeout(function () {
                const baskets = getActiveBasket();
                if (baskets[freq] && baskets[freq][idx] !== undefined) {
                    baskets[freq].splice(idx, 1);
                    saveBaskets(baskets);
                    syncWooCommerceCart();
                }
                self.render();
            }, 220);
        },

        proceedToCheckout: function () {
            // Redirect to configured checkout / donate page
            const targetUrl = config.checkoutUrl || '/donate/';
            window.location.href = targetUrl;
        },

        render: function () {
            const baskets = getActiveBasket();
            const allItems = [];

            (baskets.single || []).forEach((it, i) => allItems.push({ item: it, freq: 'single', idx: i }));
            (baskets.monthly || []).forEach((it, i) => allItems.push({ item: it, freq: 'monthly', idx: i }));

            // Update Launcher Badge
            const totalCount = allItems.reduce((acc, curr) => acc + (curr.item.quantity || 1), 0);
            if (this.$launcherBadge) {
                this.$launcherBadge.text(totalCount);
            }
            if (this.$launcher) {
                if (totalCount > 0 && config.showFloatingIcon) {
                    this.$launcher.fadeIn(200);
                } else {
                    this.$launcher.fadeOut(200);
                }
            }

            // Check if cart is completely empty
            if (allItems.length === 0) {
                if (this.$root) this.$root.removeClass('ads-has-items').addClass('ads-is-empty');
                if (this.$panel) this.$panel.removeClass('ads-has-items').addClass('ads-is-empty');
                this.$itemsList.empty().hide();
                this.$emptyState.show();
                this.$footer.hide();
                return;
            }

            if (this.$root) this.$root.removeClass('ads-is-empty').addClass('ads-has-items');
            if (this.$panel) this.$panel.removeClass('ads-is-empty').addClass('ads-has-items');
            this.$emptyState.hide();
            this.$itemsList.empty().show();
            this.$footer.show();

            let singleTotal = 0;
            let monthlyTotal = 0;

            allItems.forEach(entry => {
                const it = entry.item;
                const freq = entry.freq;
                const idx = entry.idx;
                const lineTotal = (it.unit_amount || it.amount) * (it.quantity || 1);

                if (freq === 'monthly') {
                    monthlyTotal += lineTotal;
                } else {
                    singleTotal += lineTotal;
                }

                const freqLabel = (freq === 'monthly') ? 'Monthly Payment' : 'Single Payment';
                const metaText = `${freqLabel}, ${it.type || 'Sadaqah'}`;

                const removeLabel = config.removeText || 'Remove';
                const itemHtml = `
                    <div class="ads-drawer-item" data-freq="${freq}" data-idx="${idx}">
                        <div class="ads-drawer-item-info">
                            <h4 class="ads-drawer-item-title">${it.project}</h4>
                            <div class="ads-drawer-item-subtitle">${it.package}</div>
                            <div class="ads-drawer-item-meta">${metaText}</div>
                            <div class="ads-drawer-item-price">${formatMoney(lineTotal)}</div>
                        </div>
                        <div class="ads-drawer-item-actions">
                            ${config.showQty ? `
                            <div class="ads-drawer-stepper">
                                <span role="button" tabindex="0" class="ads-drawer-step-btn ads-drawer-step-minus" data-freq="${freq}" data-idx="${idx}" aria-label="Decrease quantity">&minus;</span>
                                <span class="ads-drawer-step-val">${it.quantity || 1}</span>
                                <span role="button" tabindex="0" class="ads-drawer-step-btn ads-drawer-step-plus" data-freq="${freq}" data-idx="${idx}" aria-label="Increase quantity">&plus;</span>
                            </div>` : ''}
                            ${config.showRemove ? `
                            <a href="#" class="ads-drawer-remove-link" data-freq="${freq}" data-idx="${idx}" role="button">${removeLabel}</a>
                            ` : ''}
                        </div>
                    </div>
                `;

                this.$itemsList.append(itemHtml);
            });

            // Update Summary Totals
            const dueToday = singleTotal + monthlyTotal;
            this.$singleTotal.text(formatMoney(singleTotal));

            if (monthlyTotal > 0) {
                this.$monthlyTotal.text(formatMoney(monthlyTotal) + ' / month');
                this.$footer.find('.ads-drawer-monthly-row').addClass('ads-has-monthly').css('display', 'flex');
            } else {
                this.$footer.find('.ads-drawer-monthly-row').removeClass('ads-has-monthly').css('display', 'none');
            }

            this.$dueTotal.text(formatMoney(dueToday));
        }
    };

    // Public API
    window.adsDrawer = Drawer;

    // =========================================================================
    // GLOBAL DONATION BUTTON DISPATCHER (HOOKS INTO ALL APPEAL & CARD BUTTONS)
    // =========================================================================
    function hookGlobalDonationButtons() {
        if (!config.enabled) return;

        // Catch clicks on donation buttons across all site pages
        $(document).on('click', '.hero-donate-pill, .ads-orphan-sponsor-btn, [class*="orphan"], [class*="water-well"], [class*="water-pump"], [class*="water-tank"], a[href*="donate"], .ads-custom-donate-btn, [data-ads-add-to-cart], .elementor-button', function (e) {
            // Do not intercept if click is already inside the checkout form, drawer, or dedicated widget cards handled by donation.js
            if ($(this).closest('#ads-donation-drawer-root, #ads-modern-donation-form, .ads-basket-rows-list, #ads-checkout-form, .ads-campaign-card, .ads-project-card, .ads-quick-hero-card, .ads-quick-bar').length > 0) {
                return;
            }

            // If user is directly on the /donate/ page, let them interact with the main form directly
            if (window.location.pathname.includes('/donate') && !$(this).hasClass('ads-force-drawer')) {
                return;
            }

            const $el = $(this);
            const text = $el.text().trim();
            let matchedAmount = $el.data('amount') || null;
            let matchedProject = $el.data('project') || null;
            let matchedPackage = $el.data('package') || null;
            let matchedFreq = $el.data('freq') || $el.attr('data-freq') || 'single';
            let matchedType = $el.data('type') || $el.attr('data-type') || 'Sadaqah';

            // Extract from URL if present
            if ($el.is('a') && $el.attr('href')) {
                try {
                    const u = new URL($el.attr('href'), window.location.origin);
                    if (!matchedProject) matchedProject = u.searchParams.get('project');
                    if (!matchedAmount) matchedAmount = u.searchParams.get('amount');
                    if (!matchedPackage) matchedPackage = u.searchParams.get('package');
                    if (!matchedFreq || matchedFreq === 'single') {
                        const qFreq = u.searchParams.get('freq') || u.searchParams.get('frequency');
                        if (qFreq) matchedFreq = qFreq;
                    }
                    if (!matchedType || matchedType === 'Sadaqah') {
                        const qType = u.searchParams.get('type');
                        if (qType) matchedType = qType;
                    }
                } catch (err) {}
            }

            // Extract numeric amount directly from text if available (e.g. "£120 For A Winter Pack" -> 120)
            if (!matchedAmount) {
                const textAmtMatch = text.match(/(?:£|\$|€)?\s*(\d+(?:\.\d+)?)/);
                if (textAmtMatch) {
                    const parsedNum = parseFloat(textAmtMatch[1]);
                    if (parsedNum > 0) matchedAmount = parsedNum;
                }
            }

            // Keyword heuristics
            const combinedContext = (text + ' ' + (matchedPackage || '') + ' ' + (matchedProject || '')).toLowerCase();
            if (combinedContext.includes('winter pack') || (combinedContext.includes('winter') && matchedAmount === 120)) {
                matchedProject = 'Winter Pack';
                if (!matchedAmount) matchedAmount = 120;
                matchedPackage = 'Winter Pack';
            } else if (combinedContext.includes('emergency shelter') || combinedContext.includes('shelter') || (combinedContext.includes('winter') && matchedAmount === 130)) {
                matchedProject = 'Emergency Shelter - Family Sized Tent';
                if (!matchedAmount) matchedAmount = 130;
                matchedPackage = '£130 Emergency Shelter';
            } else if (combinedContext.includes('zakat') && combinedContext.includes('winter')) {
                matchedProject = 'Winter Emergency Appeal';
                if (!matchedAmount) matchedAmount = 100;
                matchedPackage = 'Winter Zakat or Sadaqah';
            } else if (combinedContext.includes('orphan') || combinedContext.includes('300') || combinedContext.includes('336')) {
                matchedAmount = matchedAmount || 300;
                if (!matchedProject) matchedProject = 'Orphan Sponsorship';
                if (!matchedPackage) matchedPackage = '£300 Sponsors An Orphan For One Year';
            } else if (combinedContext.includes('1200') || combinedContext.includes('water well')) {
                matchedAmount = matchedAmount || 1200;
                if (!matchedProject) matchedProject = 'Water - Well';
                if (!matchedPackage) matchedPackage = '£1200 Water Well';
            } else if (combinedContext.includes('600') || combinedContext.includes('water pump')) {
                matchedAmount = matchedAmount || 600;
                if (!matchedProject) matchedProject = 'Water - Pump';
                if (!matchedPackage) matchedPackage = '£600 Water Pump';
            } else if (combinedContext.includes('3600') || combinedContext.includes('water tank')) {
                matchedAmount = matchedAmount || 3600;
                if (!matchedProject) matchedProject = 'Water Tank - Community Well';
                if (!matchedPackage) matchedPackage = '£3600 Community Water Tank';
            } else if (combinedContext.includes('70') || combinedContext.includes('food pack')) {
                matchedAmount = matchedAmount || 70;
                if (!matchedProject) matchedProject = 'Food Pack';
                if (!matchedPackage) matchedPackage = 'Emergency Food Pack';
            }

            matchedAmount = parseFloat(matchedAmount) || 0;
            if (matchedAmount > 0) {
                e.preventDefault();
                e.stopPropagation();

                if (!matchedProject) matchedProject = 'General Donation';
                if (!matchedPackage) matchedPackage = `${currency}${matchedAmount} ${matchedProject}`;

                Drawer.addItem({
                    project: matchedProject,
                    package: matchedPackage,
                    amount: matchedAmount,
                    unit_amount: matchedAmount,
                    quantity: 1,
                    type: matchedType || 'Sadaqah',
                    frequency: matchedFreq || 'single',
                    location: 'Afghanistan'
                });
            }
        });
    }

    // Initialize on DOM Ready
    $(document).ready(function () {
        Drawer.init();
        hookGlobalDonationButtons();
    });

})(jQuery);

