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
     * The search string
     *
     * @var string
     */
    private $_search;

    private function _get_search_string()
    {
        if (isset($_GET['query']))
        {
            //Convert asterisks to correct wildcard
            $search = str_replace('*', '%', $_GET['query']);

            // Handle automatic wildcards
            $auto_wildcards = $this->_config->get('auto_wildcards');
            if (   $auto_wildcards
                && strpos($search, '%') === false)
            {
                switch($auto_wildcards)
                {
                    case 'both':
                        $search = "%{$search}%";
                        break;
                    case 'start':
                        $search = "%{$search}";
                        break;
                    case 'end':
                        $search = "{$search}%";
                        break;
                    default:
                        debug_add("Don't know how to handle auto_wildcards value '{$auto_wildcards}'", MIDCOM_LOG_WARN);
                        break;
                }
            }
            $this->_search = $search;
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     */
    public function _handler_search_type($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();
        switch ($args[0])
        {
            case 'foaf':
                $_MIDCOM->skip_page_style = true;
                $this->_view = 'foaf';
                $this->_search_qb_persons();
                break;
            default:
                throw new midcom_error('Unknown search type ' . $args[0]);
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_search_type($handler_id, &$data)
    {
        if ($this->_view == 'foaf')
        {
            if (sizeof($this->_persons) > 0)
            {
                midcom_show_style('foaf-header');
                foreach ($this->_persons as $person)
                {
                    $GLOBALS['view_person'] = $person;
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
    public function _handler_search($handler_id, $args, &$data)
    {
        $_MIDCOM->auth->require_valid_user();

        $this->_get_search_string();
        if ($this->_search)
        {
            $this->_search_qb_groups();
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

        //We always want to display *something*
        if ($_MIDCOM->auth->can_user_do('midgard:create', null, 'org_openpsa_contacts_person_dba'))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "person/create/",
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_person.png',
                )
            );
        }
        $root_group = org_openpsa_contacts_interface::find_root_group();
        if ($_MIDCOM->auth->can_do('midgard:create', $root_group))
        {
            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => 'group/create/',
                    MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create organization'),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/new-dir.png',
                )
            );
        }
    }

    /**
     * Queries all Contacts objects for $_GET['query']
     *
     * Displays style element 'search-empty' if no results at all
     * can be found
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_search($handler_id, &$data)
    {
        midcom_show_style('search-header');

        if (   count($this->_groups) == 0
            && count($this->_persons) == 0)
        {
            //No results at all (from any of the queries)
            midcom_show_style('search-empty');
        }
        else
        {
            if (count($this->_groups) > 0)
            {
                midcom_show_style('search-groups-header');
                foreach($this->_groups as $group)
                {
                    $GLOBALS['view_group'] = $group;
                    midcom_show_style('search-groups-item');
                }
                midcom_show_style('search-groups-footer');
            }

            if (count($this->_persons) > 0)
            {
                $_MIDCOM->load_library('org.openpsa.contactwidget');
                midcom_show_style('search-persons-header');
                foreach($this->_persons as $person)
                {
                    $GLOBALS['view_person'] = $person;
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
        if (!$this->_search)
        {
            return false;
        }

        $qb_org = org_openpsa_contacts_group_dba::new_query_builder();
        $qb_org->begin_group('OR');

        // Search using only the fields defined in config
        $org_fields = explode(',', $this->_config->get('organization_search_fields'));
        if (   !is_array($org_fields)
            || count($org_fields) == 0)
        {
            throw new midcom_error('Invalid organization search configuration');
        }

        foreach ($org_fields as $field)
        {
            if (empty($field))
            {
                continue;
            }
            $qb_org->add_constraint($field, 'LIKE', $this->_search);
        }

        $qb_org->end_group();

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
        if (!$this->_search)
        {
            return false;
        }

        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $qb->begin_group('OR');

        // Search using only the fields defined in config
        $person_fields = explode(',', $this->_config->get('person_search_fields'));
        if (   !is_array($person_fields)
            || count($person_fields) == 0)
        {
            throw new midcom_error( 'Invalid person search configuration');
        }

        foreach ($person_fields as $field)
        {
            if (empty($field))
            {
                continue;
            }
            $qb->add_constraint($field, 'LIKE', $this->_search);
        }

        $qb->end_group();
        $this->_persons = $qb->execute();
    }
}
?>