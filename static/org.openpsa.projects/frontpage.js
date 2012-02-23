function get_tasks_json(object , url , prefix)
{
    var wait_html = "<tr id='child_" + $(object).attr('id') +"'><td colspan ='3' style='text-align:center'><img class='loading' src='" + MIDCOM_STATIC_URL + "/stock-icons/32x32/ajax-loading.gif' alt='loading' /></td></tr>";
    row = $(object).parent().parent();
    row.after(wait_html);
    $.ajax({
           type: "GET",
           url: url,
           dataType: "json",
           success: function(json)
           {
                $("#child_" + $(object).attr('id')).remove();
                if (json.length > 0)
                {
                    var max_rows = 0;
                    for (var task_category = 0; task_category < json.length; task_category++)
                    {
                        if (json[task_category] == 0)
                        {
                            continue;
                        }
                        for (var i = 0; i < json[task_category].length; i++)
                        {
                            max_rows++;
                        }
                    }
                    html = "";
                    var tr_class = $(object).parent().parent().attr('class');
                    var add_row = 1;
                    var indent_class = 'expand-icon';
                    var expand_icon = '';
                    if (   (!$(object).parent().parent().next())
                        || $(object).parent().parent().next().children('th')[0])
                    {
                        indent_class = 'hidden-icon';
                    }

                    //iterate through category of tasks
                    for (var task_category = 0; task_category < json.length; task_category++)
                    {
                        if (json[task_category] == 0)
                        {
                            continue;
                        }
                        //iterate through tasks
                        for (var task_number = 0; task_number < json[task_category].length; task_number++)
                        {
                            // new row
                            if (tr_class == 'even')
                            {
                                tr_class = 'odd';
                            }
                            else
                            {
                                tr_class = 'even';
                            }
                            html += "<tr class='" + tr_class + " child_" + $(object).attr('id') + "'>\n";
                            //icons
                            html += "<td class='multivalue'>";
                            html += "<img class='" + indent_class + "' src='" + MIDCOM_STATIC_URL + "/stock-icons/16x16/line.png' /> ";
                            if (add_row == max_rows)
                            {
                                html += "<img class='expand-icon' src='" + MIDCOM_STATIC_URL + "/stock-icons/16x16/branchbottom.png' /> ";
                            }
                            else
                            {
                                html += "<img class='expand-icon' src='" + MIDCOM_STATIC_URL + "/stock-icons/16x16/branch.png' /> ";
                            }
                            add_row++;
                            html += "<img class='status-icon' src='" + json[task_category][task_number]['icon_url'] + "' />";
                            //title & link
                            html += "<a href='" + prefix + json[task_category][task_number]['guid'] + "'>" + json[task_category][task_number]['title'] + "</a>";
                            html += "</td>\n";
                            //dates
                            html += "<td>";
                            html += json[task_category][task_number]['start'];
                            html += "</td>\n";
                            html += "<td>";
                            html += json[task_category][task_number]['end'];
                            html += "</td>\n";
                            //sub-tasks ?
                            html += "<td>";
                            html += "</td>\n";
                            html += "<td></td><td></td><td></td>";
                            //workinghours
                            html += "<td class='numeric' >";
                            html += "<span >" + json[task_category][task_number]['reported_hours'] + "</span> ";
                            if (json[task_category][task_number]['planned_hours'] > 0)
                            {
                                html += "/ <span>" + json[task_category][task_number]['planned_hours'] + "</span>";
                            }
                            html += "</td>\n";
                        }
                    }
                    row.after(html);
                }
            },
            error: function(data)
            {
                //html = "<tr class='child_" + $(object).attr('id') +"'><td colspan='6'>No Tasks</td></tr>";
                //$(row).after(html);
            }
    });
}

function show_tasks_for_project(object, url, prefix)
{
    var position = '';
    if ($(".child_" + $(object).attr('id')).length == 0)
    {
        $(".child_" + $(object).attr('id')).remove();
        if ((!$(object).parent().parent().next())
            || $(object).parent().parent().next().children('th')[0])
        {
            position = 'bottom';
        }

        $(object).attr('src' , MIDCOM_STATIC_URL + "/stock-icons/16x16/minus" + position + ".png");
        get_tasks_json(object , url , prefix);
    }
    else
    {
        $(".child_" + $(object).attr('id')).remove();
        if ((!$(object).parent().parent().next())
            || $(object).parent().parent().next().children('th')[0])
        {
            position = 'bottom';
        }
        $(object).attr('src' , MIDCOM_STATIC_URL + "/stock-icons/16x16/plus" + position + ".png");
    }
}
