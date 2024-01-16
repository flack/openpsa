<?php
/**
 * @package org.openpsa.contacts
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\helper\autocomplete;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * org.openpsa.contacts search handler and viewer class.
 *
 * @package org.openpsa.contacts
 */
class org_openpsa_contacts_handler_search extends midcom_baseclasses_components_handler
{
    /**
     * The group results, if any
     */
    private array $_groups = [];

    /**
     * The person results, if any
     */
    private array $_persons = [];

    /**
     * The search string as entered by the user
     */
    private string $_query_string;

    /**
     * The search string, prepared for querying
     */
    private string $_query_string_processed;

    /**
     * The wildcard to wrap around the query terms, if any
     */
    private string $_wildcard_template = '__TERM__';

    /**
     * The query to run
     */
    private array $_query = [];

    /**
     * Which types of objects should be queried
     *
     * Options are: person, group, both
     */
    private string $_query_mode = 'person';

    private function _parse_query(ParameterBag $query)
    {
        $this->_query_mode = $query->getAlnum('query_mode', $this->_query_mode);
        $this->_query_string = trim($query->get('query', ''));
        if (!$this->_query_string) {
            return;
        }
        //Convert asterisks to correct wildcard
        $this->_query_string_processed = str_replace('*', '%', $this->_query_string);

        $this->_query = explode(' ', $this->_query_string_processed);

        // Handle automatic wildcards
        $auto_wildcards = $this->_config->get('auto_wildcards');
        if (   $auto_wildcards
            && !str_contains($this->_query_string_processed, '%')) {
            switch ($auto_wildcards) {
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

    public function _handler_search(Request $request, string $handler_id, array &$data)
    {
        $this->_query_mode = 'both';
        $this->_parse_query($request->query);

        if ($this->_query_mode != 'person') {
            $this->_search_qb_groups();
        }
        if ($this->_query_mode != 'group') {
            $this->_search_qb_persons();
        }

        if ($handler_id == 'search_autocomplete') {
            return $this->_prepare_json_reply();
        }

        if (empty($this->_persons) && count($this->_groups) == 1) {
            return new midcom_response_relocate($this->router->generate('group_view', ['guid' => $this->_groups[0]->guid]));
        }
        if (empty($this->_groups) && count($this->_persons) == 1) {
            return new midcom_response_relocate($this->router->generate('person_view', ['guid' => $this->_persons[0]->guid]));
        }

        $this->_populate_toolbar();

        midcom::get()->head->set_pagetitle($this->_l10n->get('search'));
        $this->add_breadcrumb("", $this->_l10n->get('search'));
        $data['query_string'] = $this->_query_string;
    }

    private function _prepare_json_reply() : JsonResponse
    {
        $data = [];
        foreach ($this->_persons as $person) {
            $data[] = [
                'category' => $this->_l10n->get('persons'),
                'label' => $person->get_label(),
                'value' => $person->get_label(),
                'url' => $this->router->generate('person_view', ['guid' => $person->guid])
            ];
        }
        foreach ($this->_groups as $group) {
            $data[] = [
                'category' => $this->_l10n->get('groups'),
                'label' => $group->get_label(),
                'value' => $group->get_label(),
                'url' => $this->router->generate('group_view', ['guid' => $group->guid])
            ];
        }
        usort($data, [autocomplete::class, 'sort_items']);

        return new JsonResponse($data);
    }

    private function _populate_toolbar()
    {
        $workflow = $this->get_workflow('datamanager');
        $buttons = [];
        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_contacts_person_dba::class)) {
            $buttons[] = $workflow->get_button($this->router->generate('person_new'), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create person'),
                MIDCOM_TOOLBAR_GLYPHICON => 'user-o',
            ]);
        }
        if (midcom::get()->auth->can_user_do('midgard:create', null, org_openpsa_contacts_group_dba::class)) {
            $buttons[] = $workflow->get_button($this->router->generate('group_new', ['type' => 'organization']), [
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('create organization'),
                MIDCOM_TOOLBAR_GLYPHICON => 'group',
            ]);
            $buttons[] = $workflow->get_button($this->router->generate('group_new', ['type' => 'group']), [
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('group')),
                MIDCOM_TOOLBAR_GLYPHICON => 'group',
            ]);
        }
        $this->_view_toolbar->add_items($buttons);
    }

    /**
     * Queries all Contacts objects for the query
     *
     * Displays style element 'search-empty' if no results at all
     * can be found
     */
    public function _show_search(string $handler_id, array &$data)
    {
        $data['mode'] = $this->_query_mode;

        midcom_show_style('search-header');

        $found = false;
        if (!empty($this->_groups)) {
            $found = true;
            midcom_show_style('search-groups-header');
            foreach ($this->_groups as $group) {
                $data['group'] = $group;
                midcom_show_style('search-groups-item');
            }
            midcom_show_style('search-groups-footer');
        }

        if (!empty($this->_persons)) {
            $found = true;
            midcom_show_style('search-persons-header');
            foreach ($this->_persons as $person) {
                $data['person'] = $person;
                midcom_show_style('search-persons-item');
            }
            midcom_show_style('search-persons-footer');
        }

        if (!$found && $this->_query_string) {
            //No results at all (from any of the queries)
            midcom_show_style('search-empty');
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
        if (!$this->_query_string) {
            return;
        }

        $qb_org = org_openpsa_contacts_group_dba::new_query_builder();
        $this->_apply_constraints($qb_org, 'organization');

        $this->_groups = $qb_org->execute();
    }

    /**
     * Does a QB query for persons, returns false or number of matched entries
     */
    private function _search_qb_persons()
    {
        if (!$this->_query_string) {
            return;
        }

        $qb = org_openpsa_contacts_person_dba::new_query_builder();
        $this->_apply_constraints($qb, 'person');

        $this->_persons = $qb->execute();
    }

    private function _apply_constraints(midcom_core_query $qb, string $type)
    {
        // Search using only the fields defined in config
        $fields = array_filter(explode(',', $this->_config->get($type . '_search_fields')));
        if (empty($fields)) {
            throw new midcom_error('Invalid ' . $type . ' search configuration');
        }

        $qb->begin_group('OR');
        if (count($this->_query) > 1) {
            //if we have more than one token in the query, we try to match the entire string as well
            $this->add_constraints($qb, $fields, $this->_query_string_processed);
        }
        $qb->begin_group('AND');
        foreach ($this->_query as $term) {
            $this->add_constraints($qb, $fields, $term);
        }
        $qb->end_group();
        $qb->end_group();
    }

    private function add_constraints(midcom_core_query $qb, array $fields, string $term)
    {
        $term = str_replace('__TERM__', $term, $this->_wildcard_template);

        $qb->begin_group('OR');
        foreach ($fields as $field) {
            if ($field == 'username') {
                midcom_core_account::add_username_constraint($qb, 'LIKE', $term);
            } else {
                $qb->add_constraint($field, 'LIKE', $term);
            }
        }
        $qb->end_group();
    }
}
