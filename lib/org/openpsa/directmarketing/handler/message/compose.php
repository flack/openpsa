<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: compose.php 23975 2009-11-09 05:44:22Z rambo $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Discussion forum index
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_compose extends midcom_baseclasses_components_handler
{
    /**
     * The message which has been created
     *
     * @var org_openpsa_directmarketing_message
     * @access private
     */
    var $_message = null;

    /**
     * MidCOM helper Datamanager2 class
     * 
     * @access private
     * @var midcom_helper_datamanager2_datamanager
     */
    var $_datamanager = false;

    /**
     * Internal helper, loads the datamanager for the current message. Any error triggers a 500.
     *
     * @access private
     */
    function _load_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        if (!$this->_datamanager)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to create a DM2 instance for messages.");
            // This will exit.
        }
    }

    /**
     * Phase for composing a message
     * 
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array $args           Variable arguments
     * @param array &$data          Public request data, passed by reference
     * @return boolean              Indicating success
     */
    function _handler_compose($handler_id, $args, &$data)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $_MIDCOM->auth->request_sudo();
        //Load message
        $data['message'] = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        if (   !$data['message']
            || !$data['message']->guid)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The message {$args[0]} was not found.");
            // This will exit.
        }

        $data['campaign'] = new org_openpsa_directmarketing_campaign_dba($data['message']->campaign);
        if (   !$data['campaign']
            || $data['campaign']->node != $this->_topic->id)
        {
            $_MIDCOM->generate_error(MIDCOM_ERRNOTFOUND, "The campaign {$this->_message->campaign} was not found.");
            // This will exit.
        }

        $this->_component_data['active_leaf'] = "campaign_{$data['campaign']->id}";

        $this->_load_datamanager();
        $this->_datamanager->autoset_storage($data['message']);
        $data['message_obj'] =& $data['message'];
        $data['message_dm'] =& $this->_datamanager;

        if (   !is_object($data['message'])
            || !$data['message']->id)
        {
            debug_pop();
            return false;
        }

        if ($handler_id === 'compose4person')
        {
            $data['person'] = new org_openpsa_contacts_person_dba($args[1]);
            if (   !is_object($data['person'])
                || !$data['person']->id)
            {
                debug_pop();
                return false;
            }
            $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
            $qb->add_constraint('person', '=', $this->_request_data['person']->id);
            $memberships = $qb->execute();
            if (empty($memberships))
            {
                $data['member'] = new org_openpsa_directmarketing_campaign_member_dba();
                $data['member']->person = $data['person']->id;
                $data['member']->campaign = $data['message']->campaign;
                $data['member']->guid = 'dummy';
            }
            else
            {
                $data['member'] = $memberships[0];
            }
        }

        $data['message_array'] = $this->_datamanager->get_content_raw();

        if (!array_key_exists('content', $data['message_array']))
        {
            debug_add('"content" not defined in schema, aborting', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        //Substyle handling
        @debug_add("\$data['message_array']['substyle']='{$data['message_array']['substyle']}'");
        if (   array_key_exists('substyle', $data['message_array'])
            && !empty($data['message_array']['substyle'])
            && !preg_match('/^builtin:/', $data['message_array']['substyle']))
        {
            debug_add("Appending substyle {$data['message_array']['substyle']}");
            $_MIDCOM->substyle_append($data['message_array']['substyle']);
        }
        //This isn't necessary for dynamic-loading, but is nice for "preview".
        $_MIDCOM->skip_page_style = true;
        debug_add('message type: ' . $data['message_obj']->orgOpenpsaObtype);
        switch($data['message_obj']->orgOpenpsaObtype)
        {
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_TEXT:
            case ORG_OPENPSA_MESSAGETYPE_SMS:
                debug_add('Forcing content type: text/plain');
                $_MIDCOM->cache->content->content_type('text/plain');
            break;
            //TODO: Other content type overrides ?
        }
        debug_pop();
        $_MIDCOM->auth->drop_sudo();
        return true;
    }

    /**
     * Compose the message and send it for post-formatting
     * 
     * @access public
     * @param String $handler_id    Name of the request handler
     * @param array &$data          Public request data, passed by reference
     * @return String               Composed message
     */
    function _show_compose($handler_id, &$data)
    {
        if ($handler_id === 'compose4person')
        {
            ob_start();
            $this->_real_show_compose($handler_id, $data);
            $composed = ob_get_contents();
            ob_end_clean();
            $personalized = $data['member']->personalize_message($composed, $data['message']->orgOpenpsaObtype, $data['person']);
            echo $personalized;
            return;
        }
        return $this->_real_show_compose($handler_id, $data);
    }

    function _real_show_compose($handler_id, &$data)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $prefix='';
        if (   array_key_exists('substyle', $data['message_array'])
            && !empty($data['message_array']['substyle'])
            && preg_match('/^builtin:(.*)/', $data['message_array']['substyle'], $matches_style))
        {
            $prefix = $matches_style[1].'-';
        }
        debug_add("Calling midcom_show_style(\"compose-{$prefix}message\")");
        midcom_show_style("compose-{$prefix}message");
        debug_pop();
    }
}
?>