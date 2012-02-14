<h1><?php echo midcom::get('i18n')->get_string('attachments', 'midgard.admin.asgard'); ?></h1>

<form method="post" class="datamanager2" enctype="multipart/form-data">
    <fieldset class="midgard_admin_asgard_object_attachments_upload">
        <legend><?php echo midcom::get('i18n')->get_string('upload file', 'midgard.admin.asgard'); ?></legend>

        <input type="file" name="midgard_admin_asgard_file" />
    </fieldset>

    <hr />

    <fieldset class="midgard_admin_asgard_object_attachments_newfile">
        <legend><?php echo midcom::get('i18n')->get_string('add text file', 'midgard.admin.asgard'); ?></legend>

        <label>
            <span><?php echo midcom::get('i18n')->get_string('filename', 'midgard.admin.asgard'); ?></span>
            <input class="text" type="text" name="midgard_admin_asgard_filename" />
        </label>

        <label>
            <span><?php echo midcom::get('i18n')->get_string('mimetype', 'midgard.admin.asgard'); ?></span>
            <select name="midgard_admin_asgard_mimetype">
                <?php
                foreach ($data['attachment_text_types'] as $type => $label)
                {
                    $label = midcom::get('i18n')->get_string($label, 'midgard.admin.asgard');
                    echo "                <option value=\"{$type}\">{$label}</option>\n";
                }
                ?>
            </select>
        </label>
    </fieldset>
    <div class="form_toolbar">
        <input type="submit" class="save" accesskey="s" name="midgard_admin_asgard_save" value="<?php echo midcom::get('i18n')->get_string('save', 'midcom'); ?>" />
    </div>
</form>
