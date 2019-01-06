<?php
/**
 * @package org.openpsa.helpers
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

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
     * The current action
     *
     * @var string
     */
    private $_action = 'form';

    /**
     * The Controller of the document used for creating
     *
     * @var controller
     */
    private $_controller;

    /**
     * The object we're working on, if any
     *
     * @var midcom_core_dbaobject
     */
    private $_object;

    public function _on_initialize()
    {
        midcom::get()->style->prepend_component_styledir('org.openpsa.helpers');
    }

    /**
     * @param string $dbaclass The DBA class
     * @param array &$data The local request data.
     */
    public function _handler_create($dbaclass, array &$data)
    {
        $this->_dbaclass = $dbaclass;
        midcom::get()->auth->require_user_do('midgard:create', null, $dbaclass);
        $this->_object = new $dbaclass();

        $this->_load_component_node();

        $this->_controller = $this->load_controller();

        switch ($this->_controller->process()) {
            case 'save':
                $this->_post_create_actions();
                //TODO: indexing
                $this->_action = 'save';
                break;
            case 'cancel':
                $this->_action = 'cancel';
                break;
        }

        $data['controller'] = $this->_controller;
        $data['action'] = $this->_action;

        midcom::get()->skip_page_style = true;
    }

    private function load_controller()
    {
        $defaults = empty($_GET['defaults']) ? [] : $_GET['defaults'];

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
        switch ($this->_dbaclass) {
            case org_openpsa_contacts_person_dba::class:
                $indexer = new org_openpsa_contacts_midcom_indexer($this->_node[MIDCOM_NAV_OBJECT]);
                $indexer->index($this->_controller->get_datamanager());
                break;
            case org_openpsa_products_product_group_dba::class:
                break;
            default:
                throw new midcom_error("The DBA class {$this->_dbaclass} is unsupported");
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
        midcom::get()->componentloader->load($component);
        $topic_guid = $siteconfig->get_node_guid($component);
        $this->_node = $nap->resolve_guid($topic_guid);

        if (!$this->_node) {
            throw new midcom_error("Could not load node information for topic {$topic_guid}. Last error was: " . midcom_connection::get_error_string());
        }
    }

    /**
     * Try to determine the schemadb based on the requested DBA class
     *
     * @return string The path to the schemadb
     */
    private function _get_schemadb_snippet()
    {
        $config_key = 'schemadb';

        switch ($this->_dbaclass) {
            case org_openpsa_contacts_person_dba::class:
                $config_key .= '_person';
                break;
            case org_openpsa_products_product_group_dba::class:
                $config_key .= '_group';
                break;
            default:
                throw new midcom_error("The DBA class {$this->_dbaclass} is unsupported");
        }

        return $this->_node[MIDCOM_NAV_CONFIGURATION]->get($config_key);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        switch ($this->_dbaclass) {
            case org_openpsa_contacts_person_dba::class:
                $title = 'person';
                break;
            case org_openpsa_products_product_group_dba::class:
                $title = 'product group';
                break;
            default:
                throw new midcom_error("The DBA class {$this->_dbaclass} is unsupported");
        }
        $data['title'] = sprintf($this->_l10n_midcom->get('create %s'), $this->_i18n->get_string($title, $this->_node[MIDCOM_NAV_COMPONENT]));

        midcom_show_style('popup-head');
        if ($this->_action != 'form') {
            if ($this->_object) {
                $data['jsdata'] = $this->object_to_jsdata();
            }
            midcom_show_style('chooser-create-after');
        } else {
            midcom_show_style('chooser-create');
        }
        midcom_show_style('popup-foot');
    }

    private function object_to_jsdata()
    {
        $jsdata = [
            'id' => @$this->_object->id,
            'guid' => @$this->_object->guid,
            'pre_selected' => true
        ];

        switch ($this->_dbaclass) {
            case org_openpsa_contacts_person_dba::class:
                //We need to reload the object so that name is constructed properly
                $this->_object = new org_openpsa_contacts_person_dba($this->_object->id);
                $jsdata['name'] = $this->_object->name;
                $jsdata['email'] = $this->_object->email;
                break;
            case org_openpsa_products_product_group_dba::class:
                $this->_object = new org_openpsa_products_product_group_dba($this->_object->id);
                $jsdata['title'] = $this->_object->title;
                break;
            default:
                throw new midcom_error("The DBA class {$this->_dbaclass} is unsupported");
        }

        return json_encode($jsdata);
    }
}
