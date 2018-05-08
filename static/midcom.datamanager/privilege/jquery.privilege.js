(function($){
    /**
     * privilege renderer - jQuery plugin
     */
    $.fn.extend({
        /**
         * Create a multiface checkbox interface out of a simple form structure.
         *
         * @example jQuery('#select-holder').render_privilege();
         * @cat plugin
         * @type jQuery
         */
        render_privilege: function(settings) {
            settings = $.extend({
                maxIndex: 3
            }, settings || {});

            return this.each(function() {
                if ($(this).hasClass('privilege_rendered')) {
                    return;
                }
                $(this).addClass('privilege_rendered');

                var div = $("<div/>").insertAfter(this),
                    list_menu = $(this).find("select")[0],
                    selected_index = 0;

                $(list_menu).on('change', function() {
                    div.find('div.privilege_val').trigger('click');
                });

                $(this).find("select option").each(function() {
                    var classes = [null, 'allow', 'deny', 'inherited'],
                        block_style = this.selected ? "style='display: block;'" : "",
                        css_class = '', icon;
                    if (this.value === null || this.value == 3) {
                        css_class = 'inherited';
                    } else if (classes[this.value] !== settings.effective_value) {
                        css_class = 'ineffectual';
                    }

                    if (this.value == 1) {
                        icon = 'check-square';
                    } else if (this.value == 2) {
                        icon = 'minus-square';
                    } else {
                        if (settings.effective_value == 'allow') {
                            icon = 'check-square-o';
                        } else {
                            icon = 'minus-square-o';
                        }
                    }

                    div.append( "<div class='privilege_val' " + block_style + "><a class='" + css_class + "' title='" + this.innerHTML + "'><i class='fa fa-" + icon + "'></i></a></div>" );
                });

                var selects = div.find('div.privilege_val').click(function() {
                    selected_index = selects.index(this) + 1;

                    if (selected_index == settings.maxIndex) {
                        selected_index = 0;
                    }

                    showNext(this);

                    list_menu.selectedIndex = selected_index;

                    return false;
                });

                function showNext(elem) {
                    $(elem).hide();
                    $(selects[selected_index]).show();
                }

            }).hide();
        },

        privilege_actions: function(privilege_key) {
            return this.each(function() {
                if ($(this).find('.privilege_action').length > 0) {
                    return;
                }

                var row = this,
                    actions_holder = $('#privilege_row_actions_' + privilege_key, row),
                    clear_action = $('<div class="privilege_action" />').insertAfter( actions_holder );

                $('<i class="fa fa-trash"></i>').attr({
                        title: "Clear privileges"
                    }).appendTo(clear_action);

                clear_action.on('click', function() {
                    $('select', row).each(function(i, n) {
                        $(n).val(3).trigger('onchange', [0]);
                        $(row).hide();
                    });
                });
            });
        }
    });

})(jQuery);
