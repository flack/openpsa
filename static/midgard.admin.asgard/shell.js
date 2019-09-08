$(window).on('load', function() {
    var editor = window.editors[window.midgard_admin_asgard_shell_identifier],
        storage_available = (typeof window.localStorage !== 'undefined' && window.localStorage);

    $('#output-wrapper').hide();

    if (storage_available) {
        $('#save-script').on('click', function(event) {
            event.preventDefault();
            var script = editor.getValue();
            window.localStorage.setItem('saved-script', script);
            $('#restore-script').removeClass('disabled');
        });
        $('#restore-script').on('click', function(event) {
            event.preventDefault();
            var script = window.localStorage.getItem('saved-script');
            if (script) {
                editor.setValue(script);
            }
        });
        $('#clear-script').on('click', function(event) {
            event.preventDefault();
            window.localStorage.removeItem('saved-script');
            $('#restore-script').addClass('disabled');
            editor.setValue('');
        });
        if (!window.localStorage.getItem('saved-script')) {
            $('#restore-script').addClass('disabled');
        }
    } else {
        $('#save-script, #restore-script, #clear-script').hide();
    }
    var form_id = window.midgard_admin_asgard_shell_identifier.slice(0, -5);

    $("#" + form_id)
        .attr('target', 'shell-runner')
        .attr('action', $("#" + form_id).attr('action') + '?ajax')
        .on('submit', function() {
            $('#output-wrapper').show();
        });
});
