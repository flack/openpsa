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
     * @var org_openpsa_directmarketing_campaign
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
     *
     * Special treatement is done for the name field, which is set readonly for non-creates
     * if the simple_name_handling config option is set. (using an auto-generated urlname based
     * on the title, if it is missing.)
     *
     * The operations are done on all available schemas within the DB.
     */
    public function load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_campaign'));
        return $this->_schemadb;
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback (&$controller)
    {
        $this->_campaign = new org_openpsa_directmarketing_campaign_dba();
        $this->_campaign->node = $this->_topic->id;

        if (! $this->_campaign->create())
        {
            debug_print_r('We operated on this object:', $this->_campaign);
            throw new midcom_error('Failed to create a new campaign. Last Midgard error was: ' . midcom_connection::get_error_string());
        }

        return $this->_campaign;
    }

    /**
     * Displays an campaign edit view.
     *
     * Note, that the campaign for non-index mode operation is automatically determined in the can_handle
     * phase.
     *
     * If create privileges apply, we relocate to the index creation campaign,
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $_MIDCOM->auth->require_user_do('midgard:create', null, 'org_openpsa_directmarketing_campaign_dba');

        $this->_schema = $args[0];
        $this->load_schemadb();

        if (!array_key_exists($this->_schema, $this->_schemadb))
        {
            throw new midcom_error_notfound('The campaign type ' . $this->_schema . 'isn\'t available in the schemadb');
        }

        $data['controller'] = $this->get_controller('create');

        switch ($data['controller']->process_form())
        {
            case 'save':
                // Index the campaign
                //$indexer = $_MIDCOM->get_service('indexer');
                //org_openpsa_directmarketing_viewer::index($data['controller']->datamanager, $indexer, $this->_topic);

                $_MIDCOM->relocate("campaign/{$this->_campaign->guid}/");

            case 'cancel':
                $_MIDCOM->relocate('');
                // This will exit.
        }

        if ($this->_campaign != null)
        {
            $_MIDCOM->set_26_request_metadata($this->_campaign->metadata->revised, $this->_campaign->guid);
        }
        $data['view_title'] = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description));
        $_MIDCOM->set_pagetitle($data['view_title']);
        $this->add_breadcrumb("create/{$this->_schema}/", sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($this->_schemadb[$this->_schema]->description)));

        org_openpsa_helpers::dm2_savecancel($this);
    }

    /**
     * Shows the loaded campaign.
     */
    public function _show_create ($handler_id, array &$data)
    {
        midcom_show_style('show-campaign-new');
    }
}
?>