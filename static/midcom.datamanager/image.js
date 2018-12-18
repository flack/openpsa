function init_image_widget(id) {
    var image_container = $("#" + id);

    image_container
        .on('change', '.midcom_datamanager_photo_checkbox', function(e) {
            e.preventDefault();
            if ($(this).is(':checked')) {
                image_container.find('td > :not(label)').addClass("delete");
            } else {
                image_container.find('td > .delete').removeClass("delete");
            }
        })
        .on('change', 'input[type="file"]', function() {
            var file = this.files[0],
                extension = file.name.replace(/^.+?\.([^\.]+)$/, '$1'),
                preview = $(this).closest('td').prev().find('.preview-image'),
                reader = new FileReader();

            if (preview.length === 0) {
                preview = $('<img class="preview-image">')
                    .appendTo($(this).closest('td').prev());
            } else {
                $(this).closest('td').find('> ul').hide();
            }

            reader.onload = function() {
                preview[0].src = reader.result;
            };
            reader.readAsDataURL(file);

            preview
                .addClass('unsaved-file')
                .prop('title', file.name);
        });

    if (image_container.find('input[type="file"]')[0].files.length > 0) {
        image_container.find('input[type="file"]').trigger('change');
    }
    image_container.find('.midcom_datamanager_photo_checkbox').trigger('change');
}
