<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;

/**
 * org.openpsa.directmarketing campaign handler and viewer class.
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_import extends midcom_baseclasses_components_handler
{
    use org_openpsa_directmarketing_handler;

    /**
     * The schema databases used for importing to various objects like persons and organizations
     *
     * @var schemadb[]
     */
    private $_schemadbs = [];

    /**
     * Flag to track whether an import was successfully performed
     *
     * @var boolean
     */
    private $_import_success = false;

    private function _prepare_handler(array $args)
    {
        midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_contacts_person_dba::class);

        // Try to load the correct campaign
        $this->_request_data['campaign'] = $this->load_campaign($args[0]);

        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('view_campaign', ['guid' => $this->_request_data['campaign']->guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get("back"),
            MIDCOM_TOOLBAR_GLYPHICON => 'eject',
        ]);

        $this->bind_view_to_object($this->_request_data['campaign']);

        $this->_schemadbs = [
            'person' => schemadb::from_path($this->_config->get('schemadb_person')),
            'campaign_member' => schemadb::from_path($this->_config->get('schemadb_campaign_member')),
            'organization' => schemadb::from_path($this->_config->get('schemadb_organization')),
            'organization_member' => schemadb::from_path($this->_config->get('schemadb_organization_member')),
        ];;
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.datamanager/default.css");

        midcom::get()->disable_limits();
    }

    /**
     * Update the breadcrumb line
     *
     * @param String $handler_id
     * @param array $args
     */
    private function _update_breadcrumb($handler_id, $args)
    {
        $this->add_breadcrumb($this->router->generate('import_main', ['guid' => $args[0]]), $this->_l10n->get('import subscribers'));

        switch ($handler_id) {
            case 'import_simpleemails':
                $this->add_breadcrumb("", $this->_l10n->get('email addresses'));
                break;
            case 'import_vcards':
                $this->add_breadcrumb("", $this->_l10n->get('vcards'));
                break;
            case 'import_csv_file_select':
            case 'import_csv_field_select':
                $this->add_breadcrumb("", $this->_l10n->get('csv'));
                break;
        }
    }

    /**
     * Phase for selecting the import type
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_index($handler_id, array $args, array &$data)
    {
        $this->_prepare_handler($args);

        // Update the breadcrumb line
        $this->_update_breadcrumb($handler_id, $args);

        return $this->show('show-import-index');
    }

    /**
     * Phase for importing simple email addresses
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_simpleemails($handler_id, array $args, array &$data)
    {
        $this->_prepare_handler($args);

        // Update the breadcrumb line
        $this->_update_breadcrumb($handler_id, $args);

        if (array_key_exists('org_openpsa_directmarketing_import_separator', $_POST)) {
            switch ($_POST['org_openpsa_directmarketing_import_separator']) {
                case 'N':
                    $separator = "\n";
                    break;
                case ';':
                    $separator = ";";
                    break;
                case ',':
                default:
                    $separator = ",";
                    break;
            }

            // Initialize the raw contact data
            $contacts_raw = '';

            if (is_uploaded_file($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name'])) {
                $contacts_raw = file_get_contents($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name']);
            }

            if (isset($_POST['org_openpsa_directmarketing_import_textarea'])) {
                $contacts_raw .= $_POST['org_openpsa_directmarketing_import_textarea'];
            }

            if ($contacts_raw) {
                $importer = new org_openpsa_directmarketing_importer_simpleemails($this->_schemadbs, ['separator' => $separator]);
                $this->_run_import($importer, $contacts_raw);
            }
        }
    }

    private function _run_import(org_openpsa_directmarketing_importer $importer, $input)
    {
        $this->_request_data['time_start'] = time();

        $contacts = $importer->parse($input);
        if (count($contacts) > 0) {
            $this->_request_data['import_status'] = $importer->import_subscribers($contacts, $this->_request_data['campaign']);
            if (   $this->_request_data['import_status']['subscribed_new'] > 0
                || $this->_request_data['import_status']['already_subscribed'] > 0) {
                $this->_import_success = true;
            }
        }

        $this->_request_data['time_end'] = time();
    }

    /**
     * Show the import phase of email addresses
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_simpleemails($handler_id, array &$data)
    {
        if (!$this->_import_success) {
            midcom_show_style('show-import-simpleemails-form');
        } else {
            midcom_show_style('show-import-status');
        }
    }

    /**
     * Phase for importing vcards
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_vcards($handler_id, array $args, array &$data)
    {
        $this->_prepare_handler($args);

        // Update the breadcrumb line
        $this->_update_breadcrumb($handler_id, $args);

        if (   array_key_exists('org_openpsa_directmarketing_import', $_POST)
            && is_uploaded_file($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name'])) {
            $importer = new org_openpsa_directmarketing_importer_vcards($this->_schemadbs);
            $this->_run_import($importer, $_FILES['org_openpsa_directmarketing_import_upload']['tmp_name']);
        }
    }

    /**
     * Show the vcard import interface
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_vcards($handler_id, array &$data)
    {
        if (!$this->_import_success) {
            midcom_show_style('show-import-vcard-form');
        } else {
            midcom_show_style('show-import-status');
        }
    }

    /**
     * Phase for importing CSV. This interface lets user to define what the fields of the CSV represent
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_csv_select($handler_id, array $args, array &$data)
    {
        $this->_prepare_handler($args);

        // Update the breadcrumb
        $this->_update_breadcrumb($handler_id, $args);

        if (array_key_exists('org_openpsa_directmarketing_import_separator', $_POST)) {
            $data['time_start'] = time();

            $data['rows'] = [];
            $data['separator'] = $_POST['org_openpsa_directmarketing_import_separator'];
            if ($data['separator'] != ';') {
                $data['separator'] = ',';
            }

            if (is_uploaded_file($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name'])) {
                // Copy the file for later processing
                $data['tmp_file'] = tempnam(midcom::get()->config->get('midcom_tempdir'), 'org_openpsa_directmarketing_import_csv');
                move_uploaded_file($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name'], $data['tmp_file']);

                // Read cell headers from the file
                $read_rows = 0;
                $handle = fopen($data['tmp_file'], 'r');
                $total_columns = 0;
                while (   $read_rows < 2
                       && $csv_line = fgetcsv($handle, 1000, $data['separator'])) {
                    if ($total_columns == 0) {
                        $total_columns = count($csv_line);
                    }
                    $columns_with_content = count(array_filter($csv_line));
                    $percentage = round(100 / $total_columns * $columns_with_content);

                    if ($percentage >= 20) {
                        $data['rows'][] = $csv_line;
                        $read_rows++;
                    }
                }
            }

            $data['time_end'] = time();
        }
    }

    /**
     * Show the CSV selection phase where user defines which field in CSV corresponds to which schema fields
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_csv_select($handler_id, array &$data)
    {
        if (array_key_exists('rows', $data)) {
            // Present user with the field matching form
            $data['schemadbs'] = $this->_schemadbs;
            midcom_show_style('show-import-csv-select');
        } else {
            // Present user with upload form
            midcom_show_style('show-import-csv-form');
        }
    }

    /**
     * Handle the CSV import phase
     *
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     */
    public function _handler_csv($handler_id, array $args, array &$data)
    {
        if (!array_key_exists('org_openpsa_directmarketing_import_separator', $_POST)) {
            throw new midcom_error('No CSV separator specified.');
        }

        if (!file_exists($_POST['org_openpsa_directmarketing_import_tmp_file'])) {
            throw new midcom_error('No CSV file available.');
        }

        $this->_prepare_handler($args);

        // Update the breadcrumb
        $this->_update_breadcrumb($handler_id, $args);

        $data['rows'] = [];
        $config = [
            'fields' => $_POST['org_openpsa_directmarketing_import_csv_field'],
            'separator' => $_POST['org_openpsa_directmarketing_import_separator'],
        ];
        $importer = new org_openpsa_directmarketing_importer_csv($this->_schemadbs, $config);
        $this->_run_import($importer, $_POST['org_openpsa_directmarketing_import_tmp_file']);

        return $this->show('show-import-status');
    }
}
