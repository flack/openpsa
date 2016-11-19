<?php
/**
 * @package org.openpsa.directmarketing
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * CSV Importer for subscribers
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_importer_csv extends org_openpsa_directmarketing_importer
{
    public function parse($input)
    {
        $parsed = array();

        // Start processing the file
        $handle = fopen($input, 'r');
        $read_rows = 0;
        $total_columns = 0;
        while ($csv_line = fgetcsv($handle, 1000, $this->_settings['separator'])) {
            if ($total_columns == 0) {
                $total_columns = count($csv_line);
            }
            $columns_with_content = count(array_filter($csv_line));
            $percentage = round(100 / $total_columns * $columns_with_content);

            if ($percentage < 20) {
                // This line has no proper content, skip
                continue;
            }
            $read_rows++;

            if ($read_rows == 1) {
                // First line is headers, skip
                continue;
            }
            $contact = $this->_read_line($csv_line);

            if (!empty($contact)) {
                $parsed[] = $contact;
            }
        }

        return $parsed;
    }

    private function _read_line(array $csv_line)
    {
        $contact = array();

        foreach (array_filter($csv_line) as $field => $value) {
            // Process the row accordingly
            $field_matching = $this->_settings['fields'][$field];
            if (   $field_matching
                && strstr($field_matching, ':')) {
                $matching_parts = explode(':', $field_matching);
                $schemadb = $matching_parts[0];
                $schema_field = $matching_parts[1];

                if (   !array_key_exists($schemadb, $this->_schemadbs)
                    || !array_key_exists($schema_field, $this->_schemadbs[$schemadb]['default']->fields)) {
                    // Invalid matching, skip
                    continue;
                }

                if (!array_key_exists($schemadb, $contact)) {
                    $contact[$schemadb] = array();
                }

                $contact[$schemadb][$schema_field] = $value;
            }
        }
        return $contact;
    }
}
