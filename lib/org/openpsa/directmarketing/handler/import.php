<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.directmarketing campaign handler and viewer class.
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_import extends midcom_baseclasses_components_handler
{
    /**
     * The schema databases used for importing to various objects like persons and organizations
     *
     * @var array
     */
    private $_schemadbs = array();

    /**
     * Flag to track whether an import was successfully performed
     *
     * @var boolean
     */
    private $_import_success = false;

    private function _prepare_handler($args)
    {
        midcom::get('auth')->require_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba');

        // Try to load the correct campaign
        $this->_request_data['campaign'] = $this->_master->load_campaign($args[0]);

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "campaign/{$this->_request_data['campaign']->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get("back"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
            )
        );

        $this->bind_view_to_object($this->_request_data['campaign']);

        $this->_schemadbs = $this->_master->load_schemas();

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/midcom.helper.datamanager2/legacy.css");

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
        $this->add_breadcrumb("campaign/{$args[0]}/", $this->_request_data['campaign']->title);
        $this->add_breadcrumb("campaign/import/{$args[0]}/", $this->_l10n->get('import subscribers'));

        switch ($handler_id)
        {
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
    }

    /**
     * Show the selection list for import types
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_index($handler_id, array &$data)
    {
        midcom_show_style('show-import-index');
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

        if (array_key_exists('org_openpsa_directmarketing_import_separator', $_POST))
        {
            $this->_request_data['time_start'] = time();

            $this->_request_data['contacts'] = array();

            switch ($_POST['org_openpsa_directmarketing_import_separator'])
            {
                case 'N':
                    $this->_request_data['separator'] = "\n";
                    break;
                case ';':
                    $this->_request_data['separator'] = ";";
                    break;
                case ',':
                default:
                    $this->_request_data['separator'] = ",";
                    break;
            }

            // Initialize the raw contact data
            $contacts_raw = '';

            if (is_uploaded_file($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name']))
            {
                $contacts_raw = file_get_contents($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name']);
            }

            if (isset($_POST['org_openpsa_directmarketing_import_textarea']))
            {
                $contacts_raw .= $_POST['org_openpsa_directmarketing_import_textarea'];
            }

            if ($contacts_raw)
            {
                // Make sure we only have NL linebreaks
                $contacts_raw = preg_replace("/\n\r|\r\n|\r/", "\n", $contacts_raw);
                $contacts = explode($this->_request_data['separator'], $contacts_raw);
                if (count($contacts) > 0)
                {
                    foreach ($contacts as $contact)
                    {
                        $contact = trim($contact);

                        // Skip the empty lines already now
                        if (!$contact)
                        {
                            continue;
                        }

                        $this->_request_data['contacts'][] = array
                        (
                            'person' => array
                            (
                                'email' => strtolower($contact),
                            )
                        );
                    }
                }
            }

            $this->_run_import();
        }
    }

    private function _run_import()
    {
        if (count($this->_request_data['contacts']) > 0)
        {
            $importer = new org_openpsa_directmarketing_importer($this->_schemadbs);
            $this->_request_data['import_status'] = $importer->import_subscribers($this->_request_data['contacts'], $this->_request_data['campaign']);
            if (   $this->_request_data['import_status']['subscribed_new'] > 0
                || $this->_request_data['import_status']['subscribed_existing'] > 0)
            {
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
        if (!$this->_import_success)
        {
            midcom_show_style('show-import-simpleemails-form');
        }
        else
        {
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

        if (array_key_exists('org_openpsa_directmarketing_import', $_POST))
        {
            $this->_request_data['contacts'] = array();

            $this->_request_data['time_start'] = time();

            if (is_uploaded_file($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name']))
            {
                $parser = new Contact_Vcard_Parse();
                $cards = @$parser->fromFile($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name']);

                if (count($cards) > 0)
                {
                    foreach ($cards as $card)
                    {
                        // Empty the person array before going through vCard data
                        $contact = array
                        (
                            'person'              => array(),
                            'organization'        => array(),
                            'organization_member' => array(),
                        );

                        // Start parsing
                        if (   array_key_exists('N', $card)
                            && array_key_exists('value', $card['N'][0])
                            && is_array($card['N'][0]['value']))
                        {
                            // FIXME: We should do something about character encodings
                            $contact['person']['lastname'] = $card['N'][0]['value'][0][0];
                            $contact['person']['firstname'] = $card['N'][0]['value'][1][0];
                        }

                        if (array_key_exists('TEL', $card))
                        {
                            foreach ($card['TEL'] as $number)
                            {
                                if (array_key_exists('param', $number))
                                {
                                    if (array_key_exists('TYPE', $number['param']))
                                    {
                                        switch ($number['param']['TYPE'][0])
                                        {
                                            case 'CELL':
                                                $contact['person']['handphone'] = $number['value'][0][0];
                                                break;
                                            case 'HOME':
                                                $contact['person']['homephone'] = $number['value'][0][0];
                                                break;
                                            case 'WORK':
                                                $contact['person']['workphone'] = $number['value'][0][0];
                                                break;
                                        }
                                    }
                                }
                            }
                        }

                        if (array_key_exists('ORG', $card))
                        {
                            $contact['organization']['official'] = $card['ORG'][0]['value'][0][0];
                        }

                        if (array_key_exists('TITLE', $card))
                        {
                            $contact['organization_member']['title'] = $card['TITLE'][0]['value'][0][0];
                        }

                        if (array_key_exists('EMAIL', $card))
                        {
                            $contact['person']['email'] = $card['EMAIL'][0]['value'][0][0];
                        }

                        if (array_key_exists('X-SKYPE-USERNAME', $card))
                        {
                            $contact['person']['skype'] = $card['X-SKYPE-USERNAME'][0]['value'][0][0];
                        }

                        if (array_key_exists('UID', $card))
                        {
                            $contact['person']['external-uid'] = $card['UID'][0]['value'][0][0];
                        }
                        elseif (array_key_exists('X-ABUID', $card))
                        {
                            $contact['person']['external-uid'] = $card['X-ABUID'][0]['value'][0][0];
                        }

                        if (count($contact['person']) > 0)
                        {
                            // We have parsed some contact info.

                            // Convert fields from latin-1 to MidCOM charset (usually utf-8)
                            foreach ($contact as $type => $fields)
                            {
                                foreach ($fields as $key => $value)
                                {
                                    $contact[$type][$key] = iconv('ISO-8859-1', midcom::get('i18n')->get_current_charset(), $value);
                                }
                            }

                            // TODO: Make sanity checks before adding

                            $this->_request_data['contacts'][] = $contact;
                        }
                    }
                }
            }

            $this->_run_import();
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
        if (!$this->_import_success)
        {
            midcom_show_style('show-import-vcard-form');
        }
        else
        {
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

        if (array_key_exists('org_openpsa_directmarketing_import_separator', $_POST))
        {
            $data['time_start'] = time();

            $data['rows'] = array();

            switch ($_POST['org_openpsa_directmarketing_import_separator'])
            {
                case ';':
                    $data['separator'] = ';';
                    break;

                case ',':
                default:
                    $data['separator'] = ',';
                    break;
            }


            if (is_uploaded_file($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name']))
            {
                // Copy the file for later processing
                $data['tmp_file'] = tempnam(midcom::get('config')->get('midcom_tempdir'), 'org_openpsa_directmarketing_import_csv');
                $src = fopen($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name'], 'r');
                $dst = fopen($data['tmp_file'], 'w+');
                while (! feof($src))
                {
                    $buffer = fread($src, 131072); /* 128 kB */
                    fwrite($dst, $buffer, 131072);
                }
                fclose($src);
                fclose($dst);

                // Read cell headers from the file
                $read_rows = 0;
                $handle = fopen($_FILES['org_openpsa_directmarketing_import_upload']['tmp_name'], 'r');
                $separator = $data['separator'];
                $total_columns = 0;
                while (   $read_rows < 2
                       && $csv_line = fgetcsv($handle, 1000, $separator))
                {
                    if ($total_columns == 0)
                    {
                        $total_columns = count($csv_line);
                    }
                    $columns_with_content = 0;
                    foreach ($csv_line as $value)
                    {
                        if ($value != '')
                        {
                            $columns_with_content++;
                        }
                    }
                    $percentage = round(100 / $total_columns * $columns_with_content);

                    if ($percentage >= 20)
                    {
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
        if (array_key_exists('rows', $data))
        {
            // Present user with the field matching form
            $data['schemadbs'] = $this->_schemadbs;
            midcom_show_style('show-import-csv-select');
        }
        else
        {
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
        $this->_prepare_handler($args);

        // Update the breadcrumb
        $this->_update_breadcrumb($handler_id, $args);

        $data['contacts'] = array();

        if (!array_key_exists('org_openpsa_directmarketing_import_separator', $_POST))
        {
            throw new midcom_error('No CSV separator specified.');
        }

        if (!file_exists($_POST['org_openpsa_directmarketing_import_tmp_file']))
        {
            throw new midcom_error('No CSV file available.');
        }

        $data['time_start'] = time();

        $data['rows'] = array();
        $data['separator'] = $_POST['org_openpsa_directmarketing_import_separator'];

        // Start processing the file
        $read_rows = 0;
        $total_columns = 0;
        $handle = fopen($_POST['org_openpsa_directmarketing_import_tmp_file'], 'r');
        $separator = $data['separator'];

        while ($csv_line = fgetcsv($handle, 1000, $separator))
        {
            if ($total_columns == 0)
            {
                $total_columns = count($csv_line);
            }
            $columns_with_content = 0;
            foreach ($csv_line as $value)
            {
                if ($value != '')
                {
                    $columns_with_content++;
                }
            }
            $percentage = round(100 / $total_columns * $columns_with_content);

            if ($percentage >= 20)
            {
                $data['rows'][] = $csv_line;
                $read_rows++;
            }
            else
            {
                // This line has no proper content, skip
                continue;
            }

            $contact = array();

            if ($read_rows == 1)
            {
                // First line is headers, skip
                continue;
            }
            foreach ($csv_line as $field => $value)
            {
                // Process the row accordingly
                $field_matching = $_POST['org_openpsa_directmarketing_import_csv_field'][$field];
                if (   $field_matching
                    && strstr($field_matching, ':'))
                {
                    $matching_parts = explode(':', $field_matching);
                    $schemadb = $matching_parts[0];
                    $schema_field = $matching_parts[1];

                    if (   !array_key_exists($schemadb, $this->_schemadbs)
                        || !array_key_exists($schema_field, $this->_schemadbs[$schemadb]['default']->fields))
                    {
                        // Invalid matching, skip
                        continue;
                    }

                    if ($value == '')
                    {
                        // No value, skip
                        continue;
                    }

                    if (!array_key_exists($schemadb, $contact))
                    {
                        $contact[$schemadb] = array();
                    }

                    $contact[$schemadb][$schema_field] = $value;
                }
            }

            if (count($contact) > 0)
            {
                $data['contacts'][] = $contact;
            }
        }

        $this->_run_import();
    }

    /**
     * Show the CSV import phase
     *
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     */
    public function _show_csv($handler_id, array &$data)
    {
        midcom_show_style('show-import-status');
    }
}
?>
