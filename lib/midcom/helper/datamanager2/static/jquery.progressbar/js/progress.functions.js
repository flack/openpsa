

// fades in the progress bar and starts polling the upload progress after 1.5seconds
function beginUpload(progress_key,url) {
    
    $(".progressbar").css("visibility","visible");
    $(".progressbar").css("position","absolute");
    
    function_string = "showUpload('"+progress_key+"','"+url+"')";

    setTimeout(function_string, 1000);

}
 
// uses ajax to poll the uploadprogress.php page with the id
// deserializes the json string, and computes the percentage (integer)
// update the jQuery progress bar
// sets a timer for the next poll in 750ms
function showUpload(progress_key, url) {
    key_url = url + "?id=" + progress_key;
    var timeout = 1200;
    $.get(key_url, function(data) {

        if (!data)
        {
            function_string = "showUpload('" + progress_key + "','" + url + "')";
            setTimeout(function_string, 10000);
            return;
        }
 
        var response;
        eval ("response = " + data);
   
        if (!response)
        {
            function_string = "showUpload('" + progress_key + "','" + url + "')";
            setTimeout(function_string, timeout);
            return;
        }
        var percentage = Math.floor(100 * parseInt(response['current']) / parseInt(response['total']));
        var mb = parseInt(response['total']) / 1000000;
        if(mb > 3)
        {
            timeout = (mb / 2) *1000;
            if(timeout > 6000)
            {
            	timeout = 60000;
            }
        }

        $(".progressbar").progressBar(percentage);

        if (percentage < 100)
        {
        	function_string = "showUpload('" + progress_key + "','" + url + "')";
        	setTimeout(function_string, timeout);
        }
    });

}
 

