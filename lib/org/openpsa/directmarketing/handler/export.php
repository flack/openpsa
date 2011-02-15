<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.directmarketing campaign handler and viewer class.
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_export extends midcom_baseclasses_components_handler
{
    /**
     * The schema databases used for importing to various objects like persons and organizations
     *
     * @var Array
     */
    private $_schemadbs = array();

    /**
     * Datamanagers used for saving various objects like persons and organizations
     *
     * @var Array
     */
    private $_datamanagers = array();

    /**
     * Holds our configured CSV related variables (separators etc)
     * @var array
     */
    var $csv = array();

    /**
     * config key csv_export_memberships cached
     * @var string
     */
    var $membership_mode = false;

    private function _prepare_handler($args)
    {
        // TODO: Add smarter per-type ACL checks
        $_MIDCOM->auth->require_valid_user();

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

        $_MIDCOM->bind_view_to_object($this->_request_data['campaign']);

        $this->_load_schemas();
    }

    /**
     * This function prepares the schemadb
     */
    private function _load_schemas()
    {
        // Load contacts explicitly to get constants for schema
        $_MIDCOM->componentloader->load('org.openpsa.contacts');

        // We try to combine these schemas to provide a single centralized controller
        $this->_schemadbs['campaign_member'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign_member'));
        if (!$this->_schemadbs['campaign_member'])
        {
            throw new midcom_error('Could not load campaign member schema database.');
        }
        $this->_schemadbs['person'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_person'));
        if (!$this->_schemadbs['person'])
        {
            throw new midcom_error('Could not load person schema database.');
        }
        $this->_schemadbs['organization_member'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_organization_member'));
        if (!$this->_schemadbs['organization_member'])
        {
            throw new midcom_error('Could not load organization member schema database.');
        }
        $this->_schemadbs['organization'] = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_organization'));
        if (!$this->_schemadbs['organization'])
        {
            throw new midcom_error('Could not load organization schema database.');
        }
    }

    private function _load_datamanagers()
    {
        foreach ($this->_schemadbs as $identifier => $schemadb)
        {
        	$this->_datamanagers[$identifier] = new midcom_helper_datamanager2_datamanager($schemadb);
            if (array_key_exists('export', $schemadb))
            {
                $this->_datamanagers[$identifier]->set_schema('export');
            }
            else
            {
                $this->_datamanagers[$identifier]->set_schema('default');
            }

        }
    }

    private function _disable_limits()
    {
        //Disable limits
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_csv($handler_id, $args, &$data)
    {
        $this->_prepare_handler($args);

        if (   !isset($args[1])
            || empty($args[1]))
        {
            debug_add('Filename part not specified in URL, generating');
            //We do not have filename in URL, generate one and redirect
            $fname = preg_replace('/[^a-z0-9-]/i', '_', strtolower($this->_request_data['campaign']->title)) . '_' . date('Y-m-d') . '.csv';
            $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
            $_MIDCOM->relocate("{$prefix}campaign/export/csv/{$this->_request_data['campaign']->guid}/{$fname}");
            // This will exit
        }
        $this->_disable_limits();

        $this->_request_data['export_rows'] = array();
        $qb_members = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb_members->add_constraint('campaign', '=', $this->_request_data['campaign']->id);
        $qb_members->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
        // PONDER: Filter by status (other than tester) ??
        $qb_members->add_order('person.lastname', 'ASC');
        $qb_members->add_order('person.firstname', 'ASC');
        $members = $qb_members->execute_unchecked();
        if (!is_array($members))
        {
            throw new midcom_error('Fatal QB error');
        }

        $this->membership_mode = $this->_config->get('csv_export_memberships');

        foreach ($members as $k => $member)
        {
            if ($row = $this->_process_member($member))
            {
                $this->_request_data['export_rows'][] = $row;
            }
        }

        $this->_load_datamanagers();
        $this->_init_csv_variables();
        $_MIDCOM->skip_page_style = true;
        $_MIDCOM->cache->content->content_type($this->_config->get('csv_export_content_type'));
    }

    private function _process_member($member)
    {
        $adder = array();
        $adder['campaign_member'] = $member;
        try
        {
            $adder['person'] = org_openpsa_contacts_person_dba::get_cached($member->person);
        }
        catch (midcom_error $e)
        {
            $e->log();
            return false;
        }
        $qb_memberships = midcom_db_member::new_query_builder();
        $qb_memberships->add_constraint('uid', '=', $member->person);
        $memberships = $qb_memberships->execute_unchecked();
        if (   !is_array($memberships)
            || count($memberships) == 0)
        {
            return $adder;
        }
        switch ($this->membership_mode)
        {
            case 'all':
                foreach ($memberships as $k2 => $membership)
                {
                    $adder['organization_member'] = $membership;
                    try
                    {
                        $adder['organization'] = org_openpsa_contacts_group_dba::get_cached($membership->gid);
                    }
                    catch (midcom_error $e)
                    {
                        debug_add("Error fetching org_openpsa_contacts_group_dba #{$membership->gid}, skipping", MIDCOM_LOG_WARN);
                        return false;
                    }
                    return $adder;
                }
                break;
            default:
                // Fall-trough intentional
            case 'first':
                // Fall-trough intentional
            case 'last':
                foreach ($memberships as $k2 => $membership)
                {
                    $adder['organization_member'] = $membership;
                    try
                    {
                        $adder['organization'] = org_openpsa_contacts_group_dba::get_cached($membership->gid);
                    }
                    catch (midcom_error $e)
                    {
                        debug_add("Error fetching org_openpsa_contacts_group_dba #{$membership->gid}, skipping", MIDCOM_LOG_WARN);
                        return false;
                    }
                    // Get only first or last membership
                    if ($this->membership_mode != 'last')
                    {
                        break;
                    }
                }
                return $adder;
                break;
        }
    }

    private function _init_csv_variables()
    {
        if (   !isset($this->csv['s'])
            || empty($this->csv['s']))
        {
            $this->csv['s'] = $this->_config->get('csv_export_separator');
        }
        if (   !isset($this->csv['q'])
            || empty($this->csv['q']))
        {
            $this->csv['q'] = $this->_config->get('csv_export_quote');
        }
        if (   !isset($this->csv['d'])
            || empty($this->csv['d']))
        {
            $this->csv['d'] = $this->_config->get('csv_export_decimal');
        }
        if (   !isset($this->csv['nl'])
            || empty($this->csv['nl']))
        {
            $this->csv['nl'] = $this->_config->get('csv_export_newline');
        }
        if ($this->csv['s'] == $this->csv['d'])
        {
            throw new midcom_error( "CSV decimal separator (configured as '{$this->csv['d']}') may not be the same as field separator (configured as '{$this->csv['s']}')");
        }
    }

    private function _encode_csv($data, $add_separator = true, $add_newline = false)
    {
        // Strings and numbers beginning with zero are quoted
        if (   (   !is_numeric($data)
                || preg_match('/^[0+]/', $data))
            && !empty($data))
        {
            // Make sure we have only newlines in data
            $data = preg_replace("/\n\r|\r\n|\r/", "\n", $data);
            // Escape quotes (PONDER: make configurable between doubling the character and escaping)
            $data = str_replace($this->csv['q'], '\\' . $this->csv['q'], $data);
            // Escape newlines
            $data = str_replace("\n", '\\n', $data);
            // Quote
            $data = "{$this->csv['q']}{$data}{$this->csv['q']}";
        }
        else
        {
            // Decimal point format
            $data = str_replace('.', $this->csv['s'], $data);
        }
        if ($add_separator)
        {
            $data .= $this->csv['s'];
        }
        if ($add_newline)
        {
            $data .= $this->csv['nl'];
        }
        return $data;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_csv($handler_id, &$data)
    {
        // Make absolutely sure we're in live output
        $_MIDCOM->cache->content->enable_live_mode();
        while(@ob_end_flush());

        $object_types = array('person', 'campaign_member', 'organization', 'organization_member');
        $type_headers = array();
        $type_headers_count = 0;
        foreach ($object_types as $type)
        {
            $type_headers[$type] = array();
            $datamanager =& $this->_datamanagers[$type];
            foreach ($datamanager->schema->field_order as $name)
            {
                $type_headers[$type][$name] = $datamanager->schema->fields[$name]['title'];
                $type_headers_count++;
            }
        }

        // Output header
        $i = 0;
        if ($this->membership_mode == 'all')
        {
            // If membership mode is 'all' we add person guid as a way to reliably recognize individuals from among the memberships
            echo $this->_encode_csv('person: GUID', true, false);
        }
        foreach ($object_types as $type)
        {
            foreach ($type_headers[$type] as $header)
            {
                $i++;
                if ($i < $type_headers_count)
                {
                    echo $this->_encode_csv("{$type}: {$header}", true, false);
                }
                else
                {
                    echo $this->_encode_csv("{$type}: {$header}", false, true);
                }
            }
        }

        // Output each row
        foreach($this->_request_data['export_rows'] as $num => $row)
        {
            if ($this->membership_mode == 'all')
            {
                echo $this->_encode_csv($row['person']->guid, true, false);
            }
            $i = 0;
            foreach ($object_types as $type)
            {
                if (!array_key_exists($type, $row))
                {
                    debug_add("row #{$num} does not have {$type} set", MIDCOM_LOG_INFO);
                    $i2_tgt = count($type_headers[$type]);
                    for ($i2 = 0; $i2 < $i2_tgt; $i2++)
                    {
                        $i++;
                        if ($i < $type_headers_count)
                        {
                            echo $this->csv['s'];
                        }
                    }
                    continue;
                }
                $object =& $row[$type];
                $datamanager =& $this->_datamanagers[$type];
                if (!$datamanager->set_storage($object))
                {
                    // Major error, panic
                    throw new midcom_error( "Could not set_storage for row #{$num} ({$type} {$object->guid})");
                }
                //$this->_init_csv_variables();
                foreach ($datamanager->schema->field_order as $fieldname)
                {
                    $i++;
                    $data = '';
                    $data = $datamanager->types[$fieldname]->convert_to_csv();
                    if ($i < $type_headers_count)
                    {
                        echo $this->_encode_csv($data, true, false);
                    }
                    else
                    {
                        echo $this->_encode_csv($data, false, false);
                    }
                }
            }
            echo $this->csv['nl'];
            flush();
        }
        // The output context thingamagjick insists on buffering things, make it happy
        ob_start();
    }
}
?>