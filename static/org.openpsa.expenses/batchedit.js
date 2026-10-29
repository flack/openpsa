$(document).ready(function() {
    $('input.batch-clear').each(function() {
        var checkbox = $(this),
            row = checkbox.closest('.element'),
            field = row.prev(),
            input = field.find('input[id$="_selection"]');

        if (!input.length) {
            input = field.find('textarea, input').first();
        }

        function toggle() {
            var empty = ['', '[]'].includes(input.val());
            row.toggle(empty);
            if (!empty) {
                checkbox.prop('checked', false);
            }
        }

        field.on('input change', toggle);
        toggle();
    });
});
