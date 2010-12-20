<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="main">
    <h1>Import</h1>

    <p>
        <?php echo $data['l10n']->get('match csv columns to database fields'); ?>
    </p>

    <form action="&(prefix);import/&(data['type']);/csv2/" method="post" class="datamanager">
        <input type="hidden" name="org_openpsa_products_import_separator" value="<?php echo $data['separator']; ?>" />
        <input type="hidden" name="org_openpsa_products_import_schema" value="<?php echo $data['schema']; ?>" />
        <input type="hidden" name="org_openpsa_products_import_tmp_file" value="<?php echo $data['tmp_file']; ?>" />
        <input type="hidden" name="org_openpsa_products_import_new_products_product_group" value="<?php echo $data['new_products_product_group']; ?>" />
        <table>
            <thead>
                <tr>
                    <th>
                        <?php
                        echo $data['l10n']->get('csv column');
                        ?>
                    </th>
                    <?php
                    if (isset($data['rows'][1]))
                    {
                    ?>
                    <th>
                        <?php
                        echo $data['l10n']->get('example');
                        ?>
                    </th>
                    <?php
                    }
                    ?>
                    <th>
                        <?php
                        echo $data['l10n']->get('store to field');
                        ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                $fields_to_skip = split(',', $data['config']->get('import_skip_fields'));
                foreach ($data['rows'][0] as $key => $cell)
                {
                    echo "<tr>\n";
                    echo "    <td><label for=\"org_openpsa_products_import_csv_field_{$key}\">{$cell}</label></td>\n";
                    if (isset($data['rows'][1]))
                    {
                        echo "    <td>{$data['rows'][1][$key]}</td>\n";
                    }
                    echo "    <td>\n";
                    echo "        <select name=\"org_openpsa_products_import_csv_field[{$key}]\" id=\"org_openpsa_products_import_csv_field_{$key}\">\n";
                    echo "            <option></option>\n";

                    // Show fields from "default" schemas as selectors
                    $schemadb = $data['schemadb'];
                    foreach ($schemadb[$data['schema']]->fields as $field_id => $field)
                    {
                        $selected = '';
                        if (   array_key_exists('hidden', $field)
                            && $field['hidden'])
                        {
                            // Hidden field, skip
                            // TODO: We may want to use some customdata field for this instead
                            continue;
                        }

                        if (   is_array($fields_to_skip)
                            && in_array($field_id, $fields_to_skip)
                            )
                        {
                            continue;
                        }

                        $field_label = $schemadb[$data['schema']]->translate_schema_string($field['title']);
                        if($cell == $field_label)
                        {
                            $selected = ' selected';
                        }
                        echo "            <option{$selected} value=\"{$field_id}\">{$field_label}</option>\n";
                    }

                    echo "    </select></td>\n";
                    echo "</tr>\n";
                }
                ?>
            </tbody>
        </table>
        <div class="form_toolbar">
            <input type="submit" class="save" value="<?php echo $data['l10n']->get('import'); ?>" />
        </div>
    </form>
</div>