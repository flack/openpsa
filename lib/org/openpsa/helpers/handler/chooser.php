<?php
/**
 * @package org.openpsa.helpers
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Chooser create handler
 *
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers_handler_chooser extends midcom_baseclasses_components_handler
{
    /**
     * The DBA class of the new object.
     *
     * @var string
     */
    private $_dbaclass;

    /**
     * The NAP node for the component the DBA class is from.
     *
     * @var array
     */
    private $_node;

    /**
     * The Controller of the document used for creating
     *
     * @var controller
     */
    private $_controller;

    /**
     * @var midcom_core_dbaobject
     */
    private $_object;

    public function _handler_create(Request $request, string $dbaclass, array &$data)
    {
        $this->_dbaclass = $dbaclass;
        midcom::get()->auth->require_user_do('midgard:create', null, $dbaclass);
        $this->_object = new $dbaclass();

        $this->_load_component_node();

        $defaults = $request->query->all('defaults');
        $this->_controller = $this->load_controller($defaults);
        $data['controller'] = $this->_controller;

        $workflow = $this->get_workflow('chooser', [
            'controller' => $this->_controller
        ]);

        $response = $workflow->run($request);

        if ($workflow->get_state() == 'save') {
            $this->_post_create_actions();
        }
        return $response;
    }

    private function load_controller(array $defaults) : controller
    {
        return datamanager::from_schemadb($this->_get_schemadb_snippet())
            ->set_defaults($defaults)
            ->set_storage($this->_object)
            ->get_controller();
    }

    /**
     * Hook for post-create operations
     */
    private function _post_create_actions()
    {
        if ($this->_dbaclass == 'org_openpsa_contacts_person_dba') {
            $indexer = new org_openpsa_contacts_midcom_indexer($this->_node[MIDCOM_NAV_OBJECT]);
            $indexer->index($this->_controller->get_datamanager());
        }
    }

    /**
     * Determine the node that handles the DBA class and load the respective component
     */
    private function _load_component_node()
    {
        $siteconfig = org_openpsa_core_siteconfig::get_instance();
        $nap = new midcom_helper_nav();
        $component = midcom::get()->dbclassloader->get_component_for_class($this->_dbaclass);
        $topic_guid = $siteconfig->get_node_guid($component);
        $this->_node = $nap->resolve_guid($topic_guid);

        if (!$this->_node) {
            throw new midcom_error("Could not load node information for topic {$topic_guid}. Last error was: " . midcom_connection::get_error_string());
        }

        switch ($this->_dbaclass) {
            case 'org_openpsa_contacts_person_dba':
                $title = 'person';
                break;
            case 'org_openpsa_products_product_group_dba':
                $title = 'product group';
                break;
            default:
                throw new midcom_error("The DBA class {$this->_dbaclass} is unsupported");
        }
        $title = sprintf($this->_l10n_midcom->get('create %s'), $this->_i18n->get_string($title, $this->_node[MIDCOM_NAV_COMPONENT]));
        midcom::get()->head->set_pagetitle($title);
    }

    /**
     * Try to determine the schemadb based on the requested DBA class
     */
    private function _get_schemadb_snippet() : string
    {
        $config_key = 'schemadb';

        switch ($this->_dbaclass) {
            case 'org_openpsa_contacts_person_dba':
                $config_key .= '_person';
                break;
            case 'org_openpsa_products_product_group_dba':
                $config_key .= '_group';
                break;
            default:
                throw new midcom_error("The DBA class {$this->_dbaclass} is unsupported");
        }

        return $this->_node[MIDCOM_NAV_CONFIGURATION]->get($config_key);
    }
}
