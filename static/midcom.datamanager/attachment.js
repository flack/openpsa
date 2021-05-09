function dm_attachment_init(id)
{
    $('#' + id).on('change', function() {
        let file = this.files[0],
            preview = $(this).closest('.attachment-input').prev().find('.icon'),
            extension = file.name.replace(/^.+?\.([^\.]+)$/, '$1'),
            extension_label = preview.find('.extension');
        
        preview
            .removeClass('no-file')
            .addClass('unsaved-file')
            .prop('title', file.name);
        
        if (extension_label.length === 0) {
            extension_label = $('<span class="extension">').appendTo(preview);
        }
        extension_label.text(extension);
    });
}