<h1><?php printf($data['l10n']->get('merge %s'), $data['l10n']->get('groups')); ?></h1>
<p><?php printf($data['l10n']->get('choose the %s to keep'), $data['l10n']->get('group')); ?></p>
<form method="post" class="org_openpsa_contacts_duplicates">
    <input type="hidden" name="org_openpsa_contacts_handler_duplicates_object_options[1]" value="<?php echo $data['object1']->guid; ?>" />
    <input type="hidden" name="org_openpsa_contacts_handler_duplicates_object_options[2]" value="<?php echo $data['object2']->guid; ?>" />
    <input type="hidden" name="org_openpsa_contacts_handler_duplicates_object_loop_i" value="<?php echo $data['loop_i']; ?>" />
    <table class="org_openpsa_contacts_duplicates">
        <tr class="contacts">
            <td><?php
            $data['group'] = $data['object1'];
            midcom_show_style('search-groups-item');
            ?></td>
            <td align="center"><?php echo $data['l10n']->get('vs'); ?></td>
            <td><?php
            $data['group'] = $data['object2'];
            midcom_show_style('search-groups-item');
            ?></td>
        </tr>
        <tr align="center" class="choices">
            <td><input type="submit" class="keepone" name="org_openpsa_contacts_handler_duplicates_object_keep[<?php echo $data['object1']->guid; ?>]" value="<?php echo $data['l10n']->get('keep this'); ?>" /></td>
            <td><input type="submit" class="keepboth" name="org_openpsa_contacts_handler_duplicates_object_keep[both]" value="<?php echo $data['l10n']->get('keep both'); ?>" /></td>
            <td><input type="submit" class="keepone" name="org_openpsa_contacts_handler_duplicates_object_keep[<?php echo $data['object2']->guid; ?>]" value="<?php echo $data['l10n']->get('keep this'); ?>" /></td>
        </tr>
        <tr align="center" class="choices">
            <td colspan=3><input type="submit" class="decidelater" name="org_openpsa_contacts_handler_duplicates_object_decide_later" value="<?php echo $data['l10n']->get('decide later'); ?>" /></td>
        </tr>
    </table>
</form>