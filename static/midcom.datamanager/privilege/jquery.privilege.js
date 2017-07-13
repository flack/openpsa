(function($){

    /**
     * background mover - jQuery plugin
     */

    /**
     * Moves elements background depending on given parameters
     *
     * @example jQuery('a.image-link').moveBackground({startPos: 0, endPos: -16});
     * @cat plugin
     * @type jQuery
     */
    $.fn.extend({
        moveBackground: function(settings) {
            settings = $.extend({
                direction: "down",
                startPos: 0,
                endPos: 0,
                time: null,
                startLeft: null,
                startTop: null,
                callback: null,
                callbackArgs: []
            }, settings);

            var element = this[0],
                direction = settings.direction,
                startPos = settings.startPos,
                endPos = settings.endPos,
                time = settings.time;

            if (direction == "down" || direction == "d")
            {
                endPos -= 1;
            }

            var left = settings.startLeft != null ? settings.startLeft : 0,
                top = settings.startTop != null ? settings.startTop : 0;

            if (direction == "down" || direction == "d") {
                top = startPos;
            }

            function anim()
            {
                var leftPos = "px",
                    topPos = "px";

                if (direction == "down" || direction == "d")
                {
                    if (endPos < top) {
                        top -= 1;
                    }

                    if (top != endPos) {
                        setTimeout(anim, time);
                    } else {
                        if (settings.callback != null) {
                            element.style.backgroundPosition = "";
                            settings.callback.call(settings.callbackArgs[0], settings.callbackArgs);
                        }
                        return this;
                    }
                }

                leftPos = left + leftPos;
                topPos = top + topPos;

                var posStr = leftPos + " " + topPos;

                element.style.backgroundPosition = posStr;
            }

            anim();
        },

        /**
         * privilege renderer - jQuery plugin
         */

        /**
         * Create a multiface checkbox interface out of a simple form structure.
         *
         * @example jQuery('#select-holder').render_privilege();
         * @cat plugin
         * @type jQuery
         */
        render_privilege: function(settings) {
            settings = $.extend({
                imageWidth: 16,
                imageHeight: 16,
                maxIndex: 3,
                animate: false
            }, settings || {});

            return this.each(function() {
                if ($(this).hasClass('privilege_rendered')) {
                    return;
                }
                $(this).addClass('privilege_rendered');

                var div = $("<div/>").insertAfter( this ),

                    list_menu = $(this).find("select")[0],
                    nextValue = 0,
                    selected_index = 0;

                $(list_menu).each(function(){
                    $(this).on('change', function() {
                        div.find('div.privilege_val').trigger('click');
                    });
                });

                $(this).find("select option").each(function(){
                    var classes = [ null, 'allow', 'deny', 'inherited'],
                        block_style = this.selected ? "style='display: block;'" : "",
                        value;
                    if (this.value === null || this.value == 3) {
                        value = 'inherited-' + settings.effective_value;
                    }
                    else {
                        value = classes[this.value];
                        if (value !== settings.effective_value) {
                            value += ' ineffectual';
                        }
                    }

                    div.append( "<div class='privilege_val' " + block_style + "><a class='" + value + "' href='#" + this.value + "' title='" + this.innerHTML + "'>" + this.innerHTML + "</a></div>" );
                });

                var selects = div.find('div.privilege_val').click(function(){
                    selected_index = selects.index(this) + 1;

                    var href = $(this).find('a')[0].href,
                        currentValue = href.charAt(href.length-1),
                        startPos, nextHref;

                    if (prevValue == undefined) {
                        var prevValue = currentValue;
                    } else {
                        prevValue = currentValue;
                    }

                    if (selected_index == settings.maxIndex) {
                        selected_index = 0;
                        startPos = 0;
                    } else {
                        startPos = 0 - (prevValue * settings.imageHeight);
                    }


                    var idx = selected_index;
                    if (selected_index >= settings.maxIndex) {
                        idx = settings.maxIndex;
                    }

                    nextHref = $(selects[idx]).find('a:eq(0)').attr('href');
                    nextValue = nextHref.charAt(nextHref.length - 1);

                    var endPos = 0 - (nextValue * settings.imageHeight);

                    if (settings.animate == true) {
                        $(this).find('a:eq(0)').moveBackground({
                            startPos: startPos,
                            endPos: endPos,
                            time: 25,
                            callback: showNext,
                            callbackArgs: [this,selects[selected_index]]
                        });
                    } else {
                        showNext([this]);
                    }

                    list_menu.selectedIndex = selected_index;

                    prevValue = currentValue;

                    return false;
                });

                function showNext(args) {
                    $(args[0]).hide();
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

                $('<img src="' + MIDCOM_STATIC_URL + '/stock-icons/16x16/trash.png" />').attr({
                        alt: "Clear privileges",
                        border: 0
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
