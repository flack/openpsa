<div class="main">
    <h1><?php echo $data['l10n']->get('import'); ?></h1>

    <p>
        <?php
        echo $data['l10n']->get('you can import csv files here');

        // Show instructions
        echo "<ul>\n";
        echo "    <li>" . $data['l10n']->get('one line per product') . "</li>\n";
        echo "    <li>" . $data['l10n']->get('first row is headers') . "</li>\n";
        echo "    <li>" . $data['l10n']->get('fields available for matching are defined in schema') . "</li>\n";
        echo "</ul>\n";
        ?>
    </p>

    <form enctype="multipart/form-data" action="<?php midcom_connection::get_url('uri'); ?>" method="post" class="datamanager2">
    <div class="form">
    <div class="element">
        <label for="org_openpsa_products_import_upload">
            <span class="field_text"><?php echo $data['l10n']->get('file to import'); ?></span>
        </label>
        <div class="input">
            <input type="file" class="fileselector" name="org_openpsa_products_import_upload" id="org_openpsa_products_import_upload" />
        </div>
        </div>
    <div class="element">
        <label for="org_openpsa_products_import_separator">
            <span class="field_text"><?php echo $data['l10n']->get('field separator'); ?></span>
        </label>
        <div class="input">
            <select class="dropdown" name="org_openpsa_products_import_separator" id="org_openpsa_products_import_separator">
                <option value=";">;</option>
                <option value=",">,</option>
            </select>
        </div>
        </div>
    <div class="element">
        <label for="org_openpsa_products_import_schema">
            <span class="field_text"><?php echo $data['l10n']->get('schema'); ?></span>
        </label>
        <div class="input">
            <select class="dropdown" name="org_openpsa_products_import_schema" id="org_openpsa_products_import_schema">
                <?php
                foreach (array_keys($data['schemadb_product']) as $name)
                {
                    echo "                <option value=\"{$name}\">" . $data['l10n']->get($data['schemadb_product'][$name]->description) . "</option>\n";
                }
                ?>
            </select>
        </div>
        </div>
    <div class="element">
        <label for="org_openpsa_products_import_new_products_product_group">
            <span class="field_text"><?php echo $data['l10n']->get('import new products to this product group'); ?></span>
        </label>
        <div class="input">
            <select class="dropdown" name="org_openpsa_products_import_new_products_product_group" id="org_openpsa_products_import_new_products_product_group">
                <?php
                foreach ($data['product_groups'] as $id => $label)
                {
                    echo "                <option value='{$id}'>{$label}</option>\n";
                }
                ?>
            </select>
        </div>
        </div>
        <div class="form_toolbar">
            <input type="submit" class="save" value="<?php echo $data['l10n']->get('import'); ?>" />
        </div>
    </div>
    </form>
</div>
