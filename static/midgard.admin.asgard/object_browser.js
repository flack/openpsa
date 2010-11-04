jQuery(document).ready(function()
{
    // Add ajax to each Thickbox link. This will tell to Asgard that it
    // should show only the object view style and nothing else.
    jQuery('a.thickbox').each(function(i)
    {
        var link = jQuery(this).attr('href');
        
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
        jQuery(this).attr('href', link);
        jQuery(this).attr('target', '_self');
    });
});