<?php
/**
 * @package org.openpsa.core
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\grid\provider\client;
use midcom\grid\provider;
use Symfony\Component\HttpFoundation\Request;

/**
 * directmarketing campaign rules handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_campaign_rules extends midcom_baseclasses_components_handler
implements client
{
    use org_openpsa_directmarketing_handler;

    private org_openpsa_directmarketing_campaign_dba $_campaign;

    private array $rules;

    public function get_qb(?string $field = null, string $direction = 'ASC', array $search = []) : midcom_core_query
    {
        $resolver = new org_openpsa_directmarketing_campaign_ruleresolver();
        $resolver->resolve($this->rules);
        $query = $resolver->get_mc();

        if ($field !== null) {
            $query->add_order($field, $direction);
        }
        // Set the order
        $query->add_order('lastname', 'ASC');
        $query->add_order('firstname', 'ASC');
        $query->add_order('email', 'ASC');

        return $query;
    }

    public function get_row(midcom_core_dbaobject $person) : array
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $url = $siteconfig->get_node_full_url('org.openpsa.contacts') . 'person/';

        return [
            'id' => $person->id,
            'index_firstname' => $person->firstname,
            'firstname' => '<a target="_blank" href="' . $url . $person->guid . '/">' . $person->firstname . '</a>',
            'index_lastname' => $person->lastname,
            'lastname' => '<a target="_blank" href="' . $url . $person->guid . '/">' . $person->lastname . '</a>',
            'index_email' => $person->email,
            'email' => '<a target="_blank" href="' . $url . $person->guid . '/">' . $person->email . '</a>'
        ];
    }

    /**
     * Displays campaign members.
     */
    public function _handler_query(Request $request, string $guid, array &$data)
    {
        $this->_campaign = $this->load_campaign($guid);
        $this->_campaign->require_do('midgard:update');
        $this->rules = $this->_load_rules($request);

        midcom::get()->skip_page_style = true;
        $data['provider'] = new provider($this);

        return $this->show('show-campaign-members');
    }

    /**
     * Displays an campaign edit view.
     */
    public function _handler_edit_query(Request $request, string $guid, array &$data)
    {
        $this->_campaign = $this->load_campaign($guid);
        $this->_campaign->require_do('midgard:update');
        $data['campaign'] = $this->_campaign;

        // PONDER: Locking ?
        if ($request->request->has('midcom_helper_datamanager2_cancel')) {
            return new midcom_response_relocate($this->router->generate('view_campaign', ['guid' => $guid]));
        }

        //check if it should be saved
        if ($request->request->has('midcom_helper_datamanager2_save')) {
            try {
                $rules = $this->_load_rules($request);
            } catch (midcom_error $e) {
                midcom::get()->uimessages->add('org.openpsa.directmarketing', $this->_l10n->get($e->getMessage()), 'error');
                return;
            }

            //update campaign & Schedule background members refresh
            $this->_campaign->rules = $rules;
            if ($this->_campaign->update()) {
                //Schedule background members refresh
                $this->_campaign->schedule_update_smart_campaign_members();

                //Save ok, relocate
                return new midcom_response_relocate($this->router->generate('view_campaign', ['guid' => $guid]));
            }
            //Save failed
            midcom::get()->uimessages->add($this->_component, sprintf($this->_l10n->get('error when saving rule, errstr: %s'), midcom_connection::get_error_string()), 'error');
        }

        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => "#",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('advanced rule editor'),
                MIDCOM_TOOLBAR_GLYPHICON => 'cogs',
                MIDCOM_TOOLBAR_OPTIONS => [
                    'id' => 'openpsa_dirmar_edit_query_advanced',
                ],
            ],
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('edit_campaign_query', ['guid' => $guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('edit rules'),
                MIDCOM_TOOLBAR_GLYPHICON => 'filter',
                MIDCOM_TOOLBAR_OPTIONS => [
                    'id' => 'openpsa_dirmar_edit_query',
                ],
            ]
        ];
        $this->_view_toolbar->add_items($buttons);

        $provider = new provider($this);
        $data['grid'] = $provider->get_grid('preview_persons');

        midcom::get()->head->enable_jquery();
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/org.openpsa.directmarketing/edit_query.css');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.datamanager/default.css');

        midcom::get()->head->set_pagetitle($this->_campaign->title);
        $this->bind_view_to_object($this->_campaign);
        $this->add_breadcrumb('', $this->_l10n->get('edit rules'));

        return $this->show('show-campaign-edit_query');
    }

    private function _load_rules(Request $request) : array
    {
        if ($rules = $request->get('midcom_helper_datamanager2_dummy_field_rules')) {
            return org_openpsa_directmarketing_campaign_ruleresolver::parse($rules);
        }
        return $this->_campaign->rules;
    }
}
