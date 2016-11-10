<?php
$component = $data['component_data'];
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>

<div class="midgard_admin_asgard_components_component">
    <div class="maintainers">
        <?php
        if (count($component['maintainers']) > 0) {
            echo "<h3>" . $data['l10n']->get('created by') . "</h3>\n";
            echo "<ul>\n";

            foreach ($component['maintainers'] as $username => $maintainer) {
                $status = 'active';
                if (   isset($maintainer['active'])
                    && $maintainer['active'] == 'no') {
                    $status = 'passive';
                }

                $gravatar_url  = "http://www.gravatar.com/avatar.php?gravatar_id=" . md5($maintainer['email']) . "&amp;size=20";
                $gravatar_url .= '&amp;default=' . urlencode(midcom::get()->get_host_prefix() . 'midcom-static/stock-icons/16x16/stock_person.png');

                echo "<li class=\"{$status} vcard\"><span class=\"photoarea\"><img class=\"photo\" src=\"{$gravatar_url}\" /></span><a href=\"mailto:{$maintainer['email']}\" class=\"email\"><span class=\"fn\">{$maintainer['name']}</a></a> (<a href=\"https://github.com/{$username}/\">{$username}</a>)</li>\n";
            }

            echo "</ul>\n";
        }
        ?>
    </div>

    <h2><img src="<?php echo MIDCOM_STATIC_URL; ?>/&(component['icon']);" alt="" /> &(component['name']);</h2>

    <div class="meta">
        <p class="version">
            <?php echo $component['version']; ?>
        </p>

        <p class="description">&(component['title']);</p>

        <?php
        if (count($data['component_dependencies']) > 0) {
            echo "<h2>" . $data['l10n_midcom']->get('component depends on') . "</h2>\n";
            echo "<ul>\n";
            foreach ($data['component_dependencies'] as $dependency) {
                if (substr($dependency, 0, 9) == 'template_') {
                    echo "<li><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/text-x-generic-template.png\" alt=\"\" /> {$dependency}</li>\n";
                    continue;
                    // Done.
                }
                if ($component_icon = midcom::get()->componentloader->get_component_icon($dependency)) {
                    echo "<li><a href=\"{$prefix}__mfa/asgard/components/{$dependency}/\"><img src=\"" . MIDCOM_STATIC_URL . "/" . $component_icon . "\" alt=\"\" /> {$dependency}</a></li>\n";
                } else {
                    echo "<li><img src=\"" . MIDCOM_STATIC_URL . "/stock-icons/16x16/cancel.png\" alt=\"\" /> {$dependency} <span class='alert'>Error: This component is not installed!</span></li>\n";
                }
            }
            echo "</ul>\n";
        }
        ?>
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