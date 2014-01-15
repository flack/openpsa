$(document).ready(function()
{
    var editor = CodeMirror.fromTextArea(document.getElementById(window.midgard_admin_asgard_shell_identifier), {
        mode: "application/x-httpd-php",
        lineNumbers: true,
        theme: "eclipse",
        lineWrapping: true,
        matchBrackets: true,
        indentUnit: 4,
        indentWithTabs: false,
        enterMode: "keep",
        tabMode: "shift",
        readOnly: false,
        extraKeys: {
            "F11": function() {
                var scroller = editor.getScrollerElement();
                if (scroller.className.search(/\bCodeMirror-fullscreen\b/) === -1) {
                    scroller.className += " CodeMirror-fullscreen";
                    scroller.style.height = "100%";
                    scroller.style.width = "100%";
                    editor.refresh();
                } else {
                    scroller.className = scroller.className.replace(" CodeMirror-fullscreen", "");
                    scroller.style.height = '';
                    scroller.style.width = '';
                    editor.refresh();
                }
            },
            "Esc": function() {
                var scroller = editor.getScrollerElement();
                if (scroller.className.search(/\bCodeMirror-fullscreen\b/) !== -1) {
                    scroller.className = scroller.className.replace(" CodeMirror-fullscreen", "");
                    scroller.style.height = '';
                    scroller.style.width = '';
                    editor.refresh();
                }
            }
        }
    });
    var hlLine = editor.addLineClass(0, 'background', "activeline");

    editor.on('cursorActivity', function(instance)
    {
        instance.removeLineClass(hlLine, 'background', 'activeline');
        hlLine = instance.addLineClass(instance.getCursor().line, 'background', "activeline");
    });

    var storage_available = (typeof window.localStorage !== 'undefined' && window.localStorage)
    if (storage_available)
    {
        $('#save-script').on('click', function(event)
        {
            event.preventDefault();
            var script = editor.getValue();
            window.localStorage.setItem('saved-script', script);
            $('#restore-script').removeClass('disabled');
        });
        $('#restore-script').on('click', function(event)
        {
            event.preventDefault();
            var script = window.localStorage.getItem('saved-script');
            if (script)
            {
                editor.setValue(script);
            }
        });
        $('#clear-script').on('click', function(event)
        {
            event.preventDefault();
            window.localStorage.removeItem('saved-script');
            $('#restore-script').addClass('disabled');
            editor.setValue('');
        });
        if (!window.localStorage.getItem('saved-script'))
        {
            $('#restore-script').addClass('disabled');
        }
    }
    else
    {
        $('#save-script, #restore-script, #clear-script').hide();
    }
    var form_id = window.midgard_admin_asgard_shell_identifier.slice(0, -5);

    $("#" + form_id)
        .attr('target', 'shell-runner')
        .attr('action', $("#" + form_id).attr('action') + '?ajax')
        .on('submit', function(event)
        {
            $('#output-wrapper').show();
        });
});