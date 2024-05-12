<?php
/**
 * @package midgard.admin.asgard
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\validation\phpValidator;

/**
 * Helper class to create a DM schema from component's config
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_schemadb_config
{
    private string $component;

    private midcom_helper_configuration $config;

    private bool $is_folder;

    public function __construct(string $component, midcom_helper_configuration $config, bool $is_folder)
    {
        $this->component = $component;
        $this->config = $config;
        $this->is_folder = $is_folder;
    }

    public function create() : schemadb
    {
        // Load SchemaDb
        $schemadb_config_path = midcom::get()->componentloader->path_to_snippetpath($this->component) . '/config/config_schemadb.inc';
        $schemaname = 'default';

        if (file_exists($schemadb_config_path)) {
            $schemadb = schemadb::from_path('file:/' . str_replace('.', '/', $this->component) . '/config/config_schemadb.inc');
            if ($schemadb->has('config')) {
                $schemaname = 'config';
            }
            // TODO: Log error on deprecated config schema?
        } else {
            // Create dummy schema. Naughty component would not provide config schema.
            $schemadb = new schemadb(['default' => [
                'description' => 'configuration for ' . $this->component,
                'fields'      => []
            ]]);
        }
        $schema = $schemadb->get($schemaname);
        $schema->set('l10n_db', $this->component);
        $fields = $schema->get('fields');

        foreach ($this->config->_global as $key => $value) {
            // try to sniff what fields are missing in schema
            if (!array_key_exists($key, $fields)) {
                $fields[$key] = $this->detect_schema($key, $value);
            }

            if (   !isset($this->config->_local[$key])
                || $this->config->_local[$key] == $this->config->_global[$key]) {
                // No local configuration setting, note to user that this is the global value
                $fields[$key]['title'] = $schema->get_l10n()->get($fields[$key]['title']);
                $fields[$key]['title'] .= " <span class=\"global\">(" . midcom::get()->i18n->get_string('global value', 'midgard.admin.asgard') .")</span>";
            }
        }

        // Prepare defaults
        $config = array_intersect_key($this->config->get_all(), $fields);
        foreach ($config as $key => $value) {
            if (is_array($value)) {
                $fields[$key]['default'] = var_export($value, true);
            } else {
                if ($fields[$key]['widget'] == 'checkbox') {
                    $value = (boolean) $value;
                }
                $fields[$key]['default'] = $value;
            }
        }
        $schema->set('fields', $fields);
        $validation = $schema->get('validation') ?: [];
        $validation[] = [
            'callback' => $this->check_config(...),
        ];
        $schema->set('validation', $validation);
        return $schemadb;
    }

    private function detect_schema(string $key, $value) : array
    {
        $result = [
            'title'  => $key,
            'type'   => 'text',
            'widget' => 'text',
        ];

        $type = gettype($value);
        switch ($type) {
            case "boolean":
                $result['type'] = 'boolean';
                $result['widget'] = 'checkbox';
                break;
            case "array":
                $result['widget'] = 'textarea';
                // Complex Array fields should be readonly for topics as we cannot store and read them properly with parameters
                $result['readonly'] = $this->is_folder;
                break;
            case 'string':
                if (str_contains($value, "\n")) {
                    $result['widget'] = 'textarea';
                }
        }

        return $result;
    }

    /**
     * Ensure the configuration is valid (form validation callback)
     */
    public function check_config(array $values)
    {
        $current = $this->config->get_all();
        $result = [];
        foreach ($values as $key => $newval) {
            if ($newval === '' || !isset($current[$key])) {
                continue;
            }
            $val = $current[$key];

            if (is_array($val)) {
                $code = "<?php\n\$data = array({$newval}\n);\n?>";
                if ($error = phpValidator::lint($code)) {
                    $result[$key] = $error;
                }
            }
        }
        return $result ?: true;
    }
}