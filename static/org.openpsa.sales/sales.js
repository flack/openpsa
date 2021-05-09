$(function() {
    if ($( "#org_openpsa_sales_salesproject_deliverable_add" ).length > 0) {
        $.widget( "custom.productselect", {
            _create: function() {
                this.wrapper = $( "<span>" )
                    .addClass( "custom-productselect" )
                    .insertAfter( this.element );
                this.element.hide();
                this._createAutocomplete();
                this._createShowAllButton();
                this.input.attr("placeholder", this.element.data('placeholder'));
            },
            _createAutocomplete: function() {
                var selected = this.element.children( ":selected" ),
                    value = selected.val() ? selected.text() : "";
                this.input = $( "<input>" )
                    .appendTo( this.wrapper )
                    .val( value )
                    .attr( "title", "" )
                    .addClass( "custom-productselect-input ui-widget ui-widget-content ui-state-default ui-corner-left" )
                    .autocomplete({
                        delay: 0,
                        minLength: 0,
                        source: $.proxy( this, "_source" ),
                        select: function(event, ui) {
                            setTimeout(function() {
                                $(ui.item.option).closest('form').submit();
                            }, 10);
                        }
                    });
                this.input.data('ui-autocomplete')._renderItem = function( ul, item ) {
                    return $( "<li>" )
                        .append( "<a>" + item.label + "<span class='product-description'>" + item.description + "</span></a>" )
                        .appendTo( ul );
                };

                this._on( this.input, {
                    autocompleteselect: function( event, ui ) {
                        ui.item.option.selected = true;
                        this._trigger( "select", event, {
                            item: ui.item.option
                        });
                    },
                    autocompletechange: "_removeIfInvalid"
                });
            },
            _createShowAllButton: function() {
                var input = this.input,
                    wasOpen = false;
                $( "<a>" )
                    .attr( "tabIndex", -1 )
                    .attr( "title", "Show All Items" )
                    .appendTo( this.wrapper )
                    .button({
                        icons: {
                            primary: "ui-icon-triangle-1-s"
                        },
                        text: false
                    })
                    .removeClass( "ui-corner-all" )
                    .addClass( "custom-productselect-toggle ui-corner-right" )
                    .mousedown(function() {
                        wasOpen = input.autocomplete( "widget" ).is( ":visible" );
                    })
                    .click(function() {
                        input.focus();
                        // Close if already visible
                        if ( wasOpen ) {
                            return;
                        }
                        // Pass empty string as value to search for, displaying all results
                        input.autocomplete( "search", "" );
                    });
            },
            _source: function( request, response ) {
                var matcher = new RegExp( $.ui.autocomplete.escapeRegex(request.term), "i" );
                response(this.element.children("option").map(function() {
                    var text = $( this ).text(),
                        desc = $( this ).data('description');
                    if (this.value && (!request.term || matcher.test(text) || matcher.test(desc))) {
                        return {
                            label: text,
                            description: desc,
                            value: text,
                            option: this
                        };
                    }
                }));
            },
            _removeIfInvalid: function( event, ui ) {
                // Selected an item, nothing to do
                if ( ui.item ) {
                    return;
                }
                // Search for a match (case-insensitive)
                var value = this.input.val(),
                valueLowerCase = value.toLowerCase(),
                valid = false;
                this.element.children("option").each(function() {
                    if ( $( this ).text().toLowerCase() === valueLowerCase ) {
                        this.selected = valid = true;
                        return false;
                    }
                });
                // Found a match, nothing to do
                if ( valid ) {
                    return;
                }
                // Remove invalid value
                this.input
                    .val( "" )
                    .attr( "title", value + " didn't match any item" );
                this.element.val( "" );
                this.input.autocomplete( "instance" ).term = "";
            },
            _destroy: function() {
                this.wrapper.remove();
                this.element.show();
            }
        });
        $( "#org_openpsa_sales_salesproject_deliverable_add" )
            .productselect()
            .closest('form')
                .on('submit', function(e) {
                    if (!$("#org_openpsa_sales_salesproject_deliverable_add").val()) {
                        e.preventDefault();
                        return;
                    }
                    create_dialog($(this), $(this).find('> label').text());
                });
    }
});

$(document).ready(function() {
    var continuous = $('form.datamanager2 #org_openpsa_sales_continuous');
    if (continuous.length > 0) {
        continuous.on('change', function() {
            $('#org_openpsa_sales_end').closest('.element').toggle(!this.checked);
        }).trigger('change');
    }
});
