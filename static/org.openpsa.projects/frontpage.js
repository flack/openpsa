function get_tasks_json(object, url) {
    var wait_html = "<tr id='child_" + $(object).attr('id') +"'><td colspan ='3' style='text-align:center'><img class='loading' src='" + MIDCOM_STATIC_URL + "/stock-icons/32x32/ajax-loading.gif' alt='loading' /></td></tr>",
        row = $(object).parent().parent();
    row.after(wait_html);
    $.ajax({
        type: "GET",
        url: url,
        dataType: "json",
        success: function(json) {
            $("#child_" + $(object).attr('id')).remove();
            if (json.length > 0) {
                var max_rows = json.length,
                    html = "",
                    tr_class = $(object).parent().parent().attr('class'),
                    indent_class = 'expand-icon';

                if (   (!$(object).parent().parent().next())
                    || $(object).parent().parent().next().children('th')[0]) {
                    indent_class = 'hidden-icon';
                }

                //iterate through tasks
                json.forEach(function(task, index) {
                    // new row
                    if (tr_class == 'even') {
                        tr_class = 'odd';
                    } else {
                        tr_class = 'even';
                    }
                    html += "<tr class='" + tr_class + " child_" + $(object).attr('id') + "'>\n";
                    //icons
                    html += "<td class='multivalue'>";
                    html += "<img class='" + indent_class + "' src='" + MIDCOM_STATIC_URL + "/stock-icons/16x16/line.png' /> ";
                    if (index == (max_rows - 1)) {
                        html += "<img class='expand-icon' src='" + MIDCOM_STATIC_URL + "/stock-icons/16x16/branchbottom.png' /> ";
                    } else {
                        html += "<img class='expand-icon' src='" + MIDCOM_STATIC_URL + "/stock-icons/16x16/branch.png' /> ";
                    }
                    //title & link
                    html += task.title;
                    html += "</td>\n";
                    //dates
                    html += "<td>";
                    html += task.start;
                    html += "</td>\n";
                    html += "<td>";
                    html += task.end;
                    html += "</td>\n";
                    //sub-tasks ?
                    html += "<td>";
                    html += "</td>\n";
                    html += "<td></td><td></td><td></td>";
                    //workinghours
                    html += "<td class='numeric' >";
                    html += "<span >" + task.reported_hours + "</span> ";
                    if (task.planned_hours > 0) {
                        html += "/ <span>" + task.planned_hours + "</span>";
                    }
                    html += "</td>\n";
                });
                row.after(html);
            }
        },
        error: function(data) {
            //html = "<tr class='child_" + $(object).attr('id') +"'><td colspan='6'>No Tasks</td></tr>";
            //$(row).after(html);
        }
    });
}

function show_tasks_for_project(object, url) {
    var position = '';
    if ($(".child_" + $(object).attr('id')).length == 0) {
        $(".child_" + $(object).attr('id')).remove();
        if (   (!$(object).parent().parent().next())
            || $(object).parent().parent().next().children('th')[0]) {
            position = 'bottom';
        }

        $(object).attr('src', MIDCOM_STATIC_URL + "/stock-icons/16x16/minus" + position + ".png");
        get_tasks_json(object, url);
    } else {
        $(".child_" + $(object).attr('id')).remove();
        if (   !$(object).parent().parent().next()
            || $(object).parent().parent().next().children('th')[0]) {
            position = 'bottom';
        }
        $(object).attr('src', MIDCOM_STATIC_URL + "/stock-icons/16x16/plus" + position + ".png");
    }
}
