$(document).ready(function()
{
    // Add ajax to each Thickbox link. This will tell to Asgard that it
    // should show only the object view style and nothing else.
    $('a.thickbox').each(function(i)
    {
        var link = $(this).attr('href');

        if (!link)
        {
            link = '';
        }

        if (link.match(/\?/))
        {
            link = link.replace(/\?/, '?ajax&');
        }
        else
        {
            link += '?ajax';
        }

        // Convert the link to use thickbox
        $(this).attr('href', link);
        $(this).attr('target', '_self');
    });
    $('a.thickbox').colorbox({maxHeight: '90%', maxWidth: '90%', fixed: true});
});