function init_image_widget(id) {
    var image_container = $("#" + id);

    image_container.on('change', '.midcom_datamanager_photo_checkbox', function(e) {
        e.preventDefault();
        if ($(this).is(':checked')) {
            image_container.addClass("delete");
        } else {
            image_container.removeClass("delete");
        }
    });
}
