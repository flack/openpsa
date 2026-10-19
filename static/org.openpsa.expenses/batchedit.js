$(document).ready(function() {
    $('input.batch-clear').each(function() {
        var checkbox = $(this),
            row = checkbox.closest('.element'),
            field = row.prev(),
            selection = field.find('input[id$="_selection"]'),
            editor = field.find('.CodeMirror')[0];

        function toggle() {
            var empty;
            if (selection.length) {
                empty = selection.val() === '[]';
            } else if (editor) {
                empty = editor.CodeMirror.getValue() === '';
            } else {
                empty = field.find('input, textarea').val() === '';
            }
            row.toggle(empty);
            if (!empty) {
                checkbox.prop('checked', false);
            }
        }

        field.on('input change', toggle);
        if (editor) {
            editor.CodeMirror.on('change', toggle);
        }
        toggle();
    });
});
