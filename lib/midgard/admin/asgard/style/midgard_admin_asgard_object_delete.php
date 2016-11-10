<h1>&(data['view_title']:h);</h1>
<form action="<?php echo midcom_connection::get_url('uri'); ?>" method="post">
    <p>
        <input type="submit" name="midgard_admin_asgard_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
        <input type="submit" name="midgard_admin_asgard_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
        <?php
        if (midcom::get()->config->get('midcom_services_rcs_enable')) {
            ?>
        <label for="midgard_admin_asgard_disablercs">
          <input type="checkbox" id="midgard_admin_asgard_disablercs" name="midgard_admin_asgard_disablercs" />
          <?php echo $data['l10n']->get('disable rcs'); ?>
        </label>
            <?php

        }
        ?>
    </p>
</form>
<div class="object_view">
   <?php $data['datamanager']->display_view(); ?>
</div>
<form action="<?php echo midcom_connection::get_url('uri'); ?>" method="post">
    <p>
        <input type="submit" name="midgard_admin_asgard_deleteok" value="<?php echo $data['l10n_midcom']->get('delete'); ?> " />
        <input type="submit" name="midgard_admin_asgard_deletecancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" />
        <?php
        if (midcom::get()->config->get('midcom_services_rcs_enable')) {
            ?>
        <label>
            <input type="checkbox" name="midgard_admin_asgard_disablercs" />
            <?php echo $data['l10n']->get('disable rcs'); ?>
        </label>
            <?php

        }
        ?>
    </p>
</form>
<h2><?php echo $data['l10n']->get('all of the following items will be deleted'); ?></h2>
<div id="midgard_admin_asgard_deletetree" class="midgard_admin_asgard_tree">
<?php
// Show a list of all of the items that will be deleted
$data['tree']->view_link = true;
$data['tree']->edit_link = true;
$data['tree']->draw();
?>
</div>
