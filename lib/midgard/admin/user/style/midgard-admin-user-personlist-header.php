<form method="get" class="midgard_admin_user_search">
    <label>
        <span><?php echo $data['l10n']->get('search person'); ?></span>
        <input type="text" id="midgard_admin_user_search" name="midgard_admin_user_search" value="<?php if (isset($_REQUEST['midgard_admin_user_search'])) {
    echo $_REQUEST['midgard_admin_user_search'];
} ?>" />
    </label>
    <script type="text/javascript">
    document.getElementById('midgard_admin_user_search').focus();
    </script>
    <input type="submit" value="<?php echo $data['l10n']->get('go'); ?>" />
    <div class="helptext">
        <?php
        $data['search_fields_l10n'] = array_map([$data['l10n'], 'get'], $data['search_fields']);
        printf($data['l10n']->get('the following fields will be searched: %s'), implode(', ', $data['search_fields_l10n']));
        ?>
    </div>
</form>

<?php
if (count($data['persons']) > 0) {
            $action_uri = midcom_connection::get_url('uri');
            if (isset($_REQUEST['midgard_admin_user_search'])) {
                $action_uri .= "?midgard_admin_user_search=" . $_REQUEST['midgard_admin_user_search'];
            }

            $data['enabled'] = 0; ?>
    <form method="post" id="midgard_admin_user_batch_process" action="&(action_uri);">
    <table class="midgard_admin_user_search_results">
        <thead>
            <tr>
                <th>&nbsp;</th>
                <?php
                foreach ($data['list_fields'] as $field) {
                    echo '<th>' . $data['l10n']->get($field) . "</th>\n";
                } ?>
                <th><?php echo $data['l10n']->get('groups'); ?></th>
            </tr>
        </thead>
        <tbody>
    <?php

        }
?>