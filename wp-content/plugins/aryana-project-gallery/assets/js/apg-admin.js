/**
 * Aryana Project Gallery - Admin Scripts
 */
jQuery(document).ready(function($) {
    'use strict';

    // 1. Initialize Color Pickers
    if ($.fn.wpColorPicker) {
        $('.apg-color-picker').wpColorPicker();
    }

    // 2. Featured Image Single Media Uploader
    let featuredFrame;
    const $featuredBtn = $('#apg-select-featured-btn');
    const $removeFeaturedBtn = $('#apg-remove-featured-btn');
    const $featuredInput = $('#apg_featured_image');
    const $featuredPreview = $('#apg-featured-preview');

    $featuredBtn.on('click', function(e) {
        e.preventDefault();

        if (featuredFrame) {
            featuredFrame.open();
            return;
        }

        featuredFrame = wp.media({
            title: 'Select Featured Cover Image',
            button: { text: 'Use this image' },
            multiple: false,
            library: { type: 'image' }
        });

        featuredFrame.on('select', function() {
            const attachment = featuredFrame.state().get('selection').first().toJSON();
            const imgUrl = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;
            
            $featuredInput.val(attachment.url);
            $featuredPreview.html('<img src="' + imgUrl + '" alt="Preview">');
            $removeFeaturedBtn.show();
        });

        featuredFrame.open();
    });

    $removeFeaturedBtn.on('click', function(e) {
        e.preventDefault();
        $featuredInput.val('');
        $featuredPreview.html('<span class="apg-no-image"><span class="dashicons dashicons-format-image"></span><br>No image selected</span>');
        $(this).hide();
    });

    // 3. Multi-Image Slider Gallery Media Uploader
    let galleryFrame;
    const $addGalleryBtn = $('#apg-add-gallery-btn');
    const $galleryGrid = $('#apg-gallery-preview-grid');

    $addGalleryBtn.on('click', function(e) {
        e.preventDefault();

        if (galleryFrame) {
            galleryFrame.open();
            return;
        }

        galleryFrame = wp.media({
            title: 'Select Slider Images (Hold Ctrl / Shift to select multiple)',
            button: { text: 'Add to Slider' },
            multiple: true,
            library: { type: 'image' }
        });

        galleryFrame.on('select', function() {
            const selection = galleryFrame.state().get('selection');
            selection.each(function(attachment) {
                const item = attachment.toJSON();
                const thumbUrl = item.sizes && item.sizes.thumbnail ? item.sizes.thumbnail.url : item.url;
                const fullUrl = item.url;

                // Check if already added
                let exists = false;
                $galleryGrid.find('input[name="gallery_images[]"]').each(function() {
                    if ($(this).val() === fullUrl) {
                        exists = true;
                    }
                });

                if (!exists) {
                    const html = `
                        <div class="apg-gallery-thumb-item">
                            <img src="${thumbUrl}" alt="Gallery item">
                            <input type="hidden" name="gallery_images[]" value="${fullUrl}">
                            <button type="button" class="apg-remove-thumb" title="Remove">&times;</button>
                        </div>
                    `;
                    $galleryGrid.append(html);
                }
            });
        });

        galleryFrame.open();
    });

    // Delegate remove gallery thumb
    $galleryGrid.on('click', '.apg-remove-thumb', function(e) {
        e.preventDefault();
        $(this).closest('.apg-gallery-thumb-item').fadeOut(200, function() {
            $(this).remove();
        });
    });
});

