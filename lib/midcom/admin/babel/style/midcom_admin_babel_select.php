<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$languages = $data['l10n']->_language_db;
$curlang = $_MIDCOM->i18n->get_current_language();
?>
<h1><?php echo $data['l10n']->get('select language to translate')?></h1>

<table class="midcom_admin_babel_languages">
    <thead>
        <tr class="header">
            <th><?php echo $data['l10n']->get('language'); ?></th>
            <th><?php echo $data['l10n']->get('core component status'); ?></th>
            <th><?php echo $data['l10n']->get('other component status'); ?></th>
        </tr>
    </thead>
    <tbody>
        <?php
        foreach ($languages as $language => $language_info)
        {
            $language_name = $data['l10n']->get($language_info['enname']);

            // Calculate status
            $state = midcom_admin_babel_plugin::calculate_language_status($language);
            $percentage = round(100 / $state['strings_core']['total'] * $state['strings_core']['translated']);
            $percentage_other = round(100 / $state['strings_other']['total'] * $state['strings_other']['translated']);

            if ($percentage >= 96)
            {
                $status = 'ok';
            }
            elseif ($percentage >= 75)
            {
                $status = 'acceptable';
            }
            else
            {
                $status = 'bad';
            }

            echo "        <tr class=\"{$status}\">\n";
            echo "            <th class=\"component\"><a href=\"{$prefix}__mfa/asgard_midcom.admin.babel/status/{$language}/\">{$language_name}</a></th>\n";
            echo "            <td title=\"{$state['strings_core']['translated']} / {$state['strings_core']['total']}\">{$percentage}%</td>\n";
            echo "            <td title=\"{$state['strings_other']['translated']} / {$state['strings_other']['total']}\">{$percentage_other}%</td>\n";
            echo "        </tr>\n";
        }
        ?>
    </tbody>
</table>

<p>
    <?php
    echo $data['l10n']->get('read information from midgard wiki on how to add languages');
    ?>
</p>