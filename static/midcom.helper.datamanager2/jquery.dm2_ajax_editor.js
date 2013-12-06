(function($){

    $.dm2 = $.dm2 || {};

    $.dm2.ajax_editor = {
        debug_enabled: false,
        possible_states: ['view', 'edit', 'preview', 'save', 'cancel', 'delete'],
        class_prefix: 'dm2_ajax_editor',
        config: {
            mode: 'inline'
        },
        instances: null,
        strings: {
            save_btn_value: 'Save',
            edit_btn_value: 'Edit',
            cancel_btn_value: 'Cancel',
            preview_btn_value: 'Preview',
            delete_btn_value: 'Delete',
            on_save_text: 'Please wait - saving...',
            on_cancel_text: 'Please wait - restoring the original...',
            on_preview_text: 'Please wait - generating preview...',
            on_edit_text: 'Please wait - loading editing interface...'
        }
    };

    $.extend($.dm2.ajax_editor, {
        init: function(identifier, config, is_composite)
        {
            config = $.extend({}, $.dm2.ajax_editor.config, config || {});
            $.dm2.ajax_editor.create_instance(identifier, config, is_composite);

            return $.dm2.ajax_editor.instances[identifier];
        },
        create_instance: function(identifier, config, is_composite)
        {
            if (typeof is_composite === 'undefined') {
                is_composite = false;
            }

            if ($.dm2.ajax_editor.instances === null)
            {
                $.dm2.ajax_editor.instances = {};
            }

            if (typeof $.dm2.ajax_editor[config.mode] !== 'undefined')
            {
                $.dm2.ajax_editor.instances[identifier] = $.dm2.ajax_editor[config.mode]();
                $.dm2.ajax_editor.instances[identifier].init(identifier, config, is_composite);
            }
            else
            {
                $.dm2.ajax_editor.instances[identifier] = new $.dm2.ajax_editor.inline();
                $.dm2.ajax_editor.instances[identifier].init(identifier, config, is_composite);
            }
        },
        get_instance: function(identifier)
        {
            $.dm2.ajax_editor.debug("Get editor instance " + identifier);

            if (typeof $.dm2.ajax_editor.instances[identifier] !== 'undefined')
            {
                return $.dm2.ajax_editor.instances[identifier];
            }
            else
            {
                return {};
            }
        },
        remove_instance: function(identifier)
        {
            $.dm2.ajax_editor.debug("Remove editor instance " + identifier);

            if (typeof $.dm2.ajax_editor.instances[identifier] !== 'undefined')
            {
                $.dm2.ajax_editor.instances[identifier].remove_from_dom();
                delete $.dm2.ajax_editor.instances[identifier];
            }
        },
        set_class_prefix: function(prefix)
        {
            if (typeof prefix !== 'undefined')
            {
                $.dm2.ajax_editor.class_prefix = prefix;
            }
        },
        generate_classname: function(suffix)
        {
            return $.dm2.ajax_editor.class_prefix + '_' + suffix;
        },
        show_message: function(message, title, type)
        {
            $.dm2.ajax_editor.debug("Show message " + message);

            if (typeof $.midcom_services_uimessage_add !== 'function')
            {
                return;
            }

            if (typeof type === 'undefined')
            {
                type = MIDCOM_SERVICES_UIMESSAGES_TYPE_OK;
            }

            if (typeof title === 'undefined')
            {
                title = 'Datamanager';
            }

            $.midcom_services_uimessage_add({
                type: type,
                title: title,
                message: message
            });
        },
        debug: function(msg)
        {
            if ($.dm2.ajax_editor.debug_enabled)
            {
                if (typeof window.console !== 'undefined') {
                    console.log(msg);
                }
            }
        },
        inline: function()
        {
            return (new DM2AjaxEditorInline());
        }
    });

    var DM2AjaxEditorBaseObject = {
        _defaults: {
            in_creation_mode: false,
            allow_removal: false
        },
        methods: {
            config: null,
            identifier: 'dm2_ajax',
            className: 'base',
            fields: null,
            form: null,
            form_fields: null,
            parsed_data: null,
            dimensions: {
                fields: null,
                form: null
            },
            first_field_id: null,
            last_field_id: null,
            state: {
                current: 'view',
                previous: 'view'
            },
            toolbar: null,
            buttons: null,
            creation_tpl: null,
            active_creation_holder: null,
            errors: null,
            init: function(identifier, config, is_composite)
            {
                $.dm2.ajax_editor.debug("Init editor");

                if (typeof is_composite === 'undefined') {
                    is_composite = false;
                }

                this.config = $.extend({}, DM2AjaxEditorBaseObject._defaults, config || {});

                if (typeof identifier !== 'undefined')
                {
                    this.identifier = identifier;
                }

                this.fields = {};
                this.form_fields = {};

                if (is_composite) {
                    this.prepare_composite();
                } else {
                    this._prepare_fields();
                }

                this.form = new DM2AjaxEditorForm();
                this.form.init(this.identifier, this.fields);

                this.initialize();
            },
            initialize: function()
            {
                $.dm2.ajax_editor.debug("Initialize editor");

                this.className = 'base';
            },
            _prepare_fields: function()
            {
                $.dm2.ajax_editor.debug("prepare fields for " + this.identifier);

                if (this.dimensions.fields === null)
                {
                    this.dimensions.fields = {};
                }
                if (this.dimensions.form === null)
                {
                    this.dimensions.form = {};
                }

                var self = this,
                fields = $('.' + this.identifier);

                if (this.config.in_creation_mode) {
                    fields = $('.' + this.identifier, this.active_creation_holder);
                }

                // Prepare the AJAX editable fields
                $.each(fields, function(i)
                {
                    var field = $(this),
                    id = field.attr('id'),
                    name = id.replace(self.identifier+'_', '');

                    if (i === 0)
                    {
                        self.first_field_id = id;
                    }
                    self.last_field_id = id;

                    field.removeClass($.dm2.ajax_editor.generate_classname('editable_area'));
                    field.removeClass($.dm2.ajax_editor.generate_classname('editable_area_hover'));
                    field.removeClass($.dm2.ajax_editor.generate_classname('editing_area'));
                    field.removeClass($.dm2.ajax_editor.generate_classname('preview_area'));

                    field.unbind();
                    if (self.state.current === 'view')
                    {
                        field.addClass($.dm2.ajax_editor.generate_classname('editable_area'));

                        var hover_class = $.dm2.ajax_editor.generate_classname('editable_area_hover');
                        field.bind('mouseover', function() {
                            field.addClass(hover_class);
                        }).bind('mouseout', function() {
                            field.removeClass(hover_class);
                        }).dblclick(function() {
                            self._fetch_fields(true);
                        });
                    }
                    else if (self.state.current === 'preview')
                    {
                        field.addClass($.dm2.ajax_editor.generate_classname('preview_area'));
                    }
                    else if (self.state.current === 'edit')
                    {
                        field.addClass($.dm2.ajax_editor.generate_classname('editing_area'));
                    }

                    self.dimensions.fields[id] = self._calculate_dimensions(field);

                    self.fields[id] = {
                        name: name,
                        elem: field
                    };
                });

                if (this.first_field_id !== null)
                {
                    this._build_toolbar();
                }

                if (this.state.previous === 'preview')
                {
                    this._fields_from_form();
                }

                if (this.state.current === 'edit')
                {
                    this._enable_wysiwygs();
                }
            },
            prepare_composite: function()
            {
                $.dm2.ajax_editor.debug("Prepare composite");

                var self = this,
                button = $('#' + this.identifier + '_button');

                button.bind('click', function() {
                    $.dm2.ajax_editor.debug(button.attr('name') + " clicked");

                    if (self.creation_tpl === null) {
                        self.creation_tpl = $(this).parent().find('.temporary_item');
                        self.creation_tpl.removeClass('temporary_item').addClass('midcom_helper_datamanager2_composite_item_editing');
                    }

                    self.config.in_creation_mode = true;

                    self.active_creation_holder = self.creation_tpl.clone().insertBefore(self.creation_tpl);
                    $(this).parent().find('.temporary_item').remove();

                    self.active_creation_holder.show();

                    self._prepare_fields();
                    self._fetch_fields(true);

                    return false;
                });
            },
            _calculate_dimensions: function(field)
            {
                $.dm2.ajax_editor.debug("Calculate dimensions for field "+field.attr('id'));

                var position = field.position(),
                dimensions = {
                    width: field.width(),
                    height: field.height(),
                    top: position.top,
                    left: position.left
                };

                $.dm2.ajax_editor.debug(dimensions);
                return dimensions;
            },
            _change_state: function(new_state)
            {
                $.dm2.ajax_editor.debug("Change state from " + this.state.current + " to " + new_state);

                this.state.previous = '' + this.state.current;
                this.state.current = new_state;

                this.state_changed();
            },
            _fields_to_form: function()
            {
                $.dm2.ajax_editor.debug("Fields to form");

                var self = this;

                this.form.set_state(this.state.current);
                $.each($('.' + this.identifier), function(i)
                {
                    var field = $(this),
                    id = field.attr('id'),
                    name = id.replace(self.identifier + '_', ''),

                    child = $(field).children('input'),
                    child_name = null;
                    //resolve the post name by the input-field...
                    if (child.length > 0)
                    {
                        child_name = child.attr('id');
                        child_name = child_name.replace(self.identifier + '_qf_', '');
                    }
                    else
                    {
                        child_name = name;
                    }
                    var value = self._get_field_input_value(field);

                    if (value !== null)
                    {
                        //if names should differ we will send it double to be sure the datamanager etc. gets it right
                        self.form.set_value(name, value);
                        self.form.set_value(child_name, value);
                    }
                });

                this._disable_wysiwygs();
            },
            _get_field_input_value: function(field)
            {
                $.dm2.ajax_editor.debug("Get field input value (" + field.attr('id') + ")");

                var self = this,
                id = field.attr('id'),
                name = id.replace(this.identifier + '_', ''),
                input_id = this.identifier + '_qf_' + name,

                input = $('#'+input_id);

                //in case the input-element is somehow different named, check if there is an input inside
                if (input.length < 1)
                {
                    var input_child = $(field).children('input');
                    if (input_child.length > 0)
                    {
                        input = input_child;
                    }
                }

                if (input.length < 1)
                {
                    return null;
                }

                var input_class = input.attr('class'),
                value = null;

                $.each($.dm2.ajax_editor.wysiwygs.configs, function(wysiwyg_name, config){
                    if (   typeof config.className  === 'undefined'
                        || typeof $.dm2.ajax_editor.wysiwygs[wysiwyg_name] === 'undefined')
                    {
                        return;
                    }

                    if (config.className === input_class) {
                        value = $.dm2.ajax_editor.wysiwygs[wysiwyg_name].get_value(field, input);
                    }
                });

                if (value === null)
                {
                    value = input.val();
                }
                return value;
            },
            _fields_from_form: function()
            {
                $.dm2.ajax_editor.debug("Fields from form");

                var self = this;
                $.each($('.'+this.identifier), function(i)
                {
                    var field = $(this),
                    id = field.attr('id'),
                    name = id.replace(self.identifier+'_', ''),
                    value = self.form.get_value(name),
                    input_id = self.identifier + '_qf_' + name,
                    input = $('#'+input_id);

                    if (! input)
                    {
                        return;
                    }

                    input.val(value);
                });
            },
            _on_form_submit: function(status)
            {
                var on_submit_text;
                $.dm2.ajax_editor.debug("On form submit (" + status + ")");

                // Create the help text for hovering effect
                switch (status)
                {
                    case 'save':
                        on_submit_text = $.dm2.ajax_editor.strings.on_save_text;
                        break;

                    case 'cancel':
                        on_submit_text = $.dm2.ajax_editor.strings.on_cancel_text;
                        break;

                    case 'preview':
                        on_submit_text = $.dm2.ajax_editor.strings.on_preview_text;
                        break;

                    case 'edit':
                        on_submit_text = $.dm2.ajax_editor.strings.on_edit_text;
                        break;

                    default:
                        return;
                }

                // Disable toolbar buttons
                $('#' + this.identifier + '_ajax_toolbar').find('input[type="submit"]').prop('disabled', true);
            },
            _fetch_fields: function(edit_mode)
            {
                $.dm2.ajax_editor.debug("fetch fields for "+this.identifier);

                if (typeof edit_mode === 'undefined')
                {
                    edit_mode = false;
                }

                var self = this,
                send_data = {};

                if (edit_mode)
                {
                    if (this.state.current === 'edit')
                    {
                        return;
                    }
                    this._change_state('edit');

                    send_data[this.identifier + '_edit'] = 1;
                }
                else
                {
                    send_data[this.identifier + '_' + this.state.current] = 1;
                }

                $.ajax({
                    global: false,
                    type: "GET",
                    url: location.href,
                    dataType: "xml",
                    data: send_data,
                    success: function(data) {
                        self._parse_response(data);
                    },
                    error: function(xhr,err,e){
                        $.dm2.ajax_editor.debug("Error loading fields!");
                        $.dm2.ajax_editor.debug(err);
                    }
                });
            },
            _parse_response: function(data)
            {
                $.dm2.ajax_editor.debug("Pre parse response");

                if (this.state.current === 'delete')
                {
                    var identifier = $('deletion', data).attr('id'),
                    status = $('deletion', data).find('status').text();

                    if (identifier)
                    {
                        $.dm2.ajax_editor.show_message(status);
                        $.dm2.ajax_editor.remove_instance(this.identifier);
                    }

                    return;
                }

                this.parsed_data = {
                    identifier: $('form', data).attr('id'),
                    new_identifier: $('form', data).attr('new_identifier'),
                    is_editable: $('form', data).attr('editable'),
                    exit_code: $('form', data).attr('exitcode')
                };

                this.errors = $('form', data).find('error');

                if (this.errors.length > 0)
                {
                    $.dm2.ajax_editor.debug("errors: ");
                    $.dm2.ajax_editor.debug(this.errors);
                    this._change_state('edit');
                }
                else
                {
                    if (this.parsed_data.exit_code === 'save')
                    {
                        $.dm2.ajax_editor.show_message('Form saved successfully');
                    }
                }

                var xml_fields = $('form', data).find('field'),
                self = this;

                $.each(xml_fields, function()
                {
                    var field = $(this),
                    name = field.attr('name'),
                    content = field.text();

                    self.form_fields[name] = content;
                });

                if (this.parsed_data.exit_code === 'save' && this.config.in_creation_mode)
                {
                    var new_holder = self.creation_tpl.clone();

                    $.each(xml_fields, function(index, element)
                    {
                        $(new_holder.children()[index])
                            .addClass(self.parsed_data.new_identifier)
                            .attr('id', self.parsed_data.new_identifier + '_' + $(this).attr('name'))
                            .html($(this).text());
                    });

                    new_holder
                        .attr('id', this.parsed_data.new_identifier + '_area')
                        .insertBefore(this.active_creation_holder);

                    this.active_creation_holder.remove();
                    this.toolbar = null;

                    var conf = $.extend({}, this.config, {in_creation_mode: false, allow_removal: true});
                    $.dm2.ajax_editor.init(this.parsed_data.new_identifier, conf);
                    new_holder.show();

                    return;
                }

                this.results_parsed();
                this._prepare_fields();
            },
            _enable_wysiwygs: function()
            {
                $.dm2.ajax_editor.debug("Enable wysiwygs");

                var self = this;
                $.each(this.fields, function(i, field)
                {
                    $.each($.dm2.ajax_editor.wysiwygs.configs, function(wysiwyg_name, config)
                    {
                        if (   typeof config.className === 'undefined'
                            || typeof $.dm2.ajax_editor.wysiwygs[wysiwyg_name] === 'undefined')
                        {
                            return;
                        }

                        if ($(self.form_fields[field.name]).hasClass(config.className))
                        {
                            $.dm2.ajax_editor.wysiwygs[wysiwyg_name].enable($(self.form_fields[field.name]));
                        }
                    });
                });
            },
            _disable_wysiwygs: function()
            {
                $.dm2.ajax_editor.debug("Disable wysiwygs");

                var self = this;
                $.each(this.fields, function(i, field)
                        {
                    $.each($.dm2.ajax_editor.wysiwygs.configs, function(wysiwyg_name, config)
                    {
                        if (   typeof config.className === 'undefined'
                            || typeof $.dm2.ajax_editor.wysiwygs[wysiwyg_name] === 'undefined')
                        {
                            return;
                        }

                        if ($(self.form_fields[field.name]).hasClass(config.className))
                        {
                            $.dm2.ajax_editor.wysiwygs[wysiwyg_name].disable($(self.form_fields[field.name]));
                        }
                    });
                });
            },
            _build_toolbar: function()
            {
                $.dm2.ajax_editor.debug("Build toolbar for state " + this.state.current);

                this.buttons = {};

                var self = this;

                if (this.state.current === 'preview')
                {
                    this.buttons.edit = {
                        name: this.identifier + '_edit',
                        value: $.dm2.ajax_editor.strings.edit_btn_value,
                        elem: null
                    };
                }

                if (this.state.current === 'edit')
                {
                    this.buttons.preview = {
                        name: this.identifier + '_preview',
                        value: $.dm2.ajax_editor.strings.preview_btn_value,
                        elem: null
                    };
                }

                if (   this.state.current === 'edit'
                    || this.state.current === 'preview')
                {
                    this.buttons.save = {
                        name: this.identifier + '_save',
                        value: $.dm2.ajax_editor.strings.save_btn_value,
                        elem: null
                    };

                    this.buttons.cancel = {
                        name: this.identifier + '_cancel',
                        value: $.dm2.ajax_editor.strings.cancel_btn_value,
                        elem: null
                    };
                }

                if (this.state.current === 'edit' && !this.config.in_creation_mode && this.config.allow_removal)
                {
                    this.buttons['delete'] = {
                        name: this.identifier + '_delete',
                        value: $.dm2.ajax_editor.strings.delete_btn_value,
                        elem: null
                    };
                }

                this.render_toolbar();
            },
            _execute_action: function(action, event)
            {
                $.dm2.ajax_editor.debug("Execute action "+action);

                this._change_state(action);

                switch(action)
                {
                    case 'edit':
                        this._on_form_submit('edit');
                        this._disable_wysiwygs();
                        this._fetch_fields();
                    break;
                    case 'preview':
                        this._on_form_submit('preview');
                        this._fields_to_form();
                        this.form.do_submit('preview');
                    break;
                    case 'save':
                        this._on_form_submit('save');

                        if (this.state.previous === 'preview')
                        {
                            this.form.set_state(this.state.current);
                        }
                        else
                        {
                            this._fields_to_form();
                        }

                        this.form.do_submit();
                    break;
                    case 'delete':
                        this._on_form_submit('delete');
                        this.form.set_state('delete');
                        this.form.do_submit('delete');
                    break;
                    case 'cancel':
                    default:
                        this._on_form_submit('cancel');
                        this._disable_wysiwygs();
                        this._fetch_fields();
                        this._change_state('view');
                        if (this.config.in_creation_mode && this.active_creation_holder !== null) {
                            this.active_creation_holder.remove();
                            this.toolbar = null;
                        }
                }
            },
            remove_from_dom: function()
            {
                $.dm2.ajax_editor.debug("Remove instance " + this.identifier+" from dom!");

                $('#' + this.identifier + '_area').remove();
            },
            state_changed: function() {},
            render_toolbar: function() {},
            results_parsed: function() {}
        }
    };

    var DM2AjaxEditorInline = function()
    {
        return $.extend({}, DM2AjaxEditorBaseObject.methods, {
            initialize: function()
            {
                $.dm2.ajax_editor.debug("Initialize inline editor!");

                this.className = 'inline';
            },
            results_parsed: function()
            {
                $.dm2.ajax_editor.debug("Results parsed");

                var self = this,
                unreplaced_fields = [];

                if (   this.parsed_data.is_editable
                    && this.state.current === 'edit')
                {
                    unreplaced_fields = [];
                    $.each(this.fields, function(i, field){
                        if (typeof self.form_fields[field.name] === 'undefined')
                        {
                            unreplaced_fields.push(field.name);
                            return;
                        }

                        field.elem.html(self.form_fields[field.name]);
                    });
                }

                if (this.state.current !== 'edit')
                {
                    unreplaced_fields = [];
                    $.each(this.fields, function(i, field){
                        if (typeof self.form_fields[field.name] === 'undefined')
                        {
                            unreplaced_fields.push(field.name);
                            return;
                        }

                        field.elem.html(self.form_fields[field.name]);
                    });
                }
            },
            render_toolbar: function()
            {
                var toolbar_class = $.dm2.ajax_editor.generate_classname('toolbar');

                $.dm2.ajax_editor.debug("Render toolbar " + toolbar_class);

                if (this.toolbar === null)
                {
                    if ($('#' + this.identifier + '_ajax_toolbar').length > 0) {
                        $('#' + this.identifier + '_ajax_toolbar').remove();
                    }

                    this.toolbar = $('<div />').attr({
                        className: toolbar_class,
                        id: this.identifier + '_ajax_toolbar'
                    }).hide();

                    this.toolbar.insertBefore(this.fields[this.first_field_id].elem);
                }

                this.toolbar.html('');

                var self = this;
                $.each(this.buttons, function(action_name, button)
                {
                    var element = $('<input type="submit" name="' + button.name + '" value="' + button.value + '" />');
                    element.appendTo(self.toolbar);

                    element.bind('click', function(e){
                        self._execute_action(action_name, e);
                    });

                    self.buttons[action_name].elem = element;
                });

                if (   this.state.current !== 'view'
                    && this.state.current !== 'cancel')
                {
                    this.toolbar.show();
                }
            }
        });
    };

    var DM2AjaxEditorForm = function()
    {
        return {
            identifier: null,
            fields: null,
            values: null,
            init: function(identifier, fields)
            {
                this.identifier = identifier;
                this.fields = fields || {};
                this.values = {};

                this._set_defaults();

                return this;
            },
            _set_defaults: function()
            {
                this.values['_qf__' + this.identifier + '_qf'] = 1;
            },
            set_state: function(state)
            {
                var editor = $.dm2.ajax_editor.get_instance(this.identifier),
                self = this;

                $.each($.dm2.ajax_editor.possible_states, function(i, state)
                {
                    if (typeof self.values[editor.identifier + '_' + state] !== 'undefined')
                    {
                        delete self.values[editor.identifier + '_' + state];
                    }

                    if (typeof self.values['midcom_helper_datamanager2_' + state] !== 'undefined')
                    {
                        delete self.values['midcom_helper_datamanager2_' + state];
                    }
                });

                this.values[editor.identifier + '_' + state] = 1;
                this.values['midcom_helper_datamanager2_' + state] = 1;
            },
            set_value: function(field_name, value)
            {
                $.dm2.ajax_editor.debug("set value for " + field_name + ": " + value);
                this.values[field_name] = value;
            },
            get_value: function(field_name)
            {
                return this.values[field_name];
            },
            do_submit: function(next_state)
            {
                if (typeof next_state === 'undefined')
                {
                    next_state = 'view';
                }

                var self = this,
                editor = $.dm2.ajax_editor.get_instance(this.identifier);

                $.ajax({
                    global: false,
                    type: 'POST',
                    url: location.href,
                    dataType: 'xml',
                    contentType: 'application/x-www-form-urlencoded',
                    data: this.values,
                    success: function(data)
                    {
                        editor.state.previous = editor.state.current;
                        editor.state.current = next_state;
                        editor._parse_response(data);
                    },
                    error: function(xhr, err, e)
                    {
                        $.dm2.ajax_editor.debug("Error saving form!");
                        $.dm2.ajax_editor.debug(err);
                    }
                });
            }
        };
    };

    $.dm2.ajax_editor.wysiwygs = {
        configs: {
            tinymce: {
                className: 'tinymce'
            }
        }
    };
    $.dm2.ajax_editor.wysiwygs.tinymce = {
        enable: function(field)
        {
            var id = field.attr('id');
            $.dm2.ajax_editor.debug("Enable tinymce for "+id);

            tinyMCE.execCommand('mceAddControl', false, id);
        },
        disable: function(field)
        {
            var id = field.attr('id');
            $.dm2.ajax_editor.debug("Disable tinymce for " + id);

            if (tinyMCE.get(id))
            {
                tinyMCE.execCommand('mceRemoveControl', true, id);
            }
        },
        get_value: function(field, input)
        {
            if (typeof input === 'undefined')
            {
                var id = field.attr('id');
                var name = id.replace(self.identifier + '_', '');

                var input_id = self.identifier + '_qf_' + name;

                var input = $('#'+input_id);
                if (! input) {
                    return '';
                }
            }

            tinyMCE.triggerSave(true, true);
            return input.val();
        }
    };

})(jQuery);