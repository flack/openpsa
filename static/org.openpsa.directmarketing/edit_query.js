
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

// adds hover effect to group
function group_hover()
{
    $("#" + this.id).hover(
        //onhover
        function()
        {
            $(this).addClass("focus");
        },
        //hover out
        function()
        {
            $(this).removeClass("focus");
        }
    );
}


function send_preview()
{
    var loading = "<img style='text-align:center;' src='" + MIDCOM_STATIC_URL + "/midcom.helper.datamanager2/ajax-loading.gif'/>";
    $("#preview_persons").css("text-align", "center");
    $("#preview_persons").html(loading);
    get_rules_array(zero_group_id);
    var rules_array = $("#midcom_helper_datamanager2_dummy_field_rules").val(),
    post_data = {show_rule_preview: true, midcom_helper_datamanager2_dummy_field_rules: rules_array};
    $.post(document.URL, post_data,
              function(data){
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
    rules[String(this.id)] = null;
    delete(rules[String(this.id)]);
    //check if this was last rule
    if (count_child_rules === 1)
    {
        if (this.parent !== zero_group_id && count_child_groups === 0)
        {
            //remove parent group from child_groups of the parent group of the parent
            groups[groups[this.parent].parent].child_groups[this.parent] = null;
            delete(groups[groups[this.parent].parent].child_groups[this.parent]);
            groups[this.parent].remove();
            delete(groups[this.parent]);
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
            rules[this.child_rules[rule_key]] = null;
            delete(rules[this.child_rules[rule_key]]);
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
            groups[this.child_groups[group_key]] = null;
            delete(groups[this.child_groups[group_key]]);
        }
    }

    //check if there is a group in front & no group behind
    if (   $("#" + this.id).prev(".group").length > 0
        && $("#" + this.id).next(".group").length < 1)
    {
        $("#" + this.id).prev(".group").removeClass("nobottom", "0px");
    }

    $("#" + this.id).remove();
    groups[String(this.id)] = null;
    delete(groups[String(this.id)]);
}
function render_match_selectinput(match, value)
{
    var html = '',
    input_id = this.id + '_match',
    input_name = this.id + '[match]';

    if ($("#" + input_id).length < 1 )
    {
        html += '<select class="select" name="' + input_name + '" id="' + input_id + '">';
        $.each(org_openpsa_directmarketing_edit_query_match_map, function(key, value)
        {
            if (match === key)
            {
                html += '<option selected="selected" value="' + key + '">' + value + '</option>';
            }
            else
            {
                html += '<option value="' + key + '">' + value + '</option>';
            }
        });
        html += '</select>';

        var input_id2 = this.id + '_value',
        input_name2 = this.id + '[value]';
        if (value === false)
        {
            value = "";
        }
        html += '<input type="text" class="shorttext" value ="' + value + '" name="' + input_name2 + '" id="' + input_id2 + '" >';
        $("#" + input_id).remove();
        $("#" + input_id2).remove();

        if ($("#" + this.id + "_object").val() === 'generic_parameters')
        {
            $("#" + this.id + "_parameter_name").after(html);
        }
        else
        {
            $("#" + this.id + "_property").after(html);
        }
    }
}

function property_select_onchange(select)
{
    this.render_match_selectinput(false, false);
}

function render_properties_select(properties, selected)
{
    var html = '',
    input_id = this.id + '_property',
    input_name = this.id + '[property]',
    jscall = "rules['" + String(this.id) +"'].property_select_onchange(this)";

    html += '<select class="select" name="' + input_name + '" id="' + input_id + '" onchange="' + jscall +'">';
    html += '<option value=""></option>';
    $("#" + this.id + "_property").remove();
    $("#" + this.id + "_parameter_domain").remove();
    $("#" + this.id + "_parameter_name").remove();
    $.each(properties, function(key, value)
    {
        if (   selected !== false
            && selected === key)
        {
            html += '<option selected="selected" value="' + key + '">' + value + '</option>';
        }
        else
        {
            html += '<option value="' + key + '">' + value + '</option>';
        }
    });
    html += '</select>';
    $("#" + this.id + "_object").after(html);
}

function object_select_onchange(object)
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
    //var rule_id = this.id;
    var html = "<div id='" + this.id + "' class='rule'> </div>",
    parent_field = "<input type=\"hidden\" name=\"" + this.id + "[parent]\" value=\"" + this.parent + "\"/>";
    $("#" + this.parent + "_add_group").before(html);
    var object_select = $('<select>')
        .attr(
        {
            id: this.id + '_object',
            name: this.id + '[object]'
        })
        .addClass('select')
        .change( function()
        {
            var rule_id = $(this).parent().attr("id");
            rules[rule_id].object_select_onchange(this);
        });

    html = '<option value=""></option>';
    $.each(org_openpsa_directmarketing_edit_query_property_map, function(key, value)
    {
        if (selected !== false && selected === key)
        {
            html += '<option selected="selected" value="' + key + '">' + value.localized + '</option>';
        }
        else
        {
            html += '<option value="' + key + '">' + value.localized + '</option>';
        }
    });
    object_select.html(html);

    $("#" + this.id).append(object_select);
    html = "";

    html += "<img src=\"" + MIDCOM_STATIC_URL + "/stock-icons/16x16/list-remove.png\"  class=\"button remove_row\" onclick=\"rules['" + this.id + "'].remove(); return false;\" />";

    html += "<img id =\"" + this.id + "_add\" type=\"image\" src=\"" + MIDCOM_STATIC_URL + "/stock-icons/16x16/list-add.png\"  class=\"button add_row\" onclick=\"$(this).remove();groups['" + this.parent + "'].add_rule(); return false;\" />";
    html += parent_field;
    $("#" + this.id).append(html);

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
    this.property_select_onchange = property_select_onchange;
    this.render_match_selectinput = render_match_selectinput;
    this.remove = remove_rule;
}

function render_group(selected)
{
    var parent_field = "<input type=\"hidden\" name=\"" + this.id + "[parent]\" value=\"" + this.parent + "\"/>",
    group_select = "<select class=\"groupselect\" name=\"" + this.id + "[group]\" id=\"" + this.id + "_select\">",
    group_button = "<br /> <input id=\"" + this.id + "_add_group\" class=\"add_group\" type=\"button\" value=\"" + org_openpsa_directmarketing_edit_query_l10n_map.add_group + "\" onclick=\"groups['" + this.id + "'].add_group(false);\">",
    // no remove for first group
    group_remove_button = "";
    if (this.id !== "dirmar_rules_editor_container_group_0")
    {
        group_remove_button = "<input id=\"" + this.id + "_remove_group\" class=\"remove_group\" type=\"button\" value=\"" + org_openpsa_directmarketing_edit_query_l10n_map.remove_group + "\" onclick=\"groups['" + this.id + "'].remove();\">";
    }

    $.each(org_openpsa_directmarketing_group_select_map, function(key, value)
    {
        //check for selected
        if (key === selected)
        {
            group_select += '<option selected="selected" value="' + key + '">' + value + '</option>';
        }
        else
        {
            group_select += '<option value="' + key + '">' + value + '</option>';
        }
    });
    group_select += '</select>';
    var content_group ="<div id='" + this.id + "' class='group'>";
    content_group += group_remove_button;
    content_group += group_select;

    content_group += group_button;
    content_group += parent_field;
    content_group +="</div>";
    if (this.id !== "dirmar_rules_editor_container_group_0")
    {
        $("#" + this.parent + "_add_group").before(content_group);
        this.hover();
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

    if ($("#" + index).prev().children(".remove_row").length < 1)
    {
        remove_button = "<img  src=\"" + MIDCOM_STATIC_URL + "/stock-icons/16x16/list-remove.png\"  class=\"button remove_row\" onclick=\"rules['" + index + "'].remove(); return false;\" />";
    }
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
    this.hover = group_hover;

    this.child_groups = [];
    this.child_rules = [];
}
/**
 * function to setup first group
 *@param id - contains string to build name of the first group
 *@param selected - array with type of group -> AND or OR
 */
function first_group(id, selected)
{
    var index = String(id + "_group_0");
    zero_group_id = index;
    groups[index] = new group(id, 0);
    groups[index].render(selected);

    return index;
}

// function to gather the rules from the form & write them into midcom_helper_datamanager2_dummy_field_rules
function get_rules_array(parent_id)
{
    var type = $("#" + parent_id + "_select").val();
    $("#midcom_helper_datamanager2_dummy_field_rules").empty();
    array_append = "Array \n ( \n";
    array_append += "'type' => '" + type + "', 'groups' => '" + type + "',\n 'classes' => Array \n ( \n";
    $("#midcom_helper_datamanager2_dummy_field_rules").append(array_append);
    get_rules_groups(parent_id, true, 0 );
    array_append = "\n ), ) \n";
    $("#midcom_helper_datamanager2_dummy_field_rules").append(array_append);

    return true;
}

/**
 * function to gather rules for group
 *   parent_id - id to check for childs
 *   TODO: check for remove of first & id
 *   first - bool if this is the first group ( not needed anymore !?)
 *   id - also not needed anymore ?
 */
function get_rules_groups(parent_id, first, group_start)
{
    var result = "",
    i_group = group_start,
    _key,
    index,
    type,
    i_rule,
    object,
    property,
    match,
    value;
    console.log(groups);
console.log(parent_id);
    for (_key in groups[parent_id].child_groups)
    {
        index = groups[parent_id].child_groups[_key];
        type = $("#" + index + "_select").val();
        if (type !== undefined)
        {
            $("#midcom_helper_datamanager2_dummy_field_rules").append(i_group + " => Array \n ( \n");
            i_group = i_group  + 1;
            $("#midcom_helper_datamanager2_dummy_field_rules").append("'type' => '" + type + "',\n 'groups' => '" + type + "',\n 'classes' => Array \n ( \n");
            get_rules_groups(groups[parent_id].child_groups[_key], false, i_group + 1 );
            $("#midcom_helper_datamanager2_dummy_field_rules").append("\n ), \n ), \n");
        }
    }
    i_rule = i_group  + 1 ;
    for (_key in groups[parent_id].child_rules)
    {
        index = groups[parent_id].child_rules[_key];
        object = $("#" + index + "_object").val();
        if (   object !== undefined
            && object !== "")
        {
            // parameters must be handled differently
            if (object === 'generic_parameters')
            {
                var domain  = $("#" + index + "_parameter_domain").val(),
                name = $("#" + index + "_parameter_name").val(),
                match = $("#" + index + "_match").val(),
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
    var key,
    value,
    map_class,
    rule_match,
    rule_value,
    rule_property,
    rule_id,
    property_class_found,
    properties,
    parameters;

    for (key in rules_array)
    {
        value = rules_array[key];
        if (value['class'] !== 'undefined')
        {
            //if class is not supported -> error-msg
            if (   value['class'] !==  undefined
                && org_openpsa_directmarketing_class_map[value['class']] === undefined)
            {
                $('<div></div>').attr({
                    id: 'midcom_services_uimessages_wrapper'
                    }).appendTo('body');
                var error_message = error_message_class  + "\n Class : " + value['class'] + "\n <a href='" + window.location.href.replace(/edit_query/g, 'edit_query_advanced') + "'>\n Advanced Editor </a>";
                $('#midcom_services_uimessages_wrapper').midcom_services_uimessage({title: 'org.openpsa.directmarketing', message: error_message, type: 'error'})
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
                if (rule_value.substr(0,1) === '%')
                {
                    rule_value = rule_value.substr(1);
                }
                if (rule_value.substr(rule_value.length - 1,1) === '%')
                {
                    rule_value = rule_value.substr(0, rule_value.length -1);
                }
                rules[rule_id].render_parameters_div(rule_domain, rule_parameter_name, rule_match, rule_value);
            }//normal group-case
            else if (value.groups)
            {
                var group_id = groups[parent].add_group(value.type);
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
                     rules[rule_id].render_match_selectinput(rule_match, rule_value)
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
    }
}
