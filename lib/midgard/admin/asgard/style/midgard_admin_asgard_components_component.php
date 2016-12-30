<?php
$component = $data['component_data'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<div class="midgard_admin_asgard_components_component">

    <h2><img src="<?php echo MIDCOM_STATIC_URL; ?>/&(component['icon']);" alt="" /> &(component['name']);</h2>

    <div class="meta">
        <p class="description">&(component['title']);</p>

    </div>

    <div class="help">
        <?php
        $help = new midcom_admin_help_help();
        $files = $help->list_files($data['component'], true);
        if (count($files) > 0) {
            echo "<h3>" . midcom::get()->i18n->get_string('component help', 'midcom.admin.help') . "</h3>\n";
            echo "<ul>\n";
            foreach ($files as $identifier => $filedata) {
                if ($identifier == 'index') {
                    $identifier = '';
                }
                echo "<li><a href=\"{$prefix}__ais/help/{$data['component']}/{$identifier}/\" target='_blank'>{$filedata['subject']}</a></li>\n";
            }
            echo "</ul>\n";
        }
        ?>
    </div>
</div>