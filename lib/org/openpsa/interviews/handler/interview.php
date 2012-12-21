<?php
/**
 * @package org.openpsa.interviews
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Phone interview handler
 *
 * @package org.openpsa.interviews
 */
class org_openpsa_interviews_handler_interview extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_edit
{
    /**
     * The member we're interviewing
     *
     * @var org_openpsa_directmarketing_campaign_member
     */
    private $_member = null;

    /**
     * Loads and prepares the schema database.
     */
    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_interview($handler_id, array $args, array &$data)
    {
        $this->_member = new org_openpsa_directmarketing_campaign_member_dba($args[0]);
        $this->_member->require_do('midgard:update');
        $data['campaign'] = new org_openpsa_directmarketing_campaign_dba($this->_member->campaign);

        $data['controller'] = $this->get_controller('simple', $this->_member);

        switch ($data['controller']->process_form())
        {
            case 'save':
                // Redirect to next interviewee
                return new midcom_response_relocate("next/{$data['campaign']->guid}/");

            case 'cancel':
                // Clear lock and return to summary
                $this->_member->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_member_dba::NORMAL;
                $this->_member->update();

                return new midcom_response_relocate("campaign/{$data['campaign']->guid}/");
        }
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_interview($handler_id, array &$data)
    {
        $this->_request_data['person'] = new midcom_db_person($this->_member->person);
        midcom_show_style('show-interview');
    }
}
?>