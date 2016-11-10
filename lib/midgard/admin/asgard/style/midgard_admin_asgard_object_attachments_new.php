<h1><?php echo $data['l10n']->get('attachments'); ?></h1>

<form method="post" class="datamanager2" enctype="multipart/form-data">
    <fieldset class="fieldset midgard_admin_asgard_object_attachments_upload">
        <legend><?php echo $data['l10n']->get('upload file'); ?></legend>

        <input type="file" name="midgard_admin_asgard_file" />
    </fieldset>

    <hr />

    <fieldset class="fieldset midgard_admin_asgard_object_attachments_newfile">
        <legend><?php echo $data['l10n']->get('add text file'); ?></legend>

        <label>
            <span><?php echo $data['l10n']->get('filename'); ?></span>
            <input class="text" type="text" name="midgard_admin_asgard_filename" />
        </label>

        <label>
            <span><?php echo $data['l10n']->get('mimetype'); ?></span>
            <select name="midgard_admin_asgard_mimetype">
                <?php
                foreach ($data['attachment_text_types'] as $type => $label) {
                    $label = $data['l10n']->get($label);
                    echo "                <option value=\"{$type}\">{$label}</option>\n";
                }
                ?>
            </select>
        </label>
    </fieldset>
    <div class="form_toolbar">
        <input type="submit" class="submit save" accesskey="s" name="midgard_admin_asgard_save" value="<?php echo $data['l10n_midcom']->get('save'); ?>" />
    </div>
</form>
