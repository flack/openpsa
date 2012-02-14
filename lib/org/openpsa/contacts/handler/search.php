<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * org.openpsa.contacts search handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_search extends midcom_baseclasses_components_handler
{
    /**
     * The group results, if any
     *
     * @var array
     */
    private $_groups = array();

    /**
     * The person results, if any
     *
     * @var array
     */
    private $_persons = array();

    /**
     * The search string as entered by the user
     *
     * @var string
     */
    private $_query_string;

    /**
     * The search string, prepared for querying
     *
     * @var string
     */
    private $_query_string_processed;

    /**
     * The wildcard to wrap around the query terms, if any
     *
     * @var string
     */
    private $_wildcard_template = '__TERM__';

    /**
     * The query to run
     *
     * @var array
     */
    private $_query = array();

    /**
     * Which types of objects should be queried
     *
     * Options are: person, group, both
     *
     * @var string
     */
    private $_query_mode = 'person';

    private function _parse_query()
    {
        if (!isset($_GET['query']))
        {
            return;
        }
        if (isset($_GET['query_mode']))
        {
            $this->_query_mode = $_GET['query_mode'];
        }
        $this->_query_string = trim($_GET['query']);
        //Convert asterisks to correct wildcard
        $this->_query_string_processed = str_replace('*', '%', $this->_query_string);

        $this->_query = explode(' ', $this->_query_string_processed);

        // Handle automatic wildcards
        $auto_wildcards = $this->_config->get('auto_wildcards');
        if (   $auto_wildcards
            && strpos($this->_query_string_processed, '%') === false)
        {
            switch ($auto_wildcards)
            {
                case 'both':
                    $this->_wildcard_template = '%__TERM__%';
                    break;
                case 'start':
                    $this->_wildcard_template = '%__TERM__';
                    break;
                case 'end':
                    $this->_wildcard_template = '__TERM__%';
                    break;
                default:
                    debug_add("Don't know how to handle auto_wildcards value '{$auto_wildcards}'", MIDCOM_LOG_WARN);
                    break;
            }
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_search_type($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $this->_parse_query();

        switch ($args[0])
        {
            case 'foaf':
                $_MIDCOM->skip_page_style = true;
                $this->_view = 'foaf';
                if (!empty($this->_query))
                {
                    $this->_search_qb_persons();
                }
                break;
            default:
                throw new midcom_error('Unknown search type ' . $args[0]);
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_search_type($handler_id, array &$data)
    {
        if ($this->_view == 'foaf')
        {
            if (sizeof($this->_persons) > 0)
            {
                midcom_show_style('foaf-header');
                foreach ($this->_persons as $person)
                {
                    $data['person'] = $person;
                    midcom_show_style('foaf-person-item');
                }
                midcom_show_style('foaf-footer');
            }
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_search($handler_id, array $args, array &$data)
    {
        midcom::get('auth')->require_valid_user();
        $this->_query_mode = 'both';
        $this->_parse_query();

        if (!empty($this->_query))
        {
            if ($this->_query_mode != 'person')
            {
                $this->_search_qb_groups();
            }
            if ($this->_query_mode != 'group')
            {
                $this->_search_qb_persons();
            }

            if (   count($this->_groups) == 1
                && count($this->_persons) == 0)
            {
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate($prefix . 'group/' . $this->_groups[0]->guid . '/');
                //This will exit
            }
            else if (   count($this->_groups) == 0
                     && count($this->_persons) == 1)
            {
                $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
                $_MIDCOM->relocate($prefix . 'person/' . $this->_persons[0]->guid . '/');
                //This will exit
            }
        }

        $this->_populate_toolbar();

        $_MIDCOM->set_pagetitle($this->_l10n->get('search'));
        $this->add_breadcrumb("", $this->_l10n->get('search'));
        $data['query_string'] = $this->_query_string;
    }

    private function _populate_toolbar()
    {
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'person/create/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person-new.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba'),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'group/create/organization/',
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create organization'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
                MIDCOM_TOOLBAR_ENABLED => org_openpsa_contacts_interface::find_root_group()->can_do('midgard:create'),
            )
        );

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'group/create/group/',
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('group')),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_people-new.png',
                MIDCOM_TOOLBAR_ENABLED => midcom::get('auth')->can_user_do('midgard:create', null, 'org_openpsa_contacts_group_dba'),
            )
        );
    }

    /**
     * Queries all Contacts objects for $_GET['query']
     *
     * Displays style element 'search-empty' if no results at all
     * can be found
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_search($handler_id, array &$data)
    {
        $data['mode'] = $this->_query_mode;

        midcom_show_style('search-header');

        if (   count($this->_groups) == 0
            && count($this->_persons) == 0
            && $this->_query !== null)
        {
            //No results at all (from any of the queries)
            midcom_show_style('search-empty');
        }
        else
        {
            if (count($this->_groups) > 0)
            {
                midcom_show_style('search-groups-header');
                foreach ($this->_groups as $group)
                {
                    $data['group'] = $group;
                    midcom_show_style('search-groups-item');
                }
                midcom_show_style('search-groups-footer');
            }

            if (count($this->_persons) > 0)
            {
                midcom_show_style('search-persons-header');
                foreach ($this->_persons as $person)
                {
                    $data['person'] = $person;
                    midcom_show_style('search-persons-item');
                }
                midcom_show_style('search-persons-footer');
            }
        }

        midcom_show_style('search-footer');
    }

    /**
     * Does a QB query for groups, returns false or number of matched entries
     *
     * Displays style element 'search-groups-empty' only if $displayEmpty is
     * set to true.
     */
    private function _search_qb_groups()
    {
        if (!$this->_query)
        {
            return false;
        }
        // Search using only the fields defined in config
        $org_fields = explode(',', $this->_config->get('organization_search_fields'));
        if (   !is_array($org_fields)
            || count($org_fields) == 0)
        {
            throw new midcom_error('Invalid organization search configuration');
        }

        $qb_org = org_openpsa_contacts_group_dba::new_query_builder();
        $this->_apply_constraints($qb_org, $org_fields);

        $this->_groups = $qb_org->execute();
    }

    /**
     * Does a QB query for persons, returns false or number of matched entries
     *
     * Displays style element 'search-persons-empty' only if $displayEmpty is
     * set to true.
     */
    private function _search_qb_persons()
    {
        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        if (!empty($this->_query))
        {
            // Search using only the fields defined in config
            $person_fields = explode(',', $this->_config->get('person_search_fields'));
            if (   !is_array($person_fields)
                || count($person_fields) == 0)
            {
                throw new midcom_error( 'Invalid person search configuration');
            }
        }
        $this->_apply_constraints($qb, $person_fields);

        $this->_persons = $qb->execute();
    }

    private function _apply_constraints(midcom_core_query &$qb, array $fields)
    {
        if (sizeof($this->_query) > 1)
        {
            //if we have more than one token in the query, we try to match the entire string as well
            $qb->begin_group('OR');
            foreach ($fields as $field)
            {
                if (empty($field))
                {
                    continue;
                }
                $qb->add_constraint($field, 'LIKE', str_replace('__TERM__', $this->_query_string_processed, $this->_wildcard_template));
            }
        }
        $qb->begin_group('AND');
        foreach ($this->_query as $term)
        {
            $qb->begin_group('OR');
            foreach ($fields as $field)
            {
                if (empty($field))
                {
                    continue;
                }
                $qb->add_constraint($field, 'LIKE', str_replace('__TERM__', $term, $this->_wildcard_template));
            }
            $qb->end_group();
        }
        $qb->end_group();
        if (sizeof($this->_query) > 1)
        {
            $qb->end_group();
        }
    }
}
?>