//setting needed variables
//Arrays containing groups & rules, indexed by their id
var groups = {},
    rules = {},
    //contains id of the group root group
    zero_group_id = "";

function count(array) {
    var c = 0,
        i;
    for (i in array) { // in returns key, not object
        if (array[i] !== undefined && array[i] !== null) {
            c++;
        }
    }
    return c;
}

function set_postdata() {
    if (!$('#preview_persons').data('initialized')) {
        $('#preview_persons').data('initialized', true);
        return;
    }
    if ($('#dirmar_rules_editor_container').is(':visible')) {
        get_rules_array(zero_group_id);
    }
    var rules_array = $("#midcom_helper_datamanager2_dummy_field_rules").val(),
        grid = $('#preview_persons').jqGrid();

    if ($('#dirmar_rules_editor_container').is(':visible')) {
        get_rules_array(zero_group_id);
    }

    grid.setGridParam({'postData': {midcom_helper_datamanager2_dummy_field_rules: rules_array}});
}

function build_select(id, name, cssclass, options, selected, add_empty) {
    var select = $('<select class="' + cssclass + '" name="' + name + '" id="' + id + '"/>'),
        option;

    if (add_empty === true) {
        select.append($('<option value=""></option>'));
    }
    $.each(options, function(key, value) {
        if ($.isPlainObject(value)) {
            value = value.localized;
        }
        option = $('<option value="' + key + '">' + value + '</option>');
        if (   selected !== false
            && selected === key) {
            option.prop('selected', true);
        }
        select.append(option);
    });

    return select;
}

//new object rule
function rule(parent, id) {
    this.id = parent  + "_rule_" +  id;
    this.parent = parent;

    this.render = function (selected) {
        var rule_object = rules[this.id],
            rule = $('<div id="' + this.id + '" class="rule" />'),
            parent_field = $('<input type="hidden" name="' + this.id + '[parent]" value="' + this.parent + '"/>'),
            object_select = build_select(this.id + '_object', this.id + '[object]', 'select', org_openpsa_directmarketing_edit_query_property_map, selected, true),
            remove_button = $('<i class="button remove_row fa fa-minus"></i>');

        remove_button.on('click', function(e) {
            e.preventDefault();
            rule_object.remove();
        });

        object_select
            .on('change', function() {
                rules[this.parentNode.id].object_select_onchange();
            });

        rule.append(object_select);
        rule.append(remove_button);
        this.append_add_button(rule);
        rule.append(parent_field);

        $("#" + this.parent + "_add_group").before(rule);
    };

    this.object_select_onchange = function () {
        /* Render next inputs based on value */
        var selected = $("#" + this.id + "_object").val(),
        properties = false,
        parameters = false;
        if (!selected) {
            $("#" + this.id + "_property").remove();
            $("#" + this.id + "_parameter_name").remove();
            $("#" + this.id + "_parameter_domain").remove();
            $("#" + this.id + "_match").remove();
            $("#" + this.id + "_value").remove();
            //TODO: Debug
            //$.debug("The selected value is empty, abort creating new subinputs");
            return;
        }

        if (org_openpsa_directmarketing_edit_query_property_map[selected] === undefined) {
            throw selected + ' could not be resolved to property or parameter';
        }

        if (org_openpsa_directmarketing_edit_query_property_map[selected].properties) {
             this.render_properties_select(org_openpsa_directmarketing_edit_query_property_map[selected].properties, false);
        } else if (org_openpsa_directmarketing_edit_query_property_map[selected].parameters) {
            this.render_parameters_div(false, false, false, false);
        }
    };

    this.render_properties_select = function (properties, selected) {
        var select = build_select(this.id + '_property', this.id + '[property]', 'select', properties, selected, true),
            rule = rules[this.id];

        select.on('change', function() {
            rule.render_match_selectinput(false, false);
        });

        $("#" + this.id + "_property").remove();
        $("#" + this.id + "_parameter_domain").remove();
        $("#" + this.id + "_parameter_name").remove();
        $("#" + this.id + "_object").after(select);
    };

    this.render_parameters_div = function (domain, parameter_name, match, value) {
        var div_id = this.id + '_parameters',
            html = '<div id="' + div_id + '" style="display: inline">',
            input_id = this.id + '_parameter_domain',
            input_name = this.id + '[parameter_domain]',
            input_id2 = this.id + '_parameter_name',
            input_name2 = this.id + '[parameter_name]',
            value_attr = 'value';

        if (domain === false) {
            value_attr = 'placeholder';
            domain = org_openpsa_directmarketing_edit_query_l10n_map.in_domain;
            parameter_name = org_openpsa_directmarketing_edit_query_l10n_map.with_name;
            match = false;
            value = false;
        }
        $("#" + this.id + "_property").remove();


        html += '<input type="text" class="shorttext" name="' + input_name + '" id="' + input_id + '" ' + value_attr + '="' + domain + '" />';
        html += '<input type="text" class="shorttext" name="' + input_name2 + '" id="' + input_id2 + '" ' + value_attr + '="' + parameter_name + '" />';
        html += '</div>';
        $("#" + this.id + "_object").after(html);

        this.render_match_selectinput(match, value);
    };

    this.render_match_selectinput = function(match, value) {
        if ($("#" + this.id + '_match').length > 0) {
            return;
        }
        var input_id = this.id + "_match",
            input_id2 = this.id + '_value',
            holder = ($("#" + input_id).val() === 'generic_parameters') ? $("#" + this.id + "_parameter_name") : $("#" + this.id + "_property"),
            select = build_select(this.id + '_match', this.id + '[match]', 'select', org_openpsa_directmarketing_edit_query_match_map, match, false),
            input = $('<input type="text" class="shorttext" name="' + this.id + '[value]" id="' + input_id2 + '" >');

        if (value === false) {
            value = "";
        }
        input.val(value);

        holder.after(input);
        holder.after(select);
    };

    this.remove = function() {
        var count_child_rules = count(groups[this.parent].child_rules),
            count_child_groups = count(groups[this.parent].child_groups);

        if ($("#" + this.id).children(".add_row").length > 0) {
            this.append_add_button($("#" + this.id).prevAll(".rule:first"));
        }
        groups[this.parent].child_rules[this.id] = null ;

        $("#" + this.id).remove();
        delete rules[String(this.id)];
        //check if this was last rule
        if (count_child_rules === 1) {
            if (this.parent !== zero_group_id && count_child_groups === 0) {
                //remove parent group from child_groups of the parent group of the parent
                delete groups[groups[this.parent].parent].child_groups[this.parent];
                groups[this.parent].remove();
                delete groups[this.parent];
            } else {
                groups[this.parent].add_rule(false);
            }
        }

        return false;
    };

    this.append_add_button = function(rule) {
        $('<i class="button add_row fa fa-plus"></i>')
            .on('click', function(e) {
                e.preventDefault();
                $(this).remove();
                groups[rules[rule.attr('id')].parent].add_rule();
            })
            .appendTo(rule);
    };
}

// group-"class"
function group(parent, number) {
    this.id = parent + "_group_" + number;
    this.parent = parent;
    this.count_rules = 0;
    this.count_groups = 0;

    this.render = function (selected) {
        var group = this,
            content_group = $('<div id="' + this.id + '" class="group"/>'),
            group_select = build_select(this.id + '_select', this.id + '[group]', 'groupselect', org_openpsa_directmarketing_group_select_map, selected, false),
            group_button = $('<input id="' + this.id + '_add_group" class="add_group" type="button" value="' + org_openpsa_directmarketing_edit_query_l10n_map.add_group + '">'),
            parent_field = $('<input type="hidden" name="' + this.id + '[parent]" value="' + this.parent + '"/>'),
            group_remove_button;

        group_button.on('click', function() {
            group.add_group(false);
        });
        if (this.id !== "dirmar_rules_editor_container_group_0") {
            group_remove_button = $('<input id=""' + this.id + '_remove_group" class="remove_group" type="button" value="' + org_openpsa_directmarketing_edit_query_l10n_map.remove_group + '">');
            group_remove_button.on('click', function() {
                group.remove();
            })
            .appendTo(content_group);
        }

        content_group.append(group_select);
        content_group.append($('<br>'));
        content_group.append(group_button);
        content_group.append(parent_field);

        if (this.id !== "dirmar_rules_editor_container_group_0") {
            $("#" + this.parent + "_add_group").before(content_group);
        } else {
            $("#" + this.parent).append(content_group);
        }
    };

    /**
     * adds rule
     * @param selected - the selected OBJECT
     */
    this.add_rule = function add_rule(selected) {
        this.count_rules = this.count_rules + 1;
        var index = String(this.id + "_rule_" + this.count_rules);
        rules[index] = new rule(this.id, this.count_rules);
        rules[index].render(selected);
        if (selected === undefined) {
            $("#" + index + "_object")
                .val($("#" + index + "_object option:nth-child(2)").attr('value'))
                .trigger('change');
            $("#" + index + "_property")
                .val($("#" + index + "_property option:nth-child(2)").attr('value'))
                .trigger('change');
        }
        this.child_rules[index] = rules[index].id;

        if ($("#" + index).prev().children(".add_row").length > 0) {
            $("#" + index).prev().children(".add_row").remove();
        }
        return index;
    };

    /**
     * adds group
     * @param selected - the selected relational operator
     */
    this.add_group = function(selected) {
        this.count_groups = this.count_groups  + 1;
        var index = String(this.id + "_group_" + this.count_groups);
        groups[index] = new group(this.id, this.count_groups);
        groups[index].render(selected);
        this.child_groups[index] = groups[index].id;

        if (selected === false) {
            groups[index].add_rule(false);
        }
        return index;
    };

    this.remove = function() {
        var rule_key,
            group_key;
        //first remove childs

        for (rule_key in this.child_rules) {
            if (this.child_rules[rule_key] !== null) {
                rules[this.child_rules[rule_key]].remove();
                delete rules[this.child_rules[rule_key]];
            }
        }

        for (group_key in this.child_groups) {
            if (   this.child_groups[group_key] !== null
                && this.child_groups[group_key] !== undefined) {
                if (groups[this.child_groups[group_key]] !== undefined) {
                    groups[this.child_groups[group_key]].remove();
                }
                this.child_groups[group_key] = null;
                delete groups[this.child_groups[group_key]];
            }
        }

        //check if there is a group in front & no group behind
        if (   $("#" + this.id).prev(".group").length > 0
            && $("#" + this.id).next(".group").length < 1) {
            $("#" + this.id).prev(".group").removeClass("nobottom", "0px");
        }

        $("#" + this.id).remove();
        delete groups[String(this.id)];
    };

    this.child_groups = {};
    this.child_rules = [];
}

function init(selector, rules) {
    var type = rules.type || false;

    zero_group_id = selector + "_group_0";
    groups[zero_group_id] = new group(selector, 0);
    groups[zero_group_id].render(type);

    // add an empty rule if no rules are currently given
    if (   rules.classes === undefined
        || rules.classes.length === 0) {
        rules.classes = [];
        groups[zero_group_id].add_rule(false);
    }

    $('#openpsa_dirmar_edit_query').parent().addClass('disabled');
    try {
        get_child_rules(zero_group_id, rules.classes);
        $('#midcom_helper_datamanager2_dummy_field_rules').hide();
    } catch (e) {
        $('#dirmar_rules_editor_container').hide();
        $('#openpsa_dirmar_edit_query_advanced').parent().addClass('disabled');
    }

    $('#openpsa_dirmar_edit_query_advanced').on('click', function(event) {
        event.preventDefault();
        get_rules_array(zero_group_id);
        $('#midcom_helper_datamanager2_dummy_field_rules').show();
        $('#dirmar_rules_editor_container').hide();
        $('#openpsa_dirmar_edit_query').parent().removeClass('disabled');
        $('#openpsa_dirmar_edit_query_advanced').parent().addClass('disabled');
    });
    $('#openpsa_dirmar_edit_query').on('click', function(event) {
        event.preventDefault();
        $('#midcom_helper_datamanager2_dummy_field_rules').hide();
        $('#dirmar_rules_editor_container').show();
        $('#openpsa_dirmar_edit_query').parent().addClass('disabled');
        $('#openpsa_dirmar_edit_query_advanced').parent().removeClass('disabled');
    });
    $('#show_rule_preview').on('click', function(event) {
        event.preventDefault();
        $('#preview_persons').jqGrid().trigger('reloadGrid');
    });
    // adds hover effect to group
    $("#org_openpsa_directmarketing_rules_editor")
        .on('mouseenter', '#dirmar_rules_editor_container_group_0 .group', function() {
            $(this).addClass("focus");
        })
        .on('mouseleave', '#dirmar_rules_editor_container_group_0 .group', function() {
            $(this).removeClass("focus");
        });
}

// function to gather the rules from the form & write them into midcom_helper_datamanager2_dummy_field_rules
function get_rules_array(parent_id) {
    var type = $("#" + parent_id + "_select").val(),
        ruleset = {
            type: type,
            classes: get_rules_groups(parent_id, 0)
        };
    $("#midcom_helper_datamanager2_dummy_field_rules").val(JSON.stringify(ruleset));

    return true;
}

/**
 * function to gather rules for group
 *   parent_id - id to check for childs
 */
function get_rules_groups(parent_id) {
    var array_key, ruleset = [],
        index, type,
        object,
        property,
        domain, name, match, value;

    for (array_key in groups[parent_id].child_groups) {
        index = groups[parent_id].child_groups[array_key];
        type = $("#" + index + "_select").val();

        if (type !== undefined) {
            ruleset.push({
                type: type,
                groups: type,
                classes: get_rules_groups(index)
            });
        }
    }

    for (array_key in groups[parent_id].child_rules) {
        index = groups[parent_id].child_rules[array_key];
        object = $("#" + index + "_object").val();
        match = $("#" + index + "_match").val();
        value = $("#" + index + "_value").val();
        if (   match === 'LIKE'
            || match === 'NOT LIKE') {
            value = "%" + value + "%";
        }

        if (   object !== undefined
            && object !== "") {
            // parameters must be handled differently
            if (object === 'generic_parameters') {
                domain  = $("#" + index + "_parameter_domain").val();
                name = $("#" + index + "_parameter_name").val();

                ruleset.push({
                    type: 'AND',
                    'class': org_openpsa_directmarketing_class_map[object],
                    rules: [
                        generate_rule('domain', '=', domain),
                        generate_rule('name', '=', name),
                        generate_rule('value', match, value)
                    ]
                });
            } else {
                property  = $("#" + index + "_property").val();

                //only write if property is chosen
                if (   property !== ""
                    && match !== "") {
                    ruleset.push({
                        type: 'AND',
                        'class': org_openpsa_directmarketing_class_map[object],
                        rules: [
                            generate_rule(property, match, value)
                        ]
                    });
                }
            }
        }
    }
    return ruleset;
}

function generate_rule(property, match, value) {
    return {
        property: property,
        match: match,
        value: value
    };
}

/**
 * function to set passed rules (passed by get_old_rules->show-campaign-edit_query.php)
 * @param parent - parent where the rules should be added
 * @param rules_array - array containing the rules
 */
function get_child_rules(parent, rules_array) {
    var map_class,
        rule_match, rule_value, rule_domain, rule_parameter_name, rule_property, rule_id,
        properties,
        parameters, group_id;

    $.each(rules_array, function (key, value) {
        //old-parameter-case
        if (value.groups && value.rules) {
            //if class is not supported -> error-msg
            if (org_openpsa_directmarketing_class_map[value['class']] === undefined) {
                throw 'unsupported class';
            }
            map_class = org_openpsa_directmarketing_class_map[value['class']];
            rule_domain = value.groups[0].rules[0].property;
            rule_parameter_name = value.groups[0].rules[1].property;
            rule_match = value.groups[0].rules[2].match;
            rule_value = value.groups[0].rules[2].value;
            rule_id = groups[parent].add_rule(map_class);
            if (rule_value.substr(0, 1) === '%') {
                rule_value = rule_value.substr(1);
            }
            if (rule_value.substr(rule_value.length - 1, 1) === '%') {
                rule_value = rule_value.substr(0, rule_value.length -1);
            }
            rules[rule_id].render_parameters_div(rule_domain, rule_parameter_name, rule_match, rule_value);
        } else if (value.groups) {
            //normal group-case
            group_id = groups[parent].add_group(value.type);
            get_child_rules(group_id, value.classes);
        } else if (value.rules) {
            //normal rule-case
            if (org_openpsa_directmarketing_class_map[value['class']] === undefined) {
                throw 'unsupported class';
            }

            map_class = org_openpsa_directmarketing_class_map[value['class']];
            rule_match = value.rules[0].match;
            rule_value = value.rules[0].value;
            rule_property = value.rules[0].property;
            rule_id = groups[parent].add_rule(map_class);
            properties = false;
            parameters = false;
            $.each(org_openpsa_directmarketing_edit_query_property_map, function(key, value) {
                if (key == map_class) {
                    properties = value.properties;
                    parameters = value.parameters;
                }
            });
            if (properties) {
                // get rid of the % in front & at the end of strings
                if (rule_value.substr(0,1) === '%') {
                    rule_value = rule_value.substr(1);
                }
                if (rule_value.substr(rule_value.length - 1, 1) === '%') {
                    rule_value = rule_value.substr(0, rule_value.length - 1);
                }
                rules[rule_id].render_properties_select(properties, rule_property);
                rules[rule_id].render_match_selectinput(rule_match, rule_value);
            } else if (parameters) {
                rule_match = value.rules[2].match;
                rule_value = value.rules[2].value;
                rule_domain = value.rules[0].value;
                rule_parameter_name = value.rules[1].value;
                if (rule_value.substr(0,1) === '%') {
                    rule_value = rule_value.substr(1);
                }
                if (rule_value.substr(rule_value.length - 1,1) === '%') {
                    rule_value = rule_value.substr(0, rule_value.length - 1);
                }
                rules[rule_id].render_parameters_div(rule_domain, rule_parameter_name, rule_match, rule_value);
            }
        }
    });
}
