/**
 * Aryana Project Gallery - Frontend Lightbox Slider
 */
(function() {
    'use strict';

    document.addEventListener('DOMContentLoaded', initProjectGallery);

    function initProjectGallery() {
        const modal = document.getElementById('apg-modal');
        if (!modal) return;

        const modalTitle = document.getElementById('apg-modal-title');
        const modalCounter = document.getElementById('apg-modal-counter');
        const modalImg = document.getElementById('apg-modal-img');
        const slideLoader = document.getElementById('apg-slide-loader');
        const prevBtn = document.getElementById('apg-prev-btn');
        const nextBtn = document.getElementById('apg-next-btn');
        const dotsContainer = document.getElementById('apg-dots-container');
        const closeBtns = modal.querySelectorAll('[data-apg-close]');

        let currentProject = null;
        let currentIndex = 0;
        let images = [];
        let touchStartX = 0;
        let touchEndX = 0;

        // 1. Attach click listener to all project cards
        const cards = document.querySelectorAll('.apg-card-item');
        cards.forEach(card => {
            card.addEventListener('click', function(e) {
                e.preventDefault();
                openGallery(this);
            });

            // Accessibility: Enter or Space key to open
            card.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    openGallery(this);
                }
            });
        });

        function openGallery(cardEl) {
            const rawData = cardEl.getAttribute('data-apg-project');
            if (!rawData) return;

            try {
                currentProject = JSON.parse(rawData);
            } catch (err) {
                console.error('Invalid APG project data:', err);
                return;
            }

            images = Array.isArray(currentProject.images) && currentProject.images.length > 0
                ? currentProject.images
                : [];

            if (images.length === 0) return;

            currentIndex = 0;

            // Set Title
            if (modalTitle) {
                modalTitle.textContent = currentProject.title || '';
            }

            // Build indicator dots
            buildDots();

            // Show first image
            showImage(currentIndex);

            // Open Modal
            modal.classList.add('apg-modal-open');
            modal.setAttribute('aria-hidden', 'false');
            document.body.style.overflow = 'hidden';

            // Hide/Show arrows if only 1 image
            if (images.length <= 1) {
                if (prevBtn) prevBtn.style.display = 'none';
                if (nextBtn) nextBtn.style.display = 'none';
            } else {
                if (prevBtn) prevBtn.style.display = 'flex';
                if (nextBtn) nextBtn.style.display = 'flex';
            }
        }

        function closeModal() {
            modal.classList.remove('apg-modal-open');
            modal.setAttribute('aria-hidden', 'true');
            document.body.style.overflow = '';
            if (modalImg) {
                modalImg.classList.remove('apg-img-visible');
                modalImg.src = '';
            }
        }

        function showImage(index) {
            if (index < 0) index = images.length - 1;
            if (index >= images.length) index = 0;
            currentIndex = index;

            // Update Counter
            if (modalCounter) {
                modalCounter.textContent = (currentIndex + 1) + ' / ' + images.length;
            }

            // Update Dots
            updateDots();

            // Load Image with smooth transition
            if (modalImg) {
                modalImg.classList.remove('apg-img-visible');
                if (slideLoader) slideLoader.classList.add('apg-is-loading');

                const tempImg = new Image();
                tempImg.src = images[currentIndex];
                tempImg.onload = function() {
                    modalImg.src = tempImg.src;
                    modalImg.alt = (currentProject ? currentProject.title : 'Photo') + ' - image ' + (currentIndex + 1);
                    if (slideLoader) slideLoader.classList.remove('apg-is-loading');
                    modalImg.classList.add('apg-img-visible');
                };
                tempImg.onerror = function() {
                    if (slideLoader) slideLoader.classList.remove('apg-is-loading');
                    modalImg.src = images[currentIndex];
                    modalImg.classList.add('apg-img-visible');
                };
            }

            // Preload adjacent images
            preloadAdjacent(currentIndex);
        }

        function preloadAdjacent(idx) {
            if (images.length > 1) {
                const nextIdx = (idx + 1) % images.length;
                const prevIdx = (idx - 1 + images.length) % images.length;
                const n = new Image(); n.src = images[nextIdx];
                const p = new Image(); p.src = images[prevIdx];
            }
        }

        function buildDots() {
            if (!dotsContainer) return;
            dotsContainer.innerHTML = '';
            if (images.length <= 1) return;

            images.forEach((_, i) => {
                const dot = document.createElement('button');
                dot.type = 'button';
                dot.className = 'apg-dot-item' + (i === currentIndex ? ' apg-dot-active' : '');
                dot.setAttribute('aria-label', 'Go to slide ' + (i + 1));
                dot.addEventListener('click', () => showImage(i));
                dotsContainer.appendChild(dot);
            });
        }

        function updateDots() {
            if (!dotsContainer) return;
            const dots = dotsContainer.querySelectorAll('.apg-dot-item');
            dots.forEach((dot, i) => {
                if (i === currentIndex) {
                    dot.classList.add('apg-dot-active');
                } else {
                    dot.classList.remove('apg-dot-active');
                }
            });
        }

        // Navigation controls
        if (prevBtn) {
            prevBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                showImage(currentIndex - 1);
            });
        }

        if (nextBtn) {
            nextBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                showImage(currentIndex + 1);
            });
        }

        // Close triggers
        closeBtns.forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                closeModal();
            });
        });

        // Keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (!modal.classList.contains('apg-modal-open')) return;

            if (e.key === 'Escape') {
                closeModal();
            } else if (e.key === 'ArrowLeft') {
                showImage(currentIndex - 1);
            } else if (e.key === 'ArrowRight') {
                showImage(currentIndex + 1);
            }
        });

        // Touch swipe support for mobile
        const stage = modal.querySelector('.apg-modal-stage');
        if (stage) {
            stage.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            }, { passive: true });

            stage.addEventListener('touchend', (e) => {
                touchEndX = e.changedTouches[0].screenX;
                handleSwipe();
            }, { passive: true });
        }

        function handleSwipe() {
            const threshold = 40;
            const diff = touchEndX - touchStartX;
            if (Math.abs(diff) > threshold) {
                if (diff < 0) {
                    // Swiped Left -> Next
                    showImage(currentIndex + 1);
                } else {
                    // Swiped Right -> Prev
                    showImage(currentIndex - 1);
                }
            }
        }
    }
})();

