<?php
/**
 * @package midcom.baseclasses
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

/**
 * Generic CSV export handler baseclass
 *
 * @package midcom.baseclasses
 */
abstract class midcom_baseclasses_components_handler_dataexport extends midcom_baseclasses_components_handler
{
    /**
     * @var datamanager[]
     */
    private array $_datamanagers = [];

    /**
     * Flag indicating whether or not the GUID of the first type should be included in exports.
     */
    public bool $include_guid = true;

    /**
     * Flag indicating whether or not totals for number fields should be generated
     */
    public bool $include_totals = false;

    public array $csv = [];

    protected string $_schema = '';

    private array $_rows = [];

    private array $_totals = [];

    private array $schemas = [];

    /**
     * @return midcom\datamanager\schemadb[]
     */
    abstract public function _load_schemadbs(string $handler_id, array &$args, array &$data) : array;

    abstract public function _load_data(string $handler_id, array &$args, array &$data) : array;

    public function _handler_csv(string $handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        $this->_load_datamanagers($this->_load_schemadbs($handler_id, $args, $data));

        if (empty($args[0])) {
            //We do not have filename in URL, generate one and redirect
            if (empty($data['filename'])) {
                $data['filename'] = preg_replace('/[^a-z0-9-]/i', '_', strtolower($this->_topic->extra)) . '_' . date('Y-m-d') . '.csv';
            }
            if (!str_ends_with(midcom_connection::get_url('uri'), '/')) {
                $data['filename'] = '/' . $data['filename'];
            }
            return new midcom_response_relocate(midcom_connection::get_url('uri') . $data['filename']);
        }

        midcom::get()->disable_limits();

        $rows = $this->_load_data($handler_id, $args, $data);
        if (count($this->_datamanagers) == 1) {
            foreach ($rows as $row) {
                $this->_rows[] = [$row];
            }
        } else {
            $this->_rows = $rows;
        }

        if (empty($data['filename'])) {
            $data['filename'] = str_replace('.csv', '', $args[0]);
        }

        $this->_init_csv_variables();

        $response = new StreamedResponse([$this, 'render_csv']);
        $response->headers->set('Content-Disposition', $response->headers->makeDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $data['filename'])
        );
        $response->headers->set('Content-Type', $this->csv['mimetype']);
        return $response;
    }

    public function render_csv()
    {
        // Make real sure we're dumping data live
        midcom::get()->cache->content->enable_live_mode();

        // Dump headers
        $first_type = array_key_first($this->_datamanagers);
        $multiple_types = count($this->_datamanagers) > 1;
        $row = [];
        if ($this->include_guid) {
            $row[] = $first_type . ' GUID';
        }

        foreach ($this->_datamanagers as $type => $datamanager) {
            foreach ($datamanager->get_schema($this->schemas[$type])->get('fields') as $name => $config) {
                $title = $config['title'];
                if (   $this->include_totals
                    && $config['type'] == 'number') {
                    $this->_totals[$type . '-' . $name] = 0;
                }
                $title = $this->_l10n->get($title);
                if ($multiple_types) {
                    $title = $this->_l10n->get($type) . ': ' . $title;
                }
                $row[] = $title;
            }
        }
        $output = fopen("php://output", 'w');
        $this->_print_row($row, $output);

        $this->_dump_rows($output);

        if ($this->include_totals) {
            $row = [];
            foreach ($this->_datamanagers as $type => $datamanager) {
                foreach ($datamanager->get_schema()->get('fields') as $name => $config) {
                    $value = "";
                    if ($config['type'] == 'number') {
                        $value = $this->_totals[$type . '-' . $name];
                    }
                    $row[] = $value;
                }
            }
            $this->_print_row($row, $output);
        }
        fclose($output);
    }

    /**
     * Internal helper, loads the datamanagers for the given types. Any error triggers a 500.
     */
    private function _load_datamanagers(array $schemadbs)
    {
        if (empty($this->_schema)) {
            throw new midcom_error('Export schema ($this->_schema) must be defined');
        }
        foreach ($schemadbs as $type => $schemadb) {
            $this->_datamanagers[$type] = new datamanager($schemadb);

            if ($schemadb->has($this->_schema)) {
                $this->schemas[$type] = $this->_schema;
            } else {
                $this->schemas[$type] = $schemadb->get_first()->get_name();
            }
        }
    }

    private function _dump_rows($output)
    {
        $first_type = array_key_first($this->_datamanagers);
        // Output each row
        foreach ($this->_rows as $num => $row) {
            $data = [];
            foreach ($this->_datamanagers as $type => $datamanager) {
                if (!array_key_exists($type, $row)) {
                    debug_add("row #{$num} does not have {$type} set", MIDCOM_LOG_INFO);
                    $target_size = count($datamanager->get_schema($this->schemas[$type])->get('fields')) + count($data);
                    $data = array_pad($data, $target_size, '');
                    continue;
                }
                $object =& $row[$type];

                $datamanager->set_storage($object, $this->schemas[$type]);

                if (   $this->include_guid
                    && $type == $first_type) {
                    $data[] = $object->guid;
                }

                $csvdata = $datamanager->get_content_csv();
                foreach ($datamanager->get_schema()->get('fields') as $fieldname => $config) {
                    if (   $this->include_totals
                        && $config['type'] == 'number') {
                        $this->_totals[$type . '-' . $fieldname] += $csvdata[$fieldname];
                    }
                    $data[] = $csvdata[$fieldname];
                }
            }
            $this->_print_row($data, $output);
        }
    }

    private function _print_row(array $row, $output)
    {
        $row = array_map([$this, 'encode_csv'], $row);
        fputcsv($output, $row, $this->csv['s'], $this->csv['q']);
    }

    private function _init_csv_variables()
    {
        // FIXME: Use global configuration
        $this->csv['s'] = $this->_config->get('csv_export_separator') ?: ';';
        $this->csv['q'] = $this->_config->get('csv_export_quote') ?: '"';
        $this->csv['mimetype'] = $this->_config->get('csv_export_content_type') ?: 'application/csv';
        $this->csv['charset'] = $this->_config->get('csv_export_charset');

        if (empty($this->csv['charset'])) {
            // Default to ISO-LATIN-15 (Latin-1 with EURO sign etc)
            $this->csv['charset'] = 'ISO-8859-15';
            if (   isset($_SERVER['HTTP_USER_AGENT'])
                && !preg_match('/Windows/i', $_SERVER['HTTP_USER_AGENT'])) {
                // Except when not on windows, then default to UTF-8
                $this->csv['charset'] = 'UTF-8';
            }
        }
    }

    private function encode_csv(string $data)
    {
        /* START: Quick'n'Dirty on-the-fly charset conversion */
        if ($this->csv['charset'] !== 'UTF-8') {
            $to_charset = "{$this->csv['charset']}//TRANSLIT";
            $stat = @iconv('UTF-8', $to_charset, $data);
            if (!empty($stat)) {
                $data = $stat;
            }
        }
        /* END: Quick'n'Dirty on-the-fly charset conversion */

        return $data;
    }
}
