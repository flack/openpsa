<form method="post" action="<?php echo midcom_connection::get_url('uri'); ?>" id="midgard_admin_asgard_navigation_form">
    <p>
    <select name="midgard_type" id="midgard_admin_asgard_navigation_chooser">
        <option value=""><?php echo $data['l10n']->get('midgard.admin.asgard'); ?></option>
<?php
foreach ($data['label_mapping'] as $type => $label) {
    if ($type === $data['navigation_type']) {
        $selected = ' selected="selected"';
    } else {
        $selected = '';
    }

    echo "        <option value=\"{$type}\"{$selected}>{$label}</option>\n";
}
$home_link = $data['router']->generate('welcome');
?>
    </select>
    <input type="submit" name="midgard_type_change" class="submit" value="<?php echo $data['l10n']->get('go'); ?>" />
    </p>
</form>
<script type="text/javascript">
    $('#midgard_admin_asgard_navigation_form input[type="submit"]').css({display:'none'});

    $('#midgard_admin_asgard_navigation_chooser').change(function() {
        if (!this.value) {
            window.location = '&(home_link);';
        } else {
            window.location = '&(home_link);' + $(this).val() + '/';
        }
    });
</script>
