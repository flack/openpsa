<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Direct marketing page handler
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_campaign_create extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
{
    /**
     * The campaign which has been created
     *
     * @var org_openpsa_directmarketing_campaign_dba
     */
    private $_campaign = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var array
     */
    private $_schemadb = null;

    /**
     * The schema to use for the new campaign.
     *
     * @var string
     */
    private $_schema = null;

    public function get_schema_name()
    {
        return $this->_schema;
    }

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign'));
        if (!array_key_exists($this->_schema, $this->_schemadb)) {
            throw new midcom_error_notfound('The campaign type ' . $this->_schema . 'isn\'t available in the schemadb');
        }
        return $this->_schemadb;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_campaign = new org_openpsa_directmarketing_campaign_dba();
        $this->_campaign->node = $this->_topic->id;

        if (!$this->_campaign->create()) {
            debug_print_r('We operated on this object:', $this->_campaign);
            throw new midcom_error('Failed to create a new campaign. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        return $this->_campaign;
    }

    /**
     * Displays an campaign edit view.
     *
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_user_do('midgard:create', null, 'org_openpsa_directmarketing_campaign_dba');

        $this->_schema = $args[0];

        $data['controller'] = $this->get_controller('create');

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)));

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $this->get_controller('create'),
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        return "campaign/{$this->_campaign->guid}/";
    }
}
