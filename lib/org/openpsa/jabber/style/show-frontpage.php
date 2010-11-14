<?php
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="main">
    <div class="area">
        <h2><?php echo $data['l10n']->get('org.openpsa.jabber'); ?></h2>

        <p>
            <a href="#" onclick="window.open('<?php echo $node[MIDCOM_NAV_FULLURL]; ?>applet/','JabberApplet','width=200,height=300,location=no,menubar=no,resizable=no,scrollbars=no,status=no,toolbar=no');"><?php echo $data['l10n']->get('open jabber applet'); ?></a>
        </p>
    </div>
</div>
<div class="sidebar">
    <div class="area">
        <h2><?php echo $data['l10n_midcom']->get("instructions"); ?></h2>
        <p><?php echo $data['l10n']->get("instructions for jabber"); ?></p>
    </div>
</div>