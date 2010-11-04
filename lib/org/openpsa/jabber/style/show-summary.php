<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="area">
    <h2><?php echo $data['l10n']->get('org.openpsa.jabber'); ?></h2>

    <p>
        <a href="#" onclick="window.open('<?php echo $prefix; ?>applet/','JabberApplet','width=200,height=300,location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no');"><?php echo $data['l10n']->get('open jabber applet'); ?></a>
    </p>
</div>