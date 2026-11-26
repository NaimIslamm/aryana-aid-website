/**
 * Aryana Aid Donation System JS Engine
 * Handles cascading selects, multi-item basket, URL parameter auto-filling,
 * live totals, gift aid, admin fee, and gateway dispatch.
 */

(function ($) {
    'use strict';

    window.ADS = window.ADS || {};

    const matrix = window.adsConfig ? window.adsConfig.matrix : {};
    const currency = window.adsConfig ? window.adsConfig.currency : '£';
    const adminFeePct = window.adsConfig ? parseFloat(window.adsConfig.adminFeePct) : 1.10;
    const checkoutUrl = window.adsConfig ? window.adsConfig.checkoutUrl : '/donate/';

    // ==========================================================================
    // 1. MASTER DONATION FORM (/donate/)
    // ==========================================================================
    class MasterDonationForm {
        constructor($container) {
            if ($container.data('ads-initialized')) return;
            $container.data('ads-initialized', true);
            this.$container = $container;
            this.mode = 'single'; // 'single' or 'monthly'
            this.rowIndex = 0;
            this.init();
        }

        init() {
            this.bindEvents();
            this.checkUrlParams();
            this.recalculateTotal();
        }

        bindEvents() {
            const self = this;

            // Frequency Tabs
            this.$container.on('click', '.ads-tab-btn', function () {
                self.$container.find('.ads-tab-btn').removeClass('active');
                $(this).addClass('active');
                self.mode = $(this).data('freq');
                self.updateBasketMode();
                self.recalculateTotal();
            });

            // Project Dropdown Change
            this.$container.on('change', '.ads-select-project', function () {
                const $row = $(this).closest('.ads-basket-row');
                self.handleProjectChange($row);
                self.recalculateTotal();
            });

            // Location Change
            this.$container.on('change', '.ads-select-location', function () {
                const $row = $(this).closest('.ads-basket-row');
                self.handleLocationChange($row);
            });

            // Amount Input Change
            this.$container.on('input change', '.ads-amount-input', function () {
                self.recalculateTotal();
            });

            // Add Row
            this.$container.on('click', '.ads-btn-add', function (e) {
                e.preventDefault();
                self.addRow();
            });

            // Remove Row
            this.$container.on('click', '.ads-btn-remove', function (e) {
                e.preventDefault();
                const $rows = self.$container.find('.ads-basket-row');
                if ($rows.length > 1) {
                    $(this).closest('.ads-basket-row').remove();
                    self.recalculateTotal();
                } else {
                    alert('At least one donation project is required.');
                }
            });

            // Admin Fee Checkbox
            this.$container.on('change', '#ads-admin-fee-check', function () {
                const checked = $(this).is(':checked');
                $(this).closest('.ads-addon-box').toggleClass('active', checked);
                self.recalculateTotal();
            });

            // Gift Aid Checkbox
            this.$container.on('change', '#ads-giftaid-check', function () {
                const checked = $(this).is(':checked');
                $(this).closest('.ads-addon-box').toggleClass('active', checked);
                self.recalculateTotal();
            });

            // Payment Method Selection
            this.$container.on('click', '.ads-pay-method-btn', function () {
                self.$container.find('.ads-pay-method-btn').removeClass('active');
                $(this).addClass('active');
                const method = $(this).data('method');
                if (method === 'card') {
                    self.$container.find('.ads-card-inputs-box').slideDown(200);
                    self.$container.find('.ads-gocardless-box').slideUp(200);
                } else if (method === 'paypal') {
                    self.$container.find('.ads-card-inputs-box').slideUp(200);
                    self.$container.find('.ads-gocardless-box').slideUp(200);
                } else if (method === 'gocardless') {
                    self.$container.find('.ads-card-inputs-box').slideUp(200);
                    self.$container.find('.ads-gocardless-box').slideDown(200);
                }
            });

            // Form Submit
            this.$container.on('submit', '#ads-checkout-form', function (e) {
                e.preventDefault();
                self.handleCheckoutSubmit();
            });
        }

        handleProjectChange($row) {
            const project = $row.find('.ads-select-project').val();
            const data = matrix[project];
            const $locSelect = $row.find('.ads-select-location');
            const $typeSelect = $row.find('.ads-select-type');
            const $amountInput = $row.find('.ads-amount-input');

            if (!data) return;

            // Populate Locations
            $locSelect.empty();
            (data.locations || []).forEach(loc => {
                $locSelect.append(`<option value="${loc}">${loc}</option>`);
            });

            // Populate Types
            $typeSelect.empty();
            (data.types || []).forEach(t => {
                $typeSelect.append(`<option value="${t}">${t}</option>`);
            });

            // Set Price
            let price = (this.mode === 'monthly' && data.fixed_monthly > 0) ? data.fixed_monthly : data.fixed_single;
            if (price > 0) {
                $amountInput.val(price);
            } else {
                if (!$amountInput.val() || parseFloat($amountInput.val()) === 0) {
                    $amountInput.val('');
                    $amountInput.attr('placeholder', 'Enter amount');
                }
            }
        }

        handleLocationChange($row) {
            // Can filter donation types by location if needed
        }

        updateBasketMode() {
            const self = this;
            this.$container.find('.ads-basket-row').each(function () {
                self.handleProjectChange($(this));
            });

            // If monthly, show GoCardless Direct Debit option
            if (this.mode === 'monthly') {
                this.$container.find('.ads-method-gocardless').show();
            } else {
                this.$container.find('.ads-method-gocardless').hide();
                if (this.$container.find('.ads-method-gocardless').hasClass('active')) {
                    this.$container.find('.ads-method-card').trigger('click');
                }
            }
        }

        addRow(initialData = null) {
            this.rowIndex++;
            const firstProject = Object.keys(matrix)[0] || '';
            const rowHtml = `
                <div class="ads-basket-row" data-row="${this.rowIndex}">
                    <div class="ads-control-wrap ads-field-project">
                        <label>Project</label>
                        <select class="ads-select ads-select-project" name="items[${this.rowIndex}][project]">
                            ${Object.keys(matrix).map(p => `<option value="${p}">${p}</option>`).join('')}
                        </select>
                    </div>
                    <div class="ads-control-wrap ads-field-location">
                        <label>Location</label>
                        <select class="ads-select ads-select-location" name="items[${this.rowIndex}][location]"></select>
                    </div>
                    <div class="ads-control-wrap ads-field-type">
                        <label>Donation Type</label>
                        <select class="ads-select ads-select-type" name="items[${this.rowIndex}][type]"></select>
                    </div>
                    <div class="ads-control-wrap ads-field-amount">
                        <label>Amount</label>
                        <div class="ads-amount-input-wrap">
                            <span class="ads-amount-prefix">${currency}</span>
                            <input type="number" step="1" min="1" class="ads-input ads-amount-input" name="items[${this.rowIndex}][amount]" placeholder="0">
                        </div>
                    </div>
                    <div class="ads-control-wrap ads-field-comments">
                        <label>Comments (optional)</label>
                        <input type="text" class="ads-input ads-comments-input" name="items[${this.rowIndex}][comments]" placeholder="e.g. In memory of...">
                    </div>
                    <div class="ads-control-wrap ads-row-remove">
                        <button type="button" class="ads-btn-remove" title="Remove">&times;</button>
                    </div>
                </div>
            `;

            const $row = $(rowHtml);
            this.$container.find('.ads-basket-rows-container').append($row);

            if (initialData && initialData.project) {
                $row.find('.ads-select-project').val(initialData.project);
            }
            this.handleProjectChange($row);

            if (initialData) {
                if (initialData.location) $row.find('.ads-select-location').val(initialData.location);
                if (initialData.type) $row.find('.ads-select-type').val(initialData.type);
                if (initialData.amount) $row.find('.ads-amount-input').val(initialData.amount);
            }

            this.recalculateTotal();
            return $row;
        }

        recalculateTotal() {
            let subtotal = 0;
            this.$container.find('.ads-amount-input').each(function () {
                const val = parseFloat($(this).val()) || 0;
                subtotal += val;
            });

            // Admin fee
            let fee = 0;
            const feeChecked = this.$container.find('#ads-admin-fee-check').is(':checked');
            if (feeChecked && subtotal > 0) {
                fee = 1.10;
            }

            const grandTotal = subtotal + fee;

            // Gift aid calculation (+25%)
            const giftAidEst = subtotal * 0.25;

            // Update UI
            this.$container.find('.ads-val-subtotal').text(`${currency}${subtotal.toFixed(2)}`);
            this.$container.find('.ads-val-fee').text(`${currency}${fee.toFixed(2)}`);
            this.$container.find('.ads-val-grandtotal').text(`${currency}${grandTotal.toFixed(2)}`);
            this.$container.find('.ads-val-giftaid-amount').text(`${currency}${giftAidEst.toFixed(2)}`);

            // Monthly suffix
            const freqLabel = this.mode === 'monthly' ? ' / month' : '';
            this.$container.find('.ads-grand-total-suffix').text(freqLabel);
        }

        checkUrlParams() {
            const params = new URLSearchParams(window.location.search);
            const projectParam = params.get('project');
            const amountParam = params.get('amount');
            const typeParam = params.get('type');
            const freqParam = params.get('freq');
            const locParam = params.get('location');

            if (freqParam === 'monthly') {
                this.$container.find('.ads-tab-btn[data-freq="monthly"]').trigger('click');
            }

            if (projectParam || amountParam) {
                // Clear initial placeholder rows and create matched row
                this.$container.find('.ads-basket-rows-container').empty();
                
                // Match project case-insensitively or partial
                let matchedProject = Object.keys(matrix).find(p => p.toLowerCase() === (projectParam || '').toLowerCase());
                if (!matchedProject) {
                    matchedProject = Object.keys(matrix).find(p => p.toLowerCase().includes((projectParam || '').toLowerCase()));
                }

                this.addRow({
                    project: matchedProject || Object.keys(matrix)[0],
                    amount: amountParam,
                    type: typeParam,
                    location: locParam
                });

                // Scroll smoothly to form
                $('html, body').animate({
                    scrollTop: this.$container.offset().top - 80
                }, 600);
            } else if (this.$container.find('.ads-basket-row').length === 0) {
                this.addRow();
            }
        }

        handleCheckoutSubmit() {
            const self = this;
            const grandTotalText = this.$container.find('.ads-val-grandtotal').text();
            const grandTotal = parseFloat(grandTotalText.replace(/[^0-9.]/g, '')) || 0;

            if (grandTotal <= 0) {
                alert('Please enter a valid donation amount.');
                return;
            }

            const activeMethod = this.$container.find('.ads-pay-method-btn.active').data('method') || 'card';

            if (activeMethod === 'paypal') {
                const paypalEmail = window.adsConfig ? window.adsConfig.paypalEmail : 'paypal@aryanaaid.org.uk';
                const firstProj = this.$container.find('.ads-select-project').first().val() || 'Donation';
                const paypalUrl = `https://www.paypal.com/cgi-bin/webscr?cmd=_xclick&business=${encodeURIComponent(paypalEmail)}&item_name=${encodeURIComponent('Donation - ' + firstProj)}&amount=${grandTotal.toFixed(2)}&currency_code=GBP&no_shipping=1`;
                window.location.href = paypalUrl;
                return;
            }

            // Transfer to donation checkout
            const firstProj = this.$container.find('.ads-select-project').first().val() || 'General Donation';
            window.location.href = `${checkoutUrl}?project=${encodeURIComponent(firstProj)}&amount=${grandTotal.toFixed(2)}&freq=${this.mode}`;
        }
    }


    // ==========================================================================
    // 2. PROJECT DONATION CARD (IMAGE 1)
    // ==========================================================================
    class ProjectDonationCard {
        constructor($card) {
            if ($card.data('ads-initialized')) return;
            $card.data('ads-initialized', true);
            this.$card = $card;
            this.project = $card.data('project') || 'Water - Well';
            this.mode = 'single';
            this.currentAmount = 0;
            this.init();
        }

        init() {
            const self = this;

            // Frequency Toggle
            this.$card.find('.ads-card-freq-btn').off('click').on('click', function () {
                self.$card.find('.ads-card-freq-btn').removeClass('active');
                $(this).addClass('active');
                self.mode = $(this).data('freq');
            });

            // Preset Amount Pills Click
            this.$card.find('.ads-card-pill').off('click').on('click', function () {
                self.$card.find('.ads-card-pill').removeClass('active');
                $(this).addClass('active');

                const amount = $(this).data('amount');
                const impact = $(this).data('impact');
                self.currentAmount = amount;

                // Clear other input
                self.$card.find('.ads-card-other-input').val('');

                // Update impact text box with smooth fade
                const $box = self.$card.find('.ads-card-impact-box');
                $box.fadeOut(150, function () {
                    $box.text(impact || 'Your generous support brings urgent, life-saving relief.').fadeIn(150);
                });
            });

            // Custom Other Amount Input
            this.$card.find('.ads-card-other-input').off('input focus').on('input focus', function () {
                const val = parseFloat($(this).val()) || 0;
                if (val > 0) {
                    self.$card.find('.ads-card-pill').removeClass('active');
                    self.currentAmount = val;
                    self.$card.find('.ads-card-impact-box').text(`Your donation of ${currency}${val} makes a direct, lasting difference to families in need.`);
                }
            });

            // Donate Button Click
            this.$card.find('.ads-card-donate-btn').off('click').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                let amount = self.currentAmount;
                const otherVal = parseFloat(self.$card.find('.ads-card-other-input').val());
                if (otherVal > 0) amount = otherVal;

                if (!amount || amount <= 0) {
                    alert('Please select or enter a donation amount.');
                    return;
                }

                const donationType = self.$card.find('.ads-card-select').val() || 'Sadaqah';
                const redirect = `${checkoutUrl}?project=${encodeURIComponent(self.project)}&amount=${amount}&type=${encodeURIComponent(donationType)}&freq=${self.mode}`;
                if (window.adsDrawer && typeof window.adsDrawer.addItem === 'function') {
                    window.adsDrawer.addItem({
                        project: self.project,
                        package: `${currency}${amount} ${self.project}`,
                        amount: amount,
                        type: donationType,
                        frequency: self.mode,
                        location: 'Afghanistan'
                    });
                } else {
                    window.location.href = redirect;
                }
            });

            // Activate First Pill by default
            this.$card.find('.ads-card-pill').first().trigger('click');

            // Wire up external project buttons on page (e.g. £1200 WATER WELL, £600 WATER PUMP)
            this.bindExternalButtons();
        }

        bindExternalButtons() {
            // Handled globally via bindGlobalDonationButtons()
        }
    }


    // ==========================================================================
    // GLOBAL EXTERNAL DONATION BUTTON DISPATCHER
    // ==========================================================================
    function bindGlobalDonationButtons() {
        // If slide-out drawer engine is active, global button clicks are handled centrally in donation-drawer.js
        if (window.adsDrawerConfig && window.adsDrawerConfig.enabled) {
            return;
        }

        $(document).on('click', '.hero-donate-pill, [class*="water-well"], [class*="water-pump"], [class*="water-tank"], a[href*="donate"], .ads-custom-donate-btn, [data-amount]', function (e) {
            // Never intercept clicks inside checkout form or card internal controls
            if ($(this).closest('#ads-modern-donation-form, .ads-master-container, .ads-basket-item-row, .ads-card-pills, .ads-campaign-pills-grid').length > 0) {
                return;
            }

            const $el = $(this);
            const text = $el.text().trim();
            let matchedAmount = $el.data('amount') || null;
            let matchedProject = $el.data('project') || null;
            let matchedPackage = $el.data('package') || null;
            let matchedFreq = $el.data('freq') || $el.attr('data-freq') || '';
            let matchedType = $el.data('type') || $el.attr('data-type') || '';

            // Extract from href if present on an <a> tag
            if ($el.is('a') && $el.attr('href')) {
                try {
                    const u = new URL($el.attr('href'), window.location.origin);
                    if (!matchedFreq) {
                        matchedFreq = u.searchParams.get('freq') || u.searchParams.get('frequency') || '';
                    }
                    if (!matchedProject) {
                        matchedProject = u.searchParams.get('project') || '';
                    }
                    if (!matchedAmount) {
                        matchedAmount = u.searchParams.get('amount') || '';
                    }
                    if (!matchedPackage) {
                        matchedPackage = u.searchParams.get('package') || '';
                    }
                    if (!matchedType) {
                        matchedType = u.searchParams.get('type') || '';
                    }
                } catch(err) {}
            }

            if (!matchedFreq) {
                matchedFreq = 'single';
            }

            if (!matchedAmount) {
                if (text.includes('1200') || text.toLowerCase().includes('water well')) {
                    matchedAmount = 1200;
                } else if (text.includes('600') || text.toLowerCase().includes('water pump')) {
                    matchedAmount = 600;
                } else if (text.includes('3600') || text.toLowerCase().includes('tank')) {
                    matchedAmount = 3600;
                }
            }

            // Always ensure the project correctly matches the preset amount & package
            const combinedContext = (text + ' ' + (matchedPackage || '') + ' ' + (matchedProject || '')).toLowerCase();
            const numericAmt = parseFloat(matchedAmount) || 0;
            const isWaterRelated = combinedContext.includes('water') || combinedContext.includes('pump') || combinedContext.includes('well') || combinedContext.includes('tank');

            if (isWaterRelated) {
                if (combinedContext.includes('tank') || numericAmt === 3600) {
                    matchedProject = 'Water Tank - Community Well';
                    if (!matchedPackage) matchedPackage = '£3600 Community Water Tank';
                } else if (combinedContext.includes('pump') || numericAmt === 600) {
                    matchedProject = 'Water - Pump';
                    if (!matchedPackage) matchedPackage = '£600 Water Pump';
                } else if (combinedContext.includes('well') || numericAmt === 1200) {
                    matchedProject = 'Water - Well';
                    if (!matchedPackage) matchedPackage = '£1200 Water Well';
                }
            }

            if (matchedAmount || matchedProject) {
                e.preventDefault();
                if (!matchedProject) {
                    matchedProject = matchedPackage || text || 'General Donation';
                }
                if (!matchedPackage) {
                    matchedPackage = matchedAmount ? `${currency}${matchedAmount} Donation` : matchedProject;
                }

                let targetUrl = `${checkoutUrl}?project=${encodeURIComponent(matchedProject)}&package=${encodeURIComponent(matchedPackage)}&freq=${encodeURIComponent(matchedFreq)}`;
                if (matchedAmount) {
                    targetUrl += `&amount=${matchedAmount}`;
                }
                if (matchedType) {
                    targetUrl += `&type=${encodeURIComponent(matchedType)}`;
                }
                if (window.adsDrawer && typeof window.adsDrawer.addItem === 'function') {
                    window.adsDrawer.addItem({
                        project: matchedProject,
                        package: matchedPackage,
                        amount: parseFloat(matchedAmount) || 50,
                        type: matchedType || 'Sadaqah',
                        frequency: matchedFreq || 'single',
                        location: 'Afghanistan'
                    });
                } else {
                    window.location.href = targetUrl;
                }
            }
        });
    }


    // ==========================================================================
    // 3. QUICK DONATE HERO WIDGET (TWO-BOX ENGINE)
    // ==========================================================================
    class QuickDonateBar {
        constructor($bar) {
            if ($bar.data('ads-initialized')) return;
            $bar.data('ads-initialized', true);
            this.$bar = $bar;
            this.mode = $bar.find('.ads-quick-freq-btn.active').data('freq') || 'single';
            this.init();
        }

        init() {
            const self = this;

            // Frequency switcher (Single Payment vs Regular Payment)
            this.$bar.find('.ads-quick-freq-btn').off('click').on('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.$bar.find('.ads-quick-freq-btn').removeClass('active');
                $(this).addClass('active');
                self.mode = $(this).data('freq') || 'single';
                self.updatePriceAndTypes();
            });

            // Project Selector Change
            this.$bar.find('.ads-quick-select-project').off('change').on('change', function () {
                self.updatePriceAndTypes();
            });

            // Donate Now submit button (.ads-quick-btn or .ads-quick-submit-btn)
            this.$bar.find('.ads-quick-btn, .ads-quick-submit-btn').off('click').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                const project = self.$bar.find('.ads-quick-select-project').val() || 'Water - Well';
                const amount = parseFloat(self.$bar.find('.ads-quick-amount-input').val()) || 0;
                const type = self.$bar.find('.ads-quick-select-type').val() || 'Sadaqah';

                if (amount <= 0) {
                    alert('Please enter a valid donation amount.');
                    return;
                }

                // Infer package attribute for clear attribution on the donation page
                let packageTag = '';
                const isWater = project.toLowerCase().includes('water') || project.toLowerCase().includes('pump') || project.toLowerCase().includes('well') || project.toLowerCase().includes('tank');
                const freqSuffix = (self.mode === 'monthly') ? '/mo' : '';

                if (isWater) {
                    if (amount === 600) {
                        packageTag = '£600 Water Pump';
                    } else if (amount === 1200) {
                        packageTag = '£1200 Water Well';
                    } else if (amount === 3600) {
                        packageTag = '£3600 Community Water Tank';
                    } else {
                        packageTag = `${currency}${amount}${freqSuffix} ${project}`;
                    }
                } else {
                    packageTag = `${currency}${amount}${freqSuffix} ${project}`;
                }

                const targetUrl = `${checkoutUrl}?project=${encodeURIComponent(project)}&amount=${amount}&type=${encodeURIComponent(type)}&freq=${self.mode}&package=${encodeURIComponent(packageTag)}&from=quick_donate`;
                if (window.adsDrawer && typeof window.adsDrawer.addItem === 'function') {
                    window.adsDrawer.addItem({
                        project: project,
                        package: packageTag,
                        amount: amount,
                        type: type,
                        frequency: self.mode,
                        location: 'Afghanistan'
                    });
                } else {
                    window.location.href = targetUrl;
                }
            });

            // Initial trigger
            this.updatePriceAndTypes();
        }

        updatePriceAndTypes() {
            const project = this.$bar.find('.ads-quick-select-project').val();
            const data = matrix[project];
            if (!data) return;

            let price = (this.mode === 'monthly') ? (data.fixed_monthly || 0) : (data.fixed_single || 0);
            if (price <= 0) {
                price = (this.mode === 'monthly') ? 25 : 100;
            }
            this.$bar.find('.ads-quick-amount-input').val(price);

            const $type = this.$bar.find('.ads-quick-select-type');
            const currentSelected = $type.val();
            $type.empty();
            (data.types || ['Sadaqah', 'Zakat', 'Lillah']).forEach(t => {
                const isSel = (t === currentSelected) ? 'selected' : '';
                $type.append(`<option value="${t}" ${isSel}>${t}</option>`);
            });
        }
    }

    // Delegated Global Handlers for Quick Donate (guarantees execution across dynamic Elementor / mobile accordion renders)
    $(document).on('click', '.ads-quick-hero-card .ads-quick-freq-btn, .ads-quick-bar .ads-quick-freq-btn', function (e) {
        e.preventDefault();
        const $card = $(this).closest('.ads-quick-hero-card, .ads-quick-bar');
        $card.find('.ads-quick-freq-btn').removeClass('active');
        $(this).addClass('active');
        const mode = $(this).data('freq') || 'single';
        const project = $card.find('.ads-quick-select-project').val();
        const data = matrix[project];
        if (data) {
            let price = (mode === 'monthly') ? (data.fixed_monthly || 0) : (data.fixed_single || 0);
            if (price <= 0) price = (mode === 'monthly') ? 25 : 100;
            $card.find('.ads-quick-amount-input').val(price);
        }
    });

    $(document).on('change', '.ads-quick-hero-card .ads-quick-select-project, .ads-quick-bar .ads-quick-select-project', function () {
        const $card = $(this).closest('.ads-quick-hero-card, .ads-quick-bar');
        const mode = $card.find('.ads-quick-freq-btn.active').data('freq') || 'single';
        const project = $(this).val();
        const data = matrix[project];
        if (!data) return;

        let price = (mode === 'monthly') ? (data.fixed_monthly || 0) : (data.fixed_single || 0);
        if (price <= 0) price = (mode === 'monthly') ? 25 : 100;
        $card.find('.ads-quick-amount-input').val(price);

        const $type = $card.find('.ads-quick-select-type');
        const currentSelected = $type.val();
        $type.empty();
        (data.types || ['Sadaqah', 'Zakat', 'Lillah']).forEach(t => {
            const isSel = (t === currentSelected) ? 'selected' : '';
            $type.append(`<option value="${t}" ${isSel}>${t}</option>`);
        });
    });

    $(document).on('click', '.ads-quick-hero-card .ads-quick-submit-btn, .ads-quick-hero-card .ads-quick-btn, .ads-quick-bar .ads-quick-submit-btn, .ads-quick-bar .ads-quick-btn', function (e) {
        e.preventDefault();
        const $btn = $(this);
        if ($btn.data('ads-submitting')) return;
        $btn.data('ads-submitting', true);
        setTimeout(() => $btn.removeData('ads-submitting'), 600);

        const $card = $(this).closest('.ads-quick-hero-card, .ads-quick-bar');
        const project = $card.find('.ads-quick-select-project').val() || 'Water - Well';
        const amount = parseFloat($card.find('.ads-quick-amount-input').val()) || 0;
        const type = $card.find('.ads-quick-select-type').val() || 'Sadaqah';
        const mode = $card.find('.ads-quick-freq-btn.active').data('freq') || 'single';

        if (amount <= 0) {
            alert('Please enter a valid donation amount.');
            return;
        }

        let packageTag = '';
        const isWater = project.toLowerCase().includes('water') || project.toLowerCase().includes('pump') || project.toLowerCase().includes('well') || project.toLowerCase().includes('tank');
        const freqSuffix = (mode === 'monthly') ? '/mo' : '';

        if (isWater) {
            if (amount === 600) {
                packageTag = '£600 Water Pump';
            } else if (amount === 1200) {
                packageTag = '£1200 Water Well';
            } else if (amount === 3600) {
                packageTag = '£3600 Community Water Tank';
            } else {
                packageTag = `${currency}${amount}${freqSuffix} ${project}`;
            }
        } else {
            packageTag = `${currency}${amount}${freqSuffix} ${project}`;
        }

        const targetUrl = `${checkoutUrl}?project=${encodeURIComponent(project)}&amount=${amount}&type=${encodeURIComponent(type)}&freq=${mode}&package=${encodeURIComponent(packageTag)}&from=quick_donate`;
        if (window.adsDrawer && typeof window.adsDrawer.addItem === 'function') {
            window.adsDrawer.addItem({
                project: project,
                package: packageTag,
                amount: amount,
                type: type,
                frequency: mode,
                location: 'Afghanistan'
            });
        } else {
            window.location.href = targetUrl;
        }
    });

    $(document).on('keydown', '.ads-quick-amount-input', function (e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            $(this).closest('.ads-quick-hero-card, .ads-quick-bar').find('.ads-quick-submit-btn, .ads-quick-btn').first().trigger('click');
        }
    });


    // ==========================================================================
    // 4. CAMPAIGN DONATION BOX ENGINE
    // ==========================================================================
    class CampaignDonationCard {
        constructor($card) {
            if ($card.data('ads-initialized')) return;
            $card.data('ads-initialized', true);
            this.$card = $card;
            this.campaignId = $card.data('campaign-id') || '';
            this.project = $card.data('project') || 'General Donation';
            this.enableSingle = $card.data('enable-single') !== 'no';
            this.enableMonthly = $card.data('enable-monthly') === 'yes';
            this.mode = $card.data('default-mode') || (this.enableSingle ? 'single' : 'monthly');
            this.currency = $card.data('currency') || (window.adsConfig ? window.adsConfig.currency : '£');
            this.currencyCode = $card.data('currency-code') || (window.adsConfig ? window.adsConfig.currencyCode : 'GBP');
            this.currentAmount = 0;
            this.currentPackage = '';
            this.init();
        }

        init() {
            const self = this;

            // Frequency Toggle (Give Once vs Give Monthly)
            this.$card.find('.ads-campaign-freq-btn').off('click').on('click', function () {
                self.$card.find('.ads-campaign-freq-btn').removeClass('active');
                $(this).addClass('active');
                self.mode = $(this).data('freq'); // 'single' or 'monthly'

                if (self.mode === 'monthly') {
                    self.$card.find('.ads-pills-grid-single').hide();
                    self.$card.find('.ads-pills-grid-monthly').css('display', 'grid');
                    const $activeMonthly = self.$card.find('.ads-pills-grid-monthly .ads-campaign-pill.active').length ?
                        self.$card.find('.ads-pills-grid-monthly .ads-campaign-pill.active') :
                        self.$card.find('.ads-pills-grid-monthly .ads-campaign-pill').first();
                    $activeMonthly.trigger('click');
                } else {
                    self.$card.find('.ads-pills-grid-monthly').hide();
                    self.$card.find('.ads-pills-grid-single').css('display', 'grid');
                    const $activeSingle = self.$card.find('.ads-pills-grid-single .ads-campaign-pill.active').length ?
                        self.$card.find('.ads-pills-grid-single .ads-campaign-pill.active') :
                        self.$card.find('.ads-pills-grid-single .ads-campaign-pill').first();
                    $activeSingle.trigger('click');
                }
            });

            // Preset Amount Pills Click (Support both Single & Monthly grids)
            this.$card.off('click', '.ads-campaign-pill').on('click', '.ads-campaign-pill', function () {
                const $grid = $(this).closest('.ads-campaign-pills-grid');
                $grid.find('.ads-campaign-pill').removeClass('active');
                $(this).addClass('active');

                const amount = parseFloat($(this).data('amount')) || 0;
                const impact = $(this).data('impact');
                const pkg = $(this).data('package') || '';

                self.currentAmount = amount;
                self.currentPackage = pkg;

                // Clear other custom input
                self.$card.find('.ads-campaign-other-input').val('');
                self.$card.find('.ads-campaign-other-row').removeClass('focused');

                // Update impact box with smooth transition
                const $box = self.$card.find('.ads-campaign-impact-box');
                $box.css('opacity', '0.4');
                setTimeout(function () {
                    $box.text(impact || 'Your generous donation delivers immediate life-saving aid.').css('opacity', '1');
                }, 120);
            });

            // Custom Other Amount Input
            this.$card.find('.ads-campaign-other-input')
                .off('focus blur input')
                .on('focus', function () {
                    self.$card.find('.ads-campaign-other-row').addClass('focused');
                }).on('blur', function () {
                    if (!$(this).val()) {
                        self.$card.find('.ads-campaign-other-row').removeClass('focused');
                    }
                }).on('input', function () {
                    const val = parseFloat($(this).val()) || 0;
                    if (val > 0) {
                        self.$card.find('.ads-campaign-pill').removeClass('active');
                        self.currentAmount = val;
                        const freqSuffix = (self.mode === 'monthly') ? '/mo' : '';
                        self.currentPackage = `${currency}${val}${freqSuffix} Custom Donation`;
                        const freqWord = (self.mode === 'monthly') ? 'regular monthly' : 'one-off';
                        self.$card.find('.ads-campaign-impact-box').text(`Your ${freqWord} donation of ${currency}${val} empowers families with direct, sustainable relief.`);
                    }
                });

            // Donate Button Click
            this.$card.find('.ads-campaign-donate-btn').off('click').on('click', function (e) {
                e.preventDefault();
                e.stopPropagation();
                let amount = self.currentAmount;
                const otherVal = parseFloat(self.$card.find('.ads-campaign-other-input').val());
                if (otherVal > 0) {
                    amount = otherVal;
                    const freqSuffix = (self.mode === 'monthly') ? '/mo' : '';
                    self.currentPackage = `${self.currency}${otherVal}${freqSuffix} Custom Donation`;
                }

                if (!amount || amount <= 0) {
                    alert('Please select or enter a donation amount.');
                    return;
                }

                const donationType = self.$card.find('.ads-campaign-type-select').val() || 'General Donation';
                const donationLocation = self.$card.find('.ads-campaign-location-select').val() || 'Where most needed';
                let redirect = `${checkoutUrl}?project=${encodeURIComponent(self.project)}&amount=${amount}&type=${encodeURIComponent(donationType)}&location=${encodeURIComponent(donationLocation)}&freq=${self.mode}&currency_code=${encodeURIComponent(self.currencyCode)}&currency=${encodeURIComponent(self.currency)}`;
                if (self.currentPackage) {
                    redirect += `&package=${encodeURIComponent(self.currentPackage)}`;
                }
                if (self.campaignId) {
                    redirect += `&campaign_id=${encodeURIComponent(self.campaignId)}`;
                }
                if (window.adsDrawer && typeof window.adsDrawer.addItem === 'function') {
                    window.adsDrawer.addItem({
                        project: self.project,
                        package: self.currentPackage || `${self.currency}${amount} ${self.project}`,
                        amount: amount,
                        type: donationType,
                        frequency: self.mode,
                        location: donationLocation
                    });
                } else {
                    window.location.href = redirect;
                }
            });

            // Trigger click on first active pill in currently active grid
            const $targetGrid = (this.mode === 'monthly') ? this.$card.find('.ads-pills-grid-monthly') : this.$card.find('.ads-pills-grid-single');
            const $activePill = $targetGrid.find('.ads-campaign-pill.active').length ?
                $targetGrid.find('.ads-campaign-pill.active') :
                $targetGrid.find('.ads-campaign-pill').first();
            if ($activePill.length) {
                $activePill.trigger('click');
            }
        }
    }


    // ==========================================================================
    // INITIALIZATION ON READY
    // ==========================================================================
    $(document).ready(function () {
        // Bind Global Donation Buttons & Pills across all pages
        bindGlobalDonationButtons();

        // Init Master Form if present
        $('.ads-master-container').each(function () {
            new MasterDonationForm($(this));
        });

        // Init Project Cards if present
        $('.ads-project-card').each(function () {
            new ProjectDonationCard($(this));
        });

        // Init Campaign Donation Boxes if present
        $('.ads-campaign-card').each(function () {
            new CampaignDonationCard($(this));
        });

        // Init Quick Donate Bars & Hero Widgets if present
        $('.ads-quick-bar, .ads-quick-hero-card').each(function () {
            new QuickDonateBar($(this));
        });

        // Elementor Live Preview Hook
        $(window).on('elementor/frontend/init', function () {
            if (window.elementorFrontend && window.elementorFrontend.hooks) {
                window.elementorFrontend.hooks.addAction('frontend/element_ready/ads_campaign_donation_box.default', function ($scope) {
                    $scope.find('.ads-campaign-card').each(function () {
                        $(this).removeData('ads-initialized');
                        new CampaignDonationCard($(this));
                    });
                });
                window.elementorFrontend.hooks.addAction('frontend/element_ready/shortcode.default', function ($scope) {
                    $scope.find('.ads-campaign-card').each(function () {
                        new CampaignDonationCard($(this));
                    });
                });
                window.elementorFrontend.hooks.addAction('frontend/element_ready/ads_project_donation_card.default', function ($scope) {
                    $scope.find('.ads-project-card').each(function () {
                        $(this).removeData('ads-initialized');
                        new ProjectDonationCard($(this));
                    });
                });
                window.elementorFrontend.hooks.addAction('frontend/element_ready/ads_quick_donate_bar.default', function ($scope) {
                    $scope.find('.ads-quick-bar, .ads-quick-hero-card').each(function () {
                        $(this).removeData('ads-initialized');
                        new QuickDonateBar($(this));
                    });
                });
            }
        });
    });

})(jQuery);


