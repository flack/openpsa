
function show_loading(show)
{
    var html_data = "";
    if(show == true || show == 'true')
    {
        $('#working_invoiceable_div').hide();
        $('#working_description').hide();
        $('#working_task').hide();
        $('#org_openpsa_mypage_workingon_time').hide();
        html_data = "<img src='"+MIDCOM_STATIC_URL+"/midcom.helper.datamanager2/ajax-loading.gif' alt='loading' />";
    }
    else
    {
        $('#working_invoiceable_div').show();
        $('#working_description').show();
        $('#working_task').show();
        $('#org_openpsa_mypage_workingon_time').show();
    }

    $('#loading').html(html_data);
}

function get_working_on(data , task)
{
    get_working_on_week();
    var time = data.documentElement.getElementsByTagName("start")[0].firstChild.nodeValue;

    if( time != 0 || time != undefined)
    {
        if(task != "none")
        {
            jQuery('#org_openpsa_mypage_workingon_time').epiclock({
                                      mode: EC_COUNTUP,
                                      target: time * 1000 ,
                                      format: 'x:i:s'
                                });
                                jQuery.epiclock(EC_RUN);
        }
    }
}

function send_working_on()
{
    var description = $("#working_description").serialize();
    var task = $("#working_task").attr("value");
    var invoiceable = false;
    var task_before = $("#task_before").attr("value");
    
    if ($('#working_invoiceable').is(':checked'))
    {
        invoiceable = true;
    }
    var send_url = MIDCOM_PAGE_PREFIX+"workingon/set/";
    if(task_before != "none")
    {
        $('#working_invoiceable').attr("checked" , false);
        $('#working_description').attr("value" , "");
    }
    $('#org_openpsa_mypage_workingon_time').empty();

    $("#task_before").attr("value", task);
    $.ajax({
       type: "POST",
       url: send_url,
       data: description+"&task="+task+"&invoiceable="+invoiceable,
       success: function(msg){
         get_working_on(msg , task);
       },
        error: function(msg, a, b){
           location.href = location.href;
       },
       beforeSend: function(msg){
           show_loading(true);
       },
       complete: function(msg){
           show_loading(false);
       }
     });

}

function get_working_on_week()
{
    var url = MIDCOM_PAGE_PREFIX+"today/expenses/";
    $.get(url, function(data){
        $("#content_expenses").html(data);
    });

}