jQuery(document).ready(function ($) {
    const $tabs = $('#wow-settings-tabs .nav-tab');
    const $panels = $('.wow-tab-panel');
    const strings = window.wowAdmin || {};

    function wowShowTab(targetId) {
        $tabs.removeClass('nav-tab-active').attr('aria-selected', 'false');
        $tabs.filter('[href="' + targetId + '"]').addClass('nav-tab-active').attr('aria-selected', 'true');
        $panels.hide().attr('hidden', 'hidden');
        $(targetId).show().removeAttr('hidden');
    }

    $panels.not('#wow-tab-content').hide().attr('hidden', 'hidden');

    $tabs.on('click', function (e) {
        e.preventDefault();
        const targetId = $(this).attr('href');
        wowShowTab(targetId);
    });

    if (window.location.hash && $(window.location.hash).length) {
        wowShowTab(window.location.hash);
    }

    let mediaUploader;

    $('#wow_select_image_button').on('click', function (e) {
        e.preventDefault();

        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        mediaUploader = wp.media({
            title: strings.mediaTitle || 'Select Featured Image',
            button: {
                text: strings.mediaButton || 'Use this image'
            },
            multiple: false
        });

        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            const alt = attachment.title || strings.imageAlt || 'Selected featured image';
            const $img = $('<img>', {
                src: attachment.url,
                alt: alt,
                css: {
                    maxWidth: '300px',
                    display: 'block',
                    marginTop: '10px'
                }
            });

            if (attachment.width) {
                $img.attr('width', attachment.width);
            }
            if (attachment.height) {
                $img.attr('height', attachment.height);
            }

            $('#wow_featured_image').val(attachment.url);
            $('#wow_image_preview').empty().append($img);
        });

        mediaUploader.open();
    });

    $('#wow_remove_image_button').on('click', function (e) {
        e.preventDefault();
        $('#wow_featured_image').val('');
        $('#wow_image_preview').empty();
    });

    $('#wow-bulk-form').on('submit', function (e) {
        const count = parseInt($('#wow_bulk_count').val(), 10);
        if (count > 1) {
            const template = strings.bulkConfirm || 'Publish %d Watch Out Wednesday posts?';
            if (!window.confirm(template.replace('%d', String(count)))) {
                e.preventDefault();
            }
        }
    });
});
