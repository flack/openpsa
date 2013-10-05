$(document).ready(function()
{
    var storage_available = (typeof window.localStorage !== 'undefined' && window.localStorage)
    if (storage_available)
    {
        $('#save-script').on('click', function(event)
        {
            event.preventDefault();
            var script = window.editors["org_openpsa_mypage_code"].getValue();
            window.localStorage.setItem('saved-script', script);
            $('#restore-script').removeClass('disabled');
        });
        $('#restore-script').on('click', function(event)
        {
            event.preventDefault();
            var script = window.localStorage.getItem('saved-script');
            if (script)
            {
                window.editors["org_openpsa_mypage_code"].setValue(script);
            }
        });
        $('#clear-script').on('click', function(event)
        {
            event.preventDefault();
            window.localStorage.removeItem('saved-script');
            $('#restore-script').addClass('disabled');
            window.editors["org_openpsa_mypage_code"].setValue('');
        });
        if (!window.localStorage.getItem('saved-script'))
        {
            $('#restore-script').addClass('disabled');
        }
    }
    else
    {
        $('#save-script, #restore-script, #clear-script').hide();
    }
});