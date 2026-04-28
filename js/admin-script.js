jQuery(document).ready(function ($) {
    const $tabs = $('#wow-settings-tabs .nav-tab');
    const $panels = $('.wow-tab-panel');

    function wowShowTab(targetId) {
        $tabs.removeClass('nav-tab-active');
        $tabs.filter(`[href="${targetId}"]`).addClass('nav-tab-active');
        $panels.hide();
        $(targetId).show();
    }

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

        // If the uploader already exists, open it
        if (mediaUploader) {
            mediaUploader.open();
            return;
        }

        // Create a new media uploader
        mediaUploader = wp.media({
            title: 'Select Featured Image',
            button: {
                text: 'Use this image'
            },
            multiple: false
        });

        // When image is selected
        mediaUploader.on('select', function () {
            const attachment = mediaUploader.state().get('selection').first().toJSON();
            $('#wow_featured_image').val(attachment.url);
            $('#wow_image_preview').html(`<img src="${attachment.url}" style="max-width: 300px; display:block; margin-top:10px;" />`);
        });

        // Open the uploader
        mediaUploader.open();
    });
});
