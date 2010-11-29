<?php
/**
 * @package org.openpsa.interviews
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: interview.php,v 1.1 2006/05/08 13:18:40 rambo Exp $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Phone interview handler
 *
 * @package org.openpsa.interviews
 */
class org_openpsa_interviews_handler_interview extends midcom_baseclasses_components_handler
{
    /**
     * The member we're interviewing
     *
     * @var org_openpsa_directmarketing_campaign_member
     */
    private $_member = null;

    /**
     * The Datamanager of the member to display
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

    /**
     * The Controller of the member used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * Loads and prepares the schema database.
     *
     * Special treatment is done for the name field, which is set readonly for non-creates
     * if the simple_name_handling config option is set. (using an auto-generated urlname based
     * on the title, if it is missing.)
     *
     * The operations are done on all available schemas within the DB.
     */
    function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
    }

    /**
     * Internal helper, loads the datamanager for the current article. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);

        if (   ! $this->_datamanager
            || ! $this->_datamanager->autoset_storage($this->_member))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for member {$this->_member->id}.");
            // This will exit.
        }
    }

    /**
     * Internal helper, loads the controller for the current article. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_member);
        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
            // This will exit.
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_interview($handler_id, $args, &$data)
    {
        $this->_member = new org_openpsa_directmarketing_campaign_member_dba($args[0]);
        if (!$this->_member)
        {
            return false;
        }
        $this->_member->require_do('midgard:update');
        $this->_request_data['campaign'] = new org_openpsa_directmarketing_campaign_dba($this->_member->campaign);

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'save':
                // Redirect to next interviewee
                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "next/{$this->_request_data['campaign']->guid}/");
                // This will exit.

            case 'cancel':
                // Clear lock and return to summary
                $this->_member->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER;
                $this->_member->update();

                $_MIDCOM->relocate($_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX) . "campaign/{$this->_request_data['campaign']->guid}/");
                // This will exit.
        }

        return true;
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    function _show_interview($handler_id, &$data)
    {
        $this->_request_data['controller'] =& $this->_controller;
        $this->_request_data['person'] = new midcom_db_person($this->_member->person);
        midcom_show_style('show-interview');
    }
}
?>