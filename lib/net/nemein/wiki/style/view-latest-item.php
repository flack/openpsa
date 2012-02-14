<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
$page =& $data['wikipage'];
$history =& $data['history'];

$version_string = "<a href=\"{$prefix}__ais/rcs/preview/{$page->guid}/{$data['version']}\">{$data['version']}</a>";

$url = midcom_connection::get_url('self') . "midcom-permalink-{$page->guid}";
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
        if ($history['user'])
        {
            $user = midcom::get('auth')->get_user($history['user']);

            if(is_object($user))
            {
                if (class_exists('org_openpsa_widgets_contact'))
                {
                    $user_card = org_openpsa_widgets_contact::get($user->guid);
                    $person_label = $user_card->show_inline();
                }
                else
                {
                    $person = $user->get_storage();
                    $person_label = $person->name;
                }
            }
            echo "                    {$person_label}\n";
        }
        else if ($history['ip'])
        {
            echo "                    {$history['ip']}\n";
        }
        ?>
    </td>
    <td>
        <?php
        echo "<abbr class=\"dtposted\" title=\"" . gmdate('Y-m-d\TH:i:s\Z', $history['date']) . "\">" . strftime('%x %X', $history['date']) . "</abbr>\n";
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