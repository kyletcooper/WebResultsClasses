function open_media_frame($input, $button) {
    if (wp.media.frames.wrd_frame) {
        wp.media.frames.wrd_frame.open();
        return;
    }

    wp.media.frames.wrd_frame = wp.media({
        title: 'Select image',
        multiple: false,
        library: {
            type: 'image'
        },
        button: {
            text: 'Use selected image'
        }
    });

    var confirm_media_frame = function () {
        var selection = wp.media.frames.wrd_frame.state().get('selection');

        if (!selection) {
            $button.removeClass("has-media").html("Select File");
            $input.val("");
            return;
        }

        selection.each(function (attachment) {
            $input.val(attachment.attributes.id);
            $button.addClass('has-media').html(`<img src="${attachment.attributes.url}"><span>${attachment.attributes.title}</span>`);
        });
    };

    wp.media.frames.wrd_frame.on('select', confirm_media_frame);

    wp.media.frames.wrd_frame.open();
}

jQuery(function ($) {
    $("[data-mediapicker]").on("click", function (e) {
        e.preventDefault();

        $input = $($(this).attr("data-mediapicker"));

        open_media_frame($input, $(this));
    });

    $("[data-mediaremove]").on("click", function (e) {
        e.preventDefault();

        $input = $($(this).attr("data-mediaremove"));
        $button = $(`[data-mediapicker=${$(this).attr("data-mediaremove")}]`);

        $input.val("");
        $button.removeClass("has-media").html("Select File");
    });
});