<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="wide">
    <div class="area">
        <h1><?php echo $data['l10n']->get('org.openpsa.reports'); ?></h1>
    <?php
        foreach ($data['available_components'] as $component => $loc)
        {
            $parts = explode('.', $component);
            $last = array_pop($parts);
            $data['report_prefix'] = "{$prefix}{$last}/";
            echo "            <h2>{$loc}</h2>\n";
            midcom_show_style("show-{$component}-quick_reports");
        }
    ?>
    </div>
</div>