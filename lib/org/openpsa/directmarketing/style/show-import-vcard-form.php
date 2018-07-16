<div class="main">
    <h1><?php printf($data['l10n']->get('import subscribers to "%s"'), $data['campaign']->title); ?></h1>

    <p>
        <?php echo $data['l10n']->get('you can import vcards here'); ?>
    </p>

    <form enctype="multipart/form-data" action="<?php echo midcom_connection::get_url('uri'); ?>" method="post" class="datamanager">
        <label for="org_openpsa_directmarketing_import_upload">
            <span class="field_text"><?php echo $data['l10n']->get('file to import'); ?></span>
            <input type="file" class="fileselector" name="org_openpsa_directmarketing_import_upload" id="org_openpsa_directmarketing_import_upload" />
        </label>
        <div class="form_toolbar">
            <input type="submit" name="org_openpsa_directmarketing_import" class="save" value="<?php echo $data['l10n']->get('import'); ?>" />
        </div>
    </form>
</div>