//setting needed variables
//Arrays containing groups & rules, indexed by their id
var groups = [],
rules = [],
//contains id of the group root group
zero_group_id = "";

function count(array)
{
    var c = 0,
    i;
    for (i in array)
    {// in returns key, not object
        if (   array[i] !== undefined
            && array[i] !== null)
        {
            c++;
        }
    }
    return c;
}

function send_preview()
{
    var loading = "<img style='text-align:center;' src='" + MIDCOM_STATIC_URL + "/stock-icons/32x32/ajax-loading.gif'/>";
    $("#preview_persons").css("text-align", "center");
    $("#preview_persons").html(loading);

    if ($('#dirmar_rules_editor_container').is(':visible'))
    {
        get_rules_array(zero_group_id);
    }
    var rules_array = $("#midcom_helper_datamanager2_dummy_field_rules").val(),
    post_data = {show_rule_preview: true, midcom_helper_datamanager2_dummy_field_rules: rules_array};
    $.post(document.URL, post_data, function(data)
    {
        $("#preview_persons").html(data);
        $("#preview_persons").css("text-align", "left");
    });
}

function render_parameters_div(domain, parameter_name, match, value)
{
    if (domain === false)
    {
        domain = "< " + org_openpsa_directmarketing_edit_query_l10n_map.in_domain + " >";
        parameter_name = "< " + org_openpsa_directmarketing_edit_query_l10n_map.with_name + " >";
        match = false;
        value = false;
    }
    $("#" + this.id + "_property").remove();

    var div_id = this.id + '_parameters',
    html = '<div id="' + div_id + '" style="display: inline">',
    input_id = this.id + '_parameter_domain',
    input_name = this.id + '[parameter_domain]',
    jscall = "$(this).val('');",
    input_id2 = this.id + '_parameter_name',
    input_name2 = this.id + '[parameter_name]';

    html += '<input type="text" class="shorttext" name="' + input_name + '" id="' + input_id + '" value="' + domain + '" onfocus="' + jscall + '" />';
    html += '<input type="text" class="shorttext" name="' + input_name2 + '" id="' + input_id2 + '"  value="' + parameter_name + '" onfocus="' + jscall + '" />';
    html += '</div>';
    $("#" + this.id + "_object").after(html);

    this.render_match_selectinput(match, value);
}

function remove_rule()
{
    var count_child_rules = count(groups[this.parent].child_rules),
    count_child_groups = count(groups[this.parent].child_groups);

    if ($("#" + this.id).children(".add_row").length > 0)
    {
        add_button = "<img id =\"" + this.id + "_add\" src=\"" + MIDCOM_STATIC_URL + "/stock-icons/16x16/list-add.png\" class=\"button add_row\" onclick=\"$(this).remove();groups['" + this.parent + "'].add_rule(); return false;\" />";
        $("#" + this.id).prev(".rule").append(add_button);
    }
    groups[this.parent].child_rules[this.id] = null ;

    //set the borders of prev & next

    if (($("#" + this.id).prev(".rule").length > 0) && ($("#" + this.id).next(".rule").length < 1))
    {
        $("#" + this.id).prev(".rule").removeClass("nobottom");
    }
    else if (($("#" + this.id).prev(".rule").length < 1) && ($("#" + this.id).next(".rule").length > 0))
    {
        $("#" + this.id).next(".rule").removeClass("notop");
    }

    $("#" + this.id).remove();
    delete rules[String(this.id)];
    //check if this was last rule
    if (count_child_rules === 1)
    {
        if (this.parent !== zero_group_id && count_child_groups === 0)
        {
            //remove parent group from child_groups of the parent group of the parent
            delete groups[groups[this.parent].parent].child_groups[this.parent];
            groups[this.parent].remove();
            delete groups[this.parent];
        }
        else
        {
            groups[this.parent].add_rule(false);
        }
    }

    return false;
}

function remove_group()
{
    var rule_key,
    group_key;
    //first remove childs

    for (rule_key in this.child_rules)
    {
        if (this.child_rules[rule_key] !== null)
        {
            rules[this.child_rules[rule_key]].remove();
            delete rules[this.child_rules[rule_key]];
        }
    }

    for (group_key in this.child_groups)
    {
        if (   this.child_groups[group_key] !== null
            && this.child_groups[group_key] !== undefined)
        {
            if (groups[this.child_groups[group_key]] !== undefined)
            {
                groups[this.child_groups[group_key]].remove();
            }
            this.child_groups[group_key] = null;
            delete groups[this.child_groups[group_key]];
        }
    }

    //check if there is a group in front & no group behind
    if (   $("#" + this.id).prev(".group").length > 0
        && $("#" + this.id).next(".group").length < 1)
    {
        $("#" + this.id).prev(".group").removeClass("nobottom", "0px");
    }

    $("#" + this.id).remove();
    delete groups[String(this.id)];
}

function build_select(id, name, cssclass, options, selected, add_empty)
{
    var select = $('<select class="' + cssclass + '" name="' + name + '" id="' + id + '"/>'),
        option;

    if (add_empty === true)
    {
        select.append($('<option value=""></option>'));
    }
    $.each(options, function(key, value)
    {
        if ($.isPlainObject(value))
        {
            value = value.localized;
        }
        option = $('<option value="' + key + '">' + value + '</option>');
        if (   selected !== false
            && selected === key)
        {
            option.prop('selected', true);
        }
        select.append(option);
    });

    return select;
}

function render_match_selectinput(match, value)
{
    if ($("#" + this.id + '_match').length > 0)
    {
        return;
    }
    var input_id = this.id + "_match",
        input_id2 = this.id + '_value',
        holder = ($("#" + input_id).val() === 'generic_parameters') ? $("#" + this.id + "_parameter_name") : $("#" + this.id + "_property"),
        select = build_select(this.id + '_match', this.id + '[match]', 'select', org_openpsa_directmarketing_edit_query_match_map, match, false),
        input = $('<input type="text" class="shorttext" name="' + this.id + '[value]" id="' + input_id2 + '" >');

    if (value === false)
    {
        value = "";
    }
    input.val(value);

    holder.after(input);
    holder.after(select);
}

function render_properties_select(properties, selected)
{
    var select = build_select(this.id + '_property', this.id + '[property]', 'select', properties, selected, true),
        rule = rules[this.id];

    select
        .on('change', function()
        {
            rule.render_match_selectinput(false, false);
        });

    $("#" + this.id + "_property").remove();
    $("#" + this.id + "_parameter_domain").remove();
    $("#" + this.id + "_parameter_name").remove();
    $("#" + this.id + "_object").after(select);
}

function object_select_onchange()
{
    /* Render next inputs based on value */
    var selected = $("#" + this.id + "_object").val(),
    properties = false,
    parameters = false;
    if (!selected)
    {
        $("#" + this.id + "_property").remove();
        $("#" + this.id + "_parameter_name").remove();
        $("#" + this.id + "_parameter_domain").remove();
        $("#" + this.id + "_match").remove();
        $("#" + this.id + "_value").remove();
        //TODO: Debug
        //$.debug("The selected value is empty, abort creating new subinputs");
        return;
    }
    $.each(org_openpsa_directmarketing_edit_query_property_map, function(key, value)
    {
        if (key === selected)
        {
            properties = value.properties;
            parameters = value.parameters;
        }
    });
    if (properties)
    {
         this.render_properties_select(properties, false);
    }
    else if (parameters)
    {
        this.render_parameters_div(false, false, false, false);
    }
    else
    {
        /* Error with key */
        //alert (selected + ' could not be resolved to property or parameter');
    }
}

function render_rule(selected)
{
    var rule_object = rules[this.id],
        rule = $('<div id="' + this.id + '" class="rule" />'),
        parent_field = $('<input type="hidden" name="' + this.id + '[parent]" value="' + this.parent + '"/>'),
        object_select = build_select(this.id + '_object', this.id + '[object]', 'select', org_openpsa_directmarketing_edit_query_property_map, selected, true),
        remove_button = $('<img src="' + MIDCOM_STATIC_URL + '/stock-icons/16x16/list-remove.png"  class="button remove_row" />'),
        add_button = $('<img src="' + MIDCOM_STATIC_URL + '/stock-icons/16x16/list-add.png"  class="button add_row" />');

    remove_button.on('click', function(e)
    {
        e.preventDefault();
        rule_object.remove();
    });

    add_button.on('click', function(e)
    {
        e.preventDefault();
        $(this).remove();
        groups[rule_object.parent].add_rule();
    });

    object_select
        .on('change', function()
        {
            var rule_id = $(this).parent().attr("id");
            rules[rule_id].object_select_onchange();
        });

    rule.append(object_select);
    rule.append(remove_button);
    rule.append(add_button);
    rule.append(parent_field);

    $("#" + this.parent + "_add_group").before(rule);

    // to display rules as one block
    if ($("#" + this.id).prev(".rule").length > 0 )
    {
        $("#" + this.id).prev(".rule").addClass("nobottom");
        $("#" + this.id).addClass("notop");
    }
    //to get distant between rules & groups
    else if ($("#" + this.id).prev(".group").length > 0 )
    {
        $("#" + this.id).css("margin-top", "5px");
    }
}
//new object rule
function rule(parent, id)
{
    this.id = parent  + "_rule_" +  id;
    this.parent = parent;
    this.render = render_rule;
    this.object_select_onchange = object_select_onchange;
    this.render_properties_select = render_properties_select;
    this.render_parameters_div = render_parameters_div;
    this.render_match_selectinput = render_match_selectinput;
    this.remove = remove_rule;
}

function render_group(selected)
{
    var group = this,
        content_group = $('<div id="' + this.id + '" class="group"/>'),
        group_select = build_select(this.id + '_group', this.id + '[group]', 'groupselect', org_openpsa_directmarketing_group_select_map, selected, false),
        group_button = $('<input id="' + this.id + '_add_group" class="add_group" type="button" value="' + org_openpsa_directmarketing_edit_query_l10n_map.add_group + '">'),
        parent_field = $('<input type="hidden" name="' + this.id + '[parent]" value="' + this.parent + '"/>'),
        group_remove_button;

    group_button.on('click', function()
    {
        group.add_group(false);
    });
    if (this.id !== "dirmar_rules_editor_container_group_0")
    {
        group_remove_button = $('<input id=""' + this.id + '_remove_group" class="remove_group" type="button" value="' + org_openpsa_directmarketing_edit_query_l10n_map.remove_group + '">');
        group_remove_button.on('click', function()
        {
            group.remove();
        })
        .appendTo(content_group);
    }

    content_group.append(group_select);
    content_group.append($('<br>'));
    content_group.append(group_button);
    content_group.append(parent_field);

    if (this.id !== "dirmar_rules_editor_container_group_0")
    {
        $("#" + this.parent + "_add_group").before(content_group);
    }
    else
    {
        $("#" + this.parent).append(content_group);
    }

    // distance between groups & rules
    if ($("#" + this.id).prev(".rule").length > 0 )
    {
        $("#" + this.id).css("margin-top", "5px");
    }

    if ($("#" + this.id).prev(".group").length > 0)
    {
        $("#" + this.id).prev(".group").addClass("nobottom");
    }
}

/**
 * adds rule
 * @param selected - the selected OBJECT
 */
function add_rule(selected)
{
    this.count_rules = this.count_rules + 1;
    var index = String(this.id + "_rule_" + this.count_rules);
    rules[index] = new rule(this.id, this.count_rules);
    rules[index].render(selected);
    this.child_rules[index] = rules[index].id;

    if ($("#" + index).prev().children(".add_row").length > 0)
    {
        $("#" + index).prev().children(".add_row").remove();
    }
    return index;
}

/**
 * adds group
 * @param selected - the selected relational operator
 */
function add_group(selected)
{
    this.count_groups = this.count_groups  + 1;
    var index = String(this.id + "_group_" + this.count_groups);
    groups[index] = new group(this.id, this.count_groups);
    groups[index].render(selected);
    this.child_groups[index] = groups[index].id;

    if (selected === false)
    {
        groups[index].add_rule(false);
    }
    return index;
}
// group-"class"
function group(parent, number)
{
    this.id = parent + "_group_" + number;
    this.parent = parent;
    this.count_rules = 0;
    this.count_groups = 0;
    this.render = render_group;
    this.add_rule = add_rule;
    this.add_group = add_group;
    this.remove = remove_group;

    this.child_groups = [];
    this.child_rules = [];
}

function init(selector, rules)
{
    var type = rules.type || false;

    zero_group_id = selector + "_group_0";
    groups[zero_group_id] = new group(selector, 0);
    groups[zero_group_id].render(type);

    // add an empty rule if no rules are currently given
    if (   rules.classes === undefined
        || rules.classes.length === 0)
    {
        groups[zero_group_id].add_rule(false);
    }

    $('#openpsa_dirmar_edit_query').parent().addClass('disabled');
    try
    {
        get_child_rules(zero_group_id, rules.classes);
        $('#midcom_helper_datamanager2_dummy_field_rules').hide();
    }
    catch (e)
    {
        $('#dirmar_rules_editor_container').hide();
        $('#openpsa_dirmar_edit_query_advanced').parent().addClass('disabled');
    }

    $('#openpsa_dirmar_edit_query_advanced').on('click', function(event)
    {
        event.preventDefault();
        get_rules_array(zero_group_id);
        $('#midcom_helper_datamanager2_dummy_field_rules').show();
        $('#dirmar_rules_editor_container').hide();
        $('#openpsa_dirmar_edit_query').parent().removeClass('disabled');
        $('#openpsa_dirmar_edit_query_advanced').parent().addClass('disabled');
    });
    $('#openpsa_dirmar_edit_query').on('click', function(event)
    {
        event.preventDefault();
        $('#midcom_helper_datamanager2_dummy_field_rules').hide();
        $('#dirmar_rules_editor_container').show();
        $('#openpsa_dirmar_edit_query').parent().addClass('disabled');
        $('#openpsa_dirmar_edit_query_advanced').parent().removeClass('disabled');
    });
}

// function to gather the rules from the form & write them into midcom_helper_datamanager2_dummy_field_rules
function get_rules_array(parent_id)
{
    $("#midcom_helper_datamanager2_dummy_field_rules").empty();

    var type = $("#" + parent_id + "_group").val(),
    array_append = "Array \n ( \n";
    array_append += "'type' => '" + type + "',\n 'classes' => Array \n ( \n";

    $("#midcom_helper_datamanager2_dummy_field_rules").append(array_append);
    get_rules_groups(parent_id, 0);
    array_append = "\n ), ) \n";
    $("#midcom_helper_datamanager2_dummy_field_rules").append(array_append);

    return true;
}

/**
 * function to gather rules for group
 *   parent_id - id to check for childs
 */
function get_rules_groups(parent_id, group_start)
{
    var i_group = group_start,
    array_key, array_append,
    index, type,
    i_rule,
    object,
    property,
    domain, name, match, value;

    for (array_key in groups[parent_id].child_groups)
    {
        index = groups[parent_id].child_groups[array_key];
        type = $("#" + index + "_select").val();
        if (type !== undefined)
        {
            $("#midcom_helper_datamanager2_dummy_field_rules").append(i_group + " => Array \n ( \n");
            i_group = i_group  + 1;
            $("#midcom_helper_datamanager2_dummy_field_rules").append("'type' => '" + type + "',\n 'groups' => '" + type + "',\n 'classes' => Array \n ( \n");
            get_rules_groups(index, i_group + 1 );
            $("#midcom_helper_datamanager2_dummy_field_rules").append("\n ), \n ), \n");
        }
    }
    i_rule = i_group  + 1 ;
    for (array_key in groups[parent_id].child_rules)
    {
        index = groups[parent_id].child_rules[array_key];
        object = $("#" + index + "_object").val();
        if (   object !== undefined
            && object !== "")
        {
            // parameters must be handled differently
            if (object === 'generic_parameters')
            {
                domain  = $("#" + index + "_parameter_domain").val();
                name = $("#" + index + "_parameter_name").val();
                match = $("#" + index + "_match").val();
                value = $("#" + index + "_value").val();

                if (match === 'LIKE' || match === 'NOT LIKE')
                {
                    value = "%" + value + "%";
                }

                array_append = i_rule + " => Array \n ( \n 'type' => 'AND', \n";
                array_append += "'class' => '" + org_openpsa_directmarketing_class_map[object] + "',\n";
                array_append += "'rules' => Array \n ( \n";
                array_append += " 0 => Array \n ( \n";
                array_append += "'property' => 'domain',\n";
                array_append += "'match' => '=',\n";
                array_append += "'value' => '" + String(domain) + "'), ";
                array_append += " 1 => Array \n ( \n";
                array_append += "'property' => 'name',\n";
                array_append += "'match' => '=',\n";
                array_append += "'value' => '" + name + "',\n ), \n ";
                array_append += " 2 => Array \n ( \n";
                array_append += "'property' => 'value',\n";
                array_append += "'match' => '" + match + "',\n";
                array_append += "'value' => '" + value + "',\n ), ";
                array_append += "\n )";
                array_append += "\n ), \n";
            }
            else
            {
                property  = $("#" + index + "_property").val();
                match = $("#" + index + "_match").val();
                value = $("#" + index + "_value").val();

                //only write if property is chosen
                if (   property !== ""
                    && match !== "")
                {
                    if (   match === 'LIKE'
                        || match === 'NOT LIKE')
                    {
                        value = "%" + value + "%";
                    }
                    array_append = i_rule + " => Array \n ( \n 'type' => 'AND', \n";
                    array_append += "'class' => '" + org_openpsa_directmarketing_class_map[object] + "',\n";
                    array_append += "'rules' => Array \n ( \n 0 => Array \n ( \n";
                    array_append += "'property' => '" + property + "',\n";
                    array_append += "'match' => '" + match + "',\n";
                    array_append += "'value' => '" + value + "',\n ) \n )";
                    array_append += "\n ), \n";
                    //array_append += "--------RULE-END ------------ \n";
                }
            }
            i_rule++;
            $("#midcom_helper_datamanager2_dummy_field_rules").append(array_append);
        }
    }
}

/**
 * function to set passed rules (passed by get_old_rules->show-campaign-edit_query.php)
 * @param parent - parent where the rules should be added
 * @param rules_array - array containing the rules
 */
function get_child_rules(parent, rules_array)
{
    var map_class,
    rule_match, rule_value, rule_domain, rule_parameter_value, rule_property, rule_id,
    properties,
    parameters, group_id;

    $.each(rules_array, function (key, value)
    {
        if (value['class'] !== undefined)
        {
            //if class is not supported -> error-msg
            if (org_openpsa_directmarketing_class_map[value['class']] === undefined)
            {
                throw 'unsupported class';
            }
            //old-parameter-case
            if (value.groups && value.rules)
            {
                map_class = org_openpsa_directmarketing_class_map[value['class']];
                rule_domain = value.groups[0].rules[0].property;
                rule_parameter_name = value.groups[0].rules[1].property;
                rule_match = value.groups[0].rules[2].match;
                rule_value = value.groups[0].rules[2].value;
                rule_id = groups[parent].add_rule(map_class);
                if (rule_value.substr(0, 1) === '%')
                {
                    rule_value = rule_value.substr(1);
                }
                if (rule_value.substr(rule_value.length - 1, 1) === '%')
                {
                    rule_value = rule_value.substr(0, rule_value.length -1);
                }
                rules[rule_id].render_parameters_div(rule_domain, rule_parameter_name, rule_match, rule_value);
            }//normal group-case
            else if (value.groups)
            {
                group_id = groups[parent].add_group(value.type);
                get_child_rules(group_id, value.classes);
            }//normal rule-case
            else if (value.rules)
            {
                map_class = org_openpsa_directmarketing_class_map[value['class']];
                rule_match = value.rules[0].match;
                rule_value = value.rules[0].value;
                rule_property = value.rules[0].property;
                rule_id = groups[parent].add_rule(map_class);
                property_class_found = false;
                properties = false;
                parameters = false;
                $.each(org_openpsa_directmarketing_edit_query_property_map, function(key, value)
                {
                    if (key == map_class)
                    {
                        property_class_found = true;
                        properties = value.properties;
                        parameters = value.parameters;
                    }
                });
                if (properties)
                {
                    // get rid of the % in front & at the end of strings
                    if (rule_value.substr(0,1) === '%')
                    {
                        rule_value = rule_value.substr(1);
                    }
                    if (rule_value.substr(rule_value.length - 1, 1) === '%')
                    {
                        rule_value = rule_value.substr(0, rule_value.length -1);
                    }
                     rules[rule_id].render_properties_select(properties, rule_property);
                     rules[rule_id].render_match_selectinput(rule_match, rule_value);
                }
                else if (parameters)
                {
                    rule_match = value.rules[2].match;
                    rule_value = value.rules[2].value;
                    rule_domain = value.rules[0].value;
                    rule_parameter_name = value.rules[1].value;
                    if (rule_value.substr(0,1) === '%')
                    {
                        rule_value = rule_value.substr(1);
                    }
                    if (rule_value.substr(rule_value.length - 1,1) === '%')
                    {
                        rule_value = rule_value.substr(0, rule_value.length -1);
                    }
                    rules[rule_id].render_parameters_div(rule_domain, rule_parameter_name, rule_match, rule_value);
                }
            }
        }
    });
}

$(document).ready(function()
{
    // adds hover effect to group
    $("#org_openpsa_directmarketing_rules_editor")
        .on('mouseenter', '#dirmar_rules_editor_container_group_0 .group', function()
        {
            $(this).addClass("focus");
        })
        .on('mouseleave', '#dirmar_rules_editor_container_group_0 .group', function()
        {
            $(this).removeClass("focus");
        });
});
