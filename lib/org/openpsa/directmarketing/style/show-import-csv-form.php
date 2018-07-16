<div class="main">
    <h1><?php printf($data['l10n']->get('import subscribers to %s'), $data['campaign']->title); ?></h1>

    <p>
        <?php
        echo $data['l10n']->get('you can import csv files here');

        // Show instructions
        echo "<ul>\n";
        echo "    <li>" . $data['l10n']->get('one line per subscriber') . "</li>\n";
        echo "    <li>" . $data['l10n']->get('first row is headers') . "</li>\n";
        echo "    <li>" . $data['l10n']->get('iso-latin-1 encoding') . "</li>\n";
        echo "    <li>" . $data['l10n']->get('fields available for matching are defined in schema') . "</li>\n";
        echo "</ul>\n";
        ?>
    </p>

    <form enctype="multipart/form-data" action="<?php echo midcom_connection::get_url('uri'); ?>" method="post" class="datamanager">
        <label for="org_openpsa_directmarketing_import_upload">
            <span class="field_text"><?php echo $data['l10n']->get('file to import'); ?></span>
            <input type="file" class="fileselector" name="org_openpsa_directmarketing_import_upload" id="org_openpsa_directmarketing_import_upload" />
        </label>
        <label for="org_openpsa_directmarketing_import_separator">
            <span class="field_text"><?php echo $data['l10n']->get('field separator'); ?></span>
            <select class="dropdown" name="org_openpsa_directmarketing_import_separator" id="org_openpsa_directmarketing_import_separator">
                <option value=";">;</option>
                <option value=",">,</option>
            </select>
        </label>
        <div class="form_toolbar">
            <input type="submit" class="save" value="<?php echo $data['l10n']->get('import'); ?>" />
        </div>
    </form>
</div>