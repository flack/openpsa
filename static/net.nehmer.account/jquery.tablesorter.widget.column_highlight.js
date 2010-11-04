(function($)
{
    $.tablesorter.addWidget({
        id: 'column_highlight',
        format: function(table)
        {
            jQuery(this).find('tbody tr:odd').each(function(i)
            {
                jQuery(this).removeClass('even');
                jQuery(this).addClass('odd');
            });
            
            jQuery(this).find('tbody tr:even').each(function(i)
            {
                jQuery(this).removeClass('odd');
                jQuery(this).addClass('even');
            });
        }
    });
})(jQuery);
