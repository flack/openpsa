<form method="get" class="midcom_admin_user_search">
    <label>
        <span><?php echo $_MIDCOM->i18n->get_string('search person', 'midcom.admin.user'); ?></span>
        <input type="text" id="midcom_admin_user_search" name="midcom_admin_user_search" value="<?php if (isset($_REQUEST['midcom_admin_user_search'])) { echo $_REQUEST['midcom_admin_user_search']; } ?>" />
    </label>
    <script type="text/javascript">
    document.getElementById('midcom_admin_user_search').focus();
    </script>
    <input type="submit" value="<?php echo $_MIDCOM->i18n->get_string('go', 'midcom.admin.user'); ?>" />
    <div class="helptext">
        <?php
        $data['search_fields_l10n'] = array();
        foreach ($data['search_fields'] as $field)
        {
            $data['search_fields_l10n'][] = $_MIDCOM->i18n->get_string($field, 'midcom.admin.user');
        }
        echo sprintf($_MIDCOM->i18n->get_string('the following fields will be searched: %s', 'midcom.admin.user'), implode(', ', $data['search_fields_l10n']));
        ?>
    </div>
</form>

<?php
if (count($data['persons']) > 0)
{
    $action_uri = midcom_connection::get_url('uri');
    if (isset($_REQUEST['midcom_admin_user_search']))
    {
        $action_uri .= "?midcom_admin_user_search=" . $_REQUEST['midcom_admin_user_search'];
    }

    $data['enabled'] = 0;
    ?>
    <form method="post" id="midcom_admin_user_batch_process" action="&(action_uri);">
    <table class="midcom_admin_user_search_results">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <?php
                foreach ($data['list_fields'] as $field)
                {
                    echo '<th>' . $_MIDCOM->i18n->get_string($field, 'midcom.admin.user') . "</th>\n";
                }
                ?>
                <th><?php echo $_MIDCOM->i18n->get_string('groups', 'midcom.admin.user'); ?></th>
            </tr>
        </thead>
        <tbody>
    <?php
}
?>