$.fn.check_all = function(target) {
    var checked = this.is(':checked');
    
    $(target).find("input[type='checkbox']").each(function() {
        // Skip the write protected
        if (this.disabled) {
            return;
        }

        this.checked = checked;

        // Trigger the onChange event of the input
        $(this).change();
    });
};

$.fn.invert_selection = function(target) {
    $(target).find("input[type='checkbox']").each(function() {
        // Skip the write protected
        if (this.disabled) {
            return;
        }

        this.checked = !this.checked;

        // Trigger the onChange event of the input
        $(this).change();
    });

    this.prop('checked', false);
};

$(document).ready(function() {
    $("#batch_process tbody input[type='checkbox']").each(function() {
        $(this).change(function() {
            var object = this.parentNode,
                n = 0;

            while (!object.tagName.match(/tr/i)) {
                object = object.parentNode;

                // Protect against infinite loops
                if (n > 20) {
                    return;
                }
            }

            if (this.checked) {
                $(object).addClass('row_selected');
            } else {
                $(object).removeClass('row_selected');
            }
        });
    });
});
