<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * MidCOM wrapped class for access to stored queries
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_campaign_member_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_campaign_member';

    function __construct($id = null)
    {
        $this->_use_rcs = false;
        $this->_use_activitystream = false;
        $ret = parent::__construct($id);
        if (!$this->orgOpenpsaObtype)
        {
            $this->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER;
        }
        return $ret;
    }

    static function new_query_builder()
    {
        return $_MIDCOM->dbfactory->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return $_MIDCOM->dbfactory->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return $_MIDCOM->dbfactory->get_cached(__CLASS__, $src);
    }

    /**
     * Human-readable label for cases like Asgard navigation
     */
    function get_label()
    {
        if ($this->person)
        {
            $person = new midcom_db_person($this->person);
            return $person->name;
        }
        return "member #{$this->id}";
    }

    /**
     * Checks for duplicate memberships returns true for NO duplicate memberships
     */
    function _check_duplicate_membership()
    {
        $qb = new midgard_query_builder('org_openpsa_campaign_member');
        $qb->add_constraint('person', '=', $this->person);
        $qb->add_constraint('campaign', '=', $this->campaign);
        //For tester membership check only other tester memberships for duplicates, for other memberships check all BUT testers
        if ($this->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER)
        {
            $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
        }
        else
        {
            $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
        }
        if ($this->id)
        {
            $qb->add_constraint('id', '<>', $this->id);
        }
        $ret = @$qb->execute();
        if ($ret === false)
        {
            //Failure in execute, return false to be safe
            return false;
        }
        if (   is_array($ret)
            && count($ret)>0)
        {
            //We already have a membership with the same campaign and person
            return false;
        }
        return true;
    }

    function _on_creating()
    {
        return $this->_check_duplicate_membership();
    }

    function _on_updating()
    {
        return $this->_check_duplicate_membership();
    }

    /**
     * Substitutes magic strings in content with values from the membership
     * and/or the person.
     */
    function personalize_message($content, $message_type=-1, &$person=false, $node=false)
    {
        if (!$node)
        {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($nap->get_current_node());
        }
        if (!is_object($person))
        {
            $person =& org_openpsa_contacts_person_dba::get_cached($this->person);
        }

        $sep_start = '<';
        $sep_end = '>';

        if ($message_type == ORG_OPENPSA_MESSAGETYPE_EMAIL_HTML)
        {
            $sep_start = '&lt;';
            $sep_end = '&gt;';
        }

        // Unsubscribe URL
        $content = str_replace($sep_start . 'UNSUBSCRIBE_URL' . $sep_end, $this->get_unsubscribe_url($node, $person), $content);
        // Unsubscribe from all URL
        $content = str_replace($sep_start . 'UNSUBSCRIBE_ALL_URL' . $sep_end, "{$node[MIDCOM_NAV_FULLURL]}campaign/unsubscribe_all/{$person->guid}/", $content);
        // Unsubscribe from all URL
        $content = str_replace($sep_start . 'UNSUBSCRIBE_ALL_FUTURE_URL' . $sep_end, "{$node[MIDCOM_NAV_FULLURL]}campaign/unsubscribe_all_future/{$person->guid}/all.html", $content);
        // General membership GUID
        $content = str_replace($sep_start . 'MEMBER_GUID' . $sep_end, $this->guid, $content);
        // General person GUID
        $content = str_replace($sep_start . 'PERSON_GUID' . $sep_end, $person->guid, $content);
        // E-Mail
        $content = str_replace($sep_start . 'EMAIL' . $sep_end, $person->email, $content);
        // Firstname
        $content = str_replace($sep_start . 'FNAME' . $sep_end, $person->firstname, $content);
        // Lastname
        $content = str_replace($sep_start . 'LNAME' . $sep_end, $person->lastname, $content);
        // Username
        $content = str_replace($sep_start . 'UNAME' . $sep_end, $person->username, $content);
        // Password (if plaintext)
        if (preg_match('/^\*\*(.*)/', $person->password, $pwd_matches))
        {
            $plaintext_password = $pwd_matches[1];
        }
        else
        {
            $plaintext_password = $sep_start . 'unknown' . $sep_end;
        }
        $content = str_replace($sep_start . 'PASSWD' . $sep_end, $plaintext_password, $content);
        // Callback functions
        if (preg_match_all('/' . $sep_start . 'CALLBACK:(.*?)' . $sep_end . '/', $content, $callback_matches))
        {
            foreach($callback_matches[0] as $k => $search)
            {
                $callback_func =& $callback_matches[1][$k];
                if (!is_callable($callback_func))
                {
                    continue;
                }
                $replace = call_user_func($callback_func, $person, $this);
                $content = str_replace($search, $replace, $content);
            }
        }

        return $content;
    }

    function get_unsubscribe_url($node=false, $person=false)
    {
        if (!$node)
        {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($nap->get_current_node());
        }
        if (!is_object($person))
        {
            $person = new org_openpsa_contacts_person_dba($this->person);
        }
        return "{$node[MIDCOM_NAV_FULLURL]}campaign/unsubscribe/{$this->guid}/";
    }

    /**
     * Creates a message receipt of type.
     */
    function create_receipt($message_id, $type, $token = '', $parameters = false)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        $receipt = new org_openpsa_directmarketing_campaign_message_receipt_dba();
        $receipt->orgOpenpsaObtype = $type;
        $receipt->person = $this->person;
        $receipt->message = $message_id;
        $receipt->token = $token;
        $receipt->timestamp = time();
        $stat = $receipt->create();
        //PONDER: do something in case of failure ?
        if (!$stat)
        {
            debug_add('Failed to create, errstr: ' . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
            debug_pop();
            return $stat;
        }
        if (   is_array($parameters)
            && !empty($parameters))
        {
            foreach ($parameters as $param_data)
            {
                if (   !isset($param_data['domain'])
                    || empty($param_data['domain'])
                    || !isset($param_data['name'])
                    || empty($param_data['name'])
                    || !isset($param_data['value'])
                    || empty($param_data['value'])
                    )
                {
                    // TODO: Log warning
                    continue;
                }
                $receipt->set_parameter($param_data['domain'], $param_data['name'], $param_data['value']);
            }
        }
    }
}

?>