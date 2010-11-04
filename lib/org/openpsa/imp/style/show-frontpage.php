<?php
//$data =& $_MIDCOM->get_custom_context_data('request_data');
$nap = new midcom_helper_nav();
$node = $nap->get_node($nap->get_current_node());
?>
<div class="main">
    <div class="area">
        <h2><?php echo $data['l10n']->get('org.openpsa.imp'); ?></h2>
        <!-- Done without JS toolbar etc hiding since in some case IE simply refuses to allow scrollbars in such windows -->
        <p><a href="<?php echo $node[MIDCOM_NAV_FULLURL]; ?>redirect/" target="_BLANK"><?php echo $data['l10n']->get('open horde/imp'); ?></a>
    </div>
</div>
<div class="sidebar">
    <div class="area">
        <h2><?php echo $data['l10n_midcom']->get("instructions"); ?></h2>
        <p>TODO: instructions</p>
    </div>
</div>