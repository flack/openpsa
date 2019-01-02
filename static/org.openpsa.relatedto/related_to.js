$(document).ready(function() {
    var ooRelatedInfoFetched = {};
    $('.relatedto_toolbar')
        .on('click', '.button.info', function() {
            var guid = $(this).closest('.relatedto_toolbar').data('other-guid'),
                target_field = $('#org_openpsa_relatedto_details_' + guid);
            if (!target_field.is('visible')) {
                if (ooRelatedInfoFetched[guid] != true) {
                    var urlField = $('#org_openpsa_relatedto_details_url_' + guid);

                    if (urlField.length > 0) {
                        target_field.load(urlField.attr('title'), function() {
                            target_field.show('slow');
                        });
                    }
                    ooRelatedInfoFetched[guid] = true;
                } else {
                    target_field.show('slow');
                }
            } else {
                target_field.hide('slow');
            }
        })
        .on('click', '.button.confirm, .button.deny', function() {
            var container = $(this).closest('.relatedto_toolbar'),
                guid = container.data('link-guid'),
                mode = $(this).hasClass('confirm') ? 'confirm' : 'deny',
                url = MIDCOM_PAGE_PREFIX + '__mfa/org.openpsa.relatedto/ajax/' + mode + '/' + guid + '/';
            $.post(url, {}, function() {
                if (mode == 'deny') {
                    $('#org_openpsa_relatedto_line_' + guid).hide('slow');
                } else {
                    $(container).find('.button.confirm, .button.deny').parent().hide('slow');
                }
            });
        });
});
