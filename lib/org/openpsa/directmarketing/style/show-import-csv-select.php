<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="main">
    <h1><?php printf($data['l10n']->get('import subscribers to "%s"'), $data['campaign']->title); ?></h1>

    <p>
        <?php echo $data['l10n']->get('match csv columns to database fields'); ?>
    </p>

    <form action="&(prefix);campaign/import/csv2/<?php echo $data['campaign']->guid; ?>/" method="post" class="datamanager">
        <input type="hidden" name="org_openpsa_directmarketing_import_separator" value="<?php echo $data['separator']; ?>" />
        <input type="hidden" name="org_openpsa_directmarketing_import_tmp_file" value="<?php echo $data['tmp_file']; ?>" />
        <table>
            <thead>
                <tr>
                    <th>
                        <?php
                        echo $data['l10n']->get('csv column');
                        ?>
                    </th>
                    <th>
                        <?php
                        echo $data['l10n']->get('example');
                        ?>
                    </th>
                    <th>
                        <?php
                        echo $data['l10n']->get('store to field');
                        ?>
                    </th>
                </tr>
            </thead>
            <tbody>
                <?php
                foreach ($data['rows'][0] as $key => $cell) {
                    $val = (!empty($data['rows'][1][$key])) ? $data['rows'][1][$key] : '';
                    echo "<tr>\n";
                    echo "    <td><label for=\"org_openpsa_directmarketing_import_csv_field_{$key}\">{$cell}</label></td>\n";
                    echo "    <td>{$val}</td>\n";
                    echo "    <td>\n";
                    echo "        <select name=\"org_openpsa_directmarketing_import_csv_field[{$key}]\" id=\"org_openpsa_directmarketing_import_csv_field_{$key}\">\n";
                    echo "            <option></option>\n";

                    // Show fields from "default" schemas as selectors
                    foreach ($data['schemadbs'] as $schemadb_id => $schemadb) {
                        if (!$schemadb->has('default')) {
                            // No default schema in this schemadb, skip
                            continue;
                        }
                        $schema = $schemadb->get('default');
                        $l10n = $schema->get_l10n();
                        foreach ($schema->get('fields') as $field_id => $field) {
                            if (!empty($field['hidden'])) {
                                // Hidden field, skip
                                // TODO: We may want to use some customdata field for this instead
                                continue;
                            }

                            $field_label = $l10n->get($schema->get('description')) . ': ' . $l10n->get($field['title']);
                            echo "            <option value=\"{$schemadb_id}:{$field_id}\">{$field_label}</option>\n";
                        }
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