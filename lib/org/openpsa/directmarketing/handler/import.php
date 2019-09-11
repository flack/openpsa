<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    private function _prepare_handler($guid)
    {
        midcom::get()->auth->require_user_do('midgard:create', null, org_openpsa_contacts_person_dba::class);

        // Try to load the correct campaign
        $this->_request_data['campaign'] = $this->load_campaign($guid);

        $this->_view_toolbar->add_item([
            MIDCOM_TOOLBAR_URL => $this->router->generate('view_campaign', ['guid' => $guid]),
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get("back"),
            MIDCOM_TOOLBAR_GLYPHICON => 'eject',
        ]);

        $this->bind_view_to_object($this->_request_data['campaign']);

        $this->_schemadbs = [
            'person' => schemadb::from_path($this->_config->get('schemadb_person')),
            'campaign_member' => schemadb::from_path($this->_config->get('schemadb_campaign_member')),
            'organization' => schemadb::from_path($this->_config->get('schemadb_organization')),
            'organization_member' => schemadb::from_path($this->_config->get('schemadb_organization_member')),
        ];
        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.datamanager/default.css");

        midcom::get()->disable_limits();
    }

    /**
     * Update the breadcrumb line
     *
     * @param string $handler_id
     * @param string $guid The object's GUID
     */
    private function _update_breadcrumb($handler_id, $guid)
    {
        $this->add_breadcrumb($this->router->generate('import_main', ['guid' => $guid]), $this->_l10n->get('import subscribers'));

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
     * @param string $handler_id Name of the request handler
     * @param string $guid The object's GUID
     */
    public function _handler_index($handler_id, $guid)
    {
        $this->_prepare_handler($guid);

        // Update the breadcrumb line
        $this->_update_breadcrumb($handler_id, $guid);

        return $this->show('show-import-index');
    }

    /**
     * Phase for importing simple email addresses
     *
     * @param Request $request The request object
     * @param string $handler_id Name of the request handler
     * @param string $guid The object's GUID
     */
    public function _handler_simpleemails(Request $request, $handler_id, $guid)
    {
        $this->_prepare_handler($guid);

        // Update the breadcrumb line
        $this->_update_breadcrumb($handler_id, $guid);

        if ($request->request->has('org_openpsa_directmarketing_import_separator')) {
            switch ($request->request->get('org_openpsa_directmarketing_import_separator')) {
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

            $file = $request->files->get('org_openpsa_directmarketing_import_upload');
            if ($file && $file instanceof UploadedFile) {
                $contacts_raw = file_get_contents($file->getPathname());
            }

            $contacts_raw .= $request->request->get('org_openpsa_directmarketing_import_textarea', '');

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
        if (!empty($contacts)) {
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
     * @param array $data          Public request data, passed by reference
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
     * @param Request $request The request object
     * @param string $handler_id Name of the request handler
     * @param string $guid The object's GUID
     */
    public function _handler_vcards(Request $request, $handler_id, $guid)
    {
        $this->_prepare_handler($guid);

        // Update the breadcrumb line
        $this->_update_breadcrumb($handler_id, $guid);

        if (   $request->request->has('org_openpsa_directmarketing_import')
            && is_uploaded_file($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name'])) {
            $importer = new org_openpsa_directmarketing_importer_vcards($this->_schemadbs);
            $this->_run_import($importer, $_FILES['org_openpsa_directmarketing_import_upload']['tmp_name']);
        }
    }

    /**
     * Show the vcard import interface
     *
     * @param String $handler_id    Name of the request handler
     * @param array $data          Public request data, passed by reference
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
     * @param Request $request The request object
     * @param String $handler_id Name of the request handler
     * @param string $guid The object's GUID
     * @param array $data Public request data, passed by reference
     */
    public function _handler_csv_select(Request $request, $handler_id, $guid, array &$data)
    {
        $this->_prepare_handler($guid);

        // Update the breadcrumb
        $this->_update_breadcrumb($handler_id, $guid);

        if ($request->request->has('org_openpsa_directmarketing_import_separator')) {
            $data['time_start'] = time();

            $data['rows'] = [];
            $data['separator'] = $request->request->get('org_openpsa_directmarketing_import_separator');
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
     * @param array $data          Public request data, passed by reference
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
     * @param Request $request The request object
     * @param string $handler_id Name of the request handler
     * @param string $guid The object's GUID
     * @param array $data Public request data, passed by reference
     */
    public function _handler_csv(Request $request, $handler_id, $guid, array &$data)
    {
        if (!$request->request->has('org_openpsa_directmarketing_import_separator')) {
            throw new midcom_error('No CSV separator specified.');
        }

        if (!file_exists($request->request->get('org_openpsa_directmarketing_import_tmp_file'))) {
            throw new midcom_error('No CSV file available.');
        }

        $this->_prepare_handler($guid);

        // Update the breadcrumb
        $this->_update_breadcrumb($handler_id, $guid);

        $data['rows'] = [];
        $config = [
            'fields' => $request->request->get('org_openpsa_directmarketing_import_csv_field'),
            'separator' => $request->request->get('org_openpsa_directmarketing_import_separator'),
        ];
        $importer = new org_openpsa_directmarketing_importer_csv($this->_schemadbs, $config);
        $this->_run_import($importer, $request->request->get('org_openpsa_directmarketing_import_tmp_file'));

        return $this->show('show-import-status');
    }
}
