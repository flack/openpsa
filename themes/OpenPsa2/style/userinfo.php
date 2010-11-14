<?php
if ($_MIDCOM->auth->user)
{
    echo "<ul>\n";
    echo "    <li class=\"user\">" . $_MIDCOM->auth->user->name . "</li>\n";
    echo "    <li class=\"logout\"><a href=\"{$_MIDGARD['self']}midcom-logout-\"><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/exit.png\" title=\"" . $_MIDCOM->i18n->get_string('logout', 'midcom') . "\" alt=\"" . $_MIDCOM->i18n->get_string('logout', 'midcom') . "\" /></a></li>\n";
    echo "    <li class=\"midgard\"><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/logos/midgard-16x16.png\" alt=\"X\" id=\"org_openpsa_toolbar_trigger\" /></li>\n";
    echo "</ul>\n";
}
?>

<script type="text/javascript">
jQuery('#org_openpsa_toolbar_trigger').bind('click', function(e){
    if (jQuery('#org_openpsa_toolbar_trigger').hasClass('active'))
    {
        jQuery('body div.midcom_services_toolbars_fancy').hide();
        jQuery('#org_openpsa_toolbar_trigger').removeClass('active');
        jQuery('#org_openpsa_toolbar_trigger').addClass('inactive');
    }
    else if (jQuery('#org_openpsa_toolbar_trigger').hasClass('inactive'))
    {
        jQuery('body div.midcom_services_toolbars_fancy').show();
        jQuery('#org_openpsa_toolbar_trigger').removeClass('inactive');
        jQuery('#org_openpsa_toolbar_trigger').addClass('active');
    }
    else
    {
        var head = document.getElementsByTagName('head')[0];
        jQuery(document.createElement('link')).attr({
            type: 'text/css',
            href: '<?php echo MIDCOM_STATIC_URL ?>/midcom.services.toolbars/fancy.css',
            rel: 'stylesheet',
            media: 'screen, projection'
            }).appendTo(head);
        jQuery.getScript('<?php echo MIDCOM_STATIC_URL ?>/midcom.services.toolbars/jquery.midcom_services_toolbars.js', function(){
            jQuery.getScript('<?php echo MIDCOM_STATIC_URL ?>/jQuery/jquery.easydrag-1.4.js', function(){
                jQuery('body div.midcom_services_toolbars_fancy').midcom_services_toolbar({});
            })
        });
        jQuery('#org_openpsa_toolbar_trigger').addClass('active');
    }
});
</script>
