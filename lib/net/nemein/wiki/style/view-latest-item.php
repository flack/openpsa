<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$page = $data['wikipage'];
$history = $data['history'];

$version_string = "<a href=\"{$prefix}__ais/rcs/preview/{$page->guid}/{$data['version']}\">{$data['version']}</a>";

$url = midcom_connection::get_url('self') . "midcom-permalink-{$page->guid}";
$formatter = $data['l10n']->get_formatter();
?>
<tr>
    <td>
        <a rel="note" class="subject url" href="&(url);">&(page.title);</a>
    </td>
    <td>
        &(version_string:h);
    </td>
    <td class="revisor">
        <?php
        if (   $history['user']
            && $user = midcom::get()->auth->get_user($history['user']))
        {
            $person_label = org_openpsa_widgets_contact::get($user->guid)->show_inline();
            echo "                    {$person_label}\n";
        }
        elseif ($history['ip'])
        {
            echo "                    {$history['ip']}\n";
        }
        ?>
    </td>
    <td>
        <?php
        echo "<abbr class=\"dtposted\" title=\"" . gmdate('Y-m-d\TH:i:s\Z', $history['date']) . "\">" . $formatter->datetime($history['date']) . "</abbr>\n";
        ?>
    </td>
    <td class="message">
        <?php
        if (strlen($history['message']) > 42)
        {
            echo substr($history['message'], 0, 40) . '...';
        }
        else
        {
            echo $history['message'];
        } ?>
    </td>
</tr>