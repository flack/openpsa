$(document).ready(function(){
    if ($('#content form.datamanager2 .form_toolbar').length === 1) {
        var last_insert;
        $('#content form.datamanager2 .form_toolbar > *').each(function(){
            var btn = $(this),
                icon = btn.css('background-image').replace(/url\(\"/, '').replace(/\"\)/, ''),
                toolbar_entry = $('<li class="enabled"><div><button type=\submit"><img src="' + icon + '"><soan class="toolbar_label"> ' + (btn.val() || btn.text()) + '</span></button></div></li>')
                         .on('click', function(){
                             btn.click();
                         });

            if (last_insert) {
                last_insert.after(toolbar_entry);
            }
            else {
                if ($('#org_openpsa_toolbar > .midcom_toolbar').length === 0) {
                    $('#org_openpsa_toolbar').append($('<ul class="midcom_toolbar"></ul>'));
                }

                $('#org_openpsa_toolbar > .midcom_toolbar')
                .prepend(toolbar_entry);
            }
            last_insert = toolbar_entry;
        });
    }
});
