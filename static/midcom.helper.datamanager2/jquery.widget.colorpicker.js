jQuery(document).ready(function()
{
    jQuery('input.midcom_helper_datamanager2_colorpicker').each(function(i)
    {
        var preset_color = (jQuery(this).attr('value')) || '#ffffff';
        var options = {
            flat: true,
            id: jQuery(this).attr('id'),
            color: preset_color,
            onChange: function(hsb, hex, rgb)
            {
                switch (true)
                {
                    case (jQuery(this).parent().find('input.midcom_helper_datamanager2_colorpicker').hasClass('hsb')):
                        var value = 'hsb(' + hsb.h + ',' + hsb.s + ',' + hsb.b + ')';
                        jQuery(this).parent().find('input.midcom_helper_datamanager2_colorpicker').attr('value', value);
                        break;
                    
                    case (jQuery(this).parent().find('input.midcom_helper_datamanager2_colorpicker').hasClass('hex')):
                        jQuery(this).parent().find('input.midcom_helper_datamanager2_colorpicker').attr('value', '#' + hex);
                        break;
                    
                    default:
                        var value = 'rgb(' + rgb.r + ',' + rgb.g + ',' + rgb.b + ')';
                        jQuery(this).parent().find('input.midcom_helper_datamanager2_colorpicker').attr('value', value);
                        break;
                }
                
            }
        }
        
        jQuery(this).parent().ColorPicker(options);
    });
});