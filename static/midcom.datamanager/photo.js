function init_photo_widget(id)
{
    var photo_container = $("#" + id);

    photo_container.on('change', '.midcom_datamanager_photo_checkbox', function(e)
    {
        e.preventDefault();
        if($(this).is(':checked'))
        {
            photo_container.find(".preview-image").addClass("delete");
        }
        else
        {
            photo_container.find(".preview-image").removeClass("delete");
        }
    });
}
