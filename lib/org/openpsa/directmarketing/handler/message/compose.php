<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;

/**
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_compose extends midcom_baseclasses_components_handler
{
    use org_openpsa_directmarketing_handler;

    /**
     * @var org_openpsa_directmarketing_campaign_message_dba
     */
    private $_message;

    /**
     * @var datamanager
     */
    private $_datamanager;

    /**
     * @var org_openpsa_directmarketing_campaign_member_dba
     */
    private $member;

    /**
     * @var org_openpsa_contacts_person_dba
     */
    private $person;

    /**
     * Internal helper, loads the datamanager for the current message. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $this->_datamanager = datamanager::from_schemadb($this->_config->get('schemadb_message'));
        $this->_datamanager->set_storage($this->_message);
    }

    /**
     * Phase for composing a message
     *
     * @param string $guid The object's GUID
     * @param string $person The person's GUID
     */
    public function _handler_compose(array &$data, string $guid, string $person = null)
    {
        midcom::get()->auth->request_sudo($this->_component);
        //Load message
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($guid);
        $this->load_campaign($this->_message->campaign);

        $this->_load_datamanager();
        $data['message_array'] = $this->_datamanager->get_content_raw();
        if (!array_key_exists('content', $data['message_array'])) {
            throw new midcom_error('"content" not defined in schema');
        }

        //Substyle handling
        if (   !empty($data['message_array']['substyle'])
            && !str_starts_with($data['message_array']['substyle'], 'builtin:')) {
            debug_add("Appending substyle {$data['message_array']['substyle']}");
            midcom::get()->style->append_substyle($data['message_array']['substyle']);
        }

        if ($person !== null) {
            $this->person = new org_openpsa_contacts_person_dba($person);
            $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
            $qb->add_constraint('person', '=', $this->person->id);
            $memberships = $qb->execute();
            if (empty($memberships)) {
                $this->member = new org_openpsa_directmarketing_campaign_member_dba();
                $this->member->person = $this->person->id;
                $this->member->campaign = $this->_message->campaign;
            } else {
                $this->member = $memberships[0];
            }
        }

        //This isn't necessary for dynamic-loading, but is nice for "preview".
        midcom::get()->skip_page_style = true;
        debug_add('message type: ' . $this->_message->orgOpenpsaObtype);
        if ($this->_message->orgOpenpsaObtype == org_openpsa_directmarketing_campaign_message_dba::EMAIL_TEXT) {
            debug_add('Forcing content type: text/plain');
            midcom::get()->header('Content-Type: text/plain');
        }
        midcom::get()->auth->drop_sudo();
    }

    /**
     * Compose the message and send it for post-formatting
     */
    public function _show_compose(string $handler_id, array &$data)
    {
        if ($handler_id === 'compose4person') {
            ob_start();
            $this->_real_show_compose($data);
            $composed = ob_get_clean();
            $personalized = $this->member->personalize_message($composed, $this->_message->orgOpenpsaObtype, $this->person);
            echo $personalized;
            return;
        }
        $this->_real_show_compose($data);
    }

    private function _real_show_compose(array $data)
    {
        $prefix = '';
        if (   !empty($data['message_array']['substyle'])
            && preg_match('/^builtin:(.*)/', $data['message_array']['substyle'], $matches_style)) {
            $prefix = $matches_style[1] . '-';
        }
        debug_add("Calling midcom_show_style(\"compose-{$prefix}message\")");
        midcom_show_style("compose-{$prefix}message");
    }
}
