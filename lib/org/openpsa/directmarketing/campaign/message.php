<?php
/**
 * @package org.openpsa.directmarketing
 * @author Nemein Oy http://www.nemein.com/
 * @copyright Nemein Oy http://www.nemein.com/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Message class, handles storage of various messages and sending them out.
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_campaign_message_dba extends midcom_core_dbaobject
{
    var $__midcom_class_name__ = __CLASS__;
    var $__mgdschema_class_name__ = 'org_openpsa_campaign_member';

    var $send_output = false;
    var $sms_lib = 'org.openpsa.smslib';
    var $sms_lib_api = 'tambur';
    var $sms_lib_location = '';
    var $sms_lib_client_id = '';
    var $sms_lib_user = '';
    var $sms_lib_password = '';
    var $mms_lib = ''; //Defaults to reference, see constructor
    var $mms_lib_api = ''; //Defaults to reference, see constructor
    var $mms_lib_location = ''; //Defaults to reference, see constructor
    var $mms_lib_client_id = '';
    var $mms_lib_user = ''; //Defaults to reference, see constructor
    var $mms_lib_password = ''; //Defaults to reference, see constructor
    var $test_mode = false;
    var $chunk_size = 50;
    var $_offset = 0;
    var $_chunk_num = 0;
    var $_chunk_max_recurse = 15; //How many times to recurse if all results are filtered (speed vs memory [and risk on crashing], higher is faster)
    var $token_size = 15;

    function __construct($id = null)
    {
        $stat = parent::__construct($id);
        if ($stat)
        {
            /* To specify different values for MMS and SMS first unset the MMS
               values to destroy the reference, then set correct value */
            $this->mms_lib = &$this->sms_lib;
            $this->mms_lib_api = &$this->sms_lib_api;
            $this->mms_lib_location = &$this->sms_lib_location;
            $this->mms_lib_client_id = &$this->sms_lib_client_id;
            $this->mms_lib_user = &$this->sms_lib_user;
            $this->mms_lib_password = &$this->sms_lib_password;
        }

        $config =& $GLOBALS['midcom_component_data']['org.openpsa.directmarketing']['config'];

        $this->chunk_size = $config->get('chunk_size');

        return $stat;
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

    function get_parent_guid_uncached()
    {
        if (empty($this->campaign))
        {
            return null;
        }
        if (method_exists($this, 'new_collector'))
        {
            // Use collector, it's faster
            return org_openpsa_directmarketing_campaign_message_dba::get_parent_guid_uncached_static($this->guid);
        }
        $campaign = new org_openpsa_directmarketing_campaign_dba($this->campaign);
        if (   !is_object($campaign)
            || empty($campaign->id))
        {
            return null;
        }
        return $campaign->guid;
    }

    function get_parent_guid_uncached_static($guid)
    {
        if (empty($guid))
        {
            return null;
        }

        $mc = org_openpsa_directmarketing_campaign_message_dba::new_collector('guid', $guid);
        $mc->add_value_property('campaign');
        $stat = $mc->execute();
        if (!$stat)
        {
            // error
            return null;
        }
        $keys = $mc->list_keys();
        list ($key, $copy) = each ($keys);
        $campaign_id = $mc->get_subkey($key, 'campaign');
        if ($campaign_id === false)
        {
            // error
            return null;
        }
        $mc2 = org_openpsa_directmarketing_campaign_dba::new_collector('id', $campaign_id);
        $mc2->add_value_property('guid');
        $stat = $mc2->execute();
        if (!$stat)
        {
            // error
            return null;
        }
        $keys2 = $mc2->list_keys();
        list ($key2, $copy2) = each ($keys2);
        $campaign_guid = $mc2->get_subkey($key2, 'guid');
        if ($campaign_guid === false)
        {
            // error
            return null;
        }
        return $campaign_guid;
    }

    function get_dba_parent_class()
    {
        return 'org_openpsa_directmarketing_campaign_dba';
    }

    function _on_created()
    {
        parent::_on_created();

        if (!$this->orgOpenpsaObtype)
        {
            $this->orgOpenpsaObtype = ORG_OPENPSA_MESSAGETYPE_EMAIL_TEXT;
            $this->update();
        }
    }

    function _on_loaded()
    {
        $this->title = trim($this->title);
        if (   $this->id
            && empty($this->title))
        {
            $this->title = 'untitled';
        }
        return true;
    }

    /**
     * Matches message type and calls correct subhandler
     */
    function send_status()
    {
        switch($this->orgOpenpsaObtype)
        {
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_TEXT:
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_HTML:
                return $this->send_email_status();
            break;
            case ORG_OPENPSA_MESSAGETYPE_SMS:
                return $this->send_sms_status();
            break;
            case ORG_OPENPSA_MESSAGETYPE_MMS:
                return $this->send_mms_status();
            break;
            case ORG_OPENPSA_MESSAGETYPE_CALL:
                //This quite naturally cannot be handled via web
                return false;
            break;
            case ORG_OPENPSA_MESSAGETYPE_SNAILMAIL:
                //While this can in theory be automated we don't do it yet
                return false;
            break;
            case ORG_OPENPSA_MESSAGETYPE_FAX:
                //See above
                return false;
            break;
            default:
                return false;
            break;
        }
    }

    /**
     * Sends $content to all members of the campaign
     */
    function send(&$content, &$from, &$subject, &$data_array)
    {
        //Disable limits, TODO: Make smarter if at all possible, see reindex.php from torben for ideas
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
        //Make sure we have smart campaign members up-to-date (this might take a while)
        if (!$this->test_mode)
        {
            $this->_check_campaign_up_to_date();
        }
        switch($this->orgOpenpsaObtype)
        {
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_TEXT:
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_HTML:
                return $this->send_email($subject, $content, $from, $data_array);
            break;
            case ORG_OPENPSA_MESSAGETYPE_SMS:
                return $this->send_sms($content, $from, $data_array);
            break;
            case ORG_OPENPSA_MESSAGETYPE_MMS:
                return $this->send_mms($content, $from, $data_array);
            break;
            case ORG_OPENPSA_MESSAGETYPE_CALL:
                //This quite naturally cannot be handled via web
                return false;
            break;
            case ORG_OPENPSA_MESSAGETYPE_SNAILMAIL:
                //While this can in theory be automated we don't do it yet
                return false;
            break;
            case ORG_OPENPSA_MESSAGETYPE_FAX:
                //See above
                return false;
            break;
            default:
                return false;
            break;
        }
    }

    /**
     * Check if this message is attached to a smart campaign, if so update the campaign members
     */
    function _check_campaign_up_to_date()
    {
        $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
        $campaign = new org_openpsa_directmarketing_campaign_dba($this->campaign);
        $_MIDCOM->auth->drop_sudo();
        if ($campaign->orgOpenpsaObtype == ORG_OPENPSA_OBTYPE_CAMPAIGN_SMART)
        {
            $campaign->update_smart_campaign_members();
        }
    }

    /**
     * Sends $content to all members of the campaign
     */
    function send_bg($url_base, $batch, &$content, &$from, &$subject, &$data_array)
    {
        //Disable limits, TODO: Make smarter if at all possible, see reindex.php from torben for ideas
        @ini_set('memory_limit', -1);
        @ini_set('max_execution_time', 0);
        //For first batch (they start from 1 instead of 0) make sure we have smart campaign members up to date
        if (   $batch == 1
            && !$this->test_mode)
        {
            $this->_check_campaign_up_to_date();
        }
        // Register sendStarted if not already set (and we're not in test mode)
        if (!$this->test_mode)
        {
            if (!$this->sendStarted)
            {
                $this->sendStarted = time();
                $this->update();
            }
        }
        switch($this->orgOpenpsaObtype)
        {
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_TEXT:
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_HTML:
                list ($status, $reg_next) = $this->send_email_bg($batch, $subject, $content, $from, $data_array);
            break;
            case ORG_OPENPSA_MESSAGETYPE_SMS:
                list ($status, $reg_next) = $this->send_sms_bg($batch, $content, $from, $data_array);
            break;
            case ORG_OPENPSA_MESSAGETYPE_MMS:
                list ($status, $reg_next) = $this->send_mms_bg($batch, $content, $from, $data_array);
            break;
            case ORG_OPENPSA_MESSAGETYPE_CALL:
                //This quite naturally cannot be handled via web
                return false;
            break;
            case ORG_OPENPSA_MESSAGETYPE_SNAILMAIL:
                //While this can in theory be automated we don't do it yet
                return false;
            break;
            case ORG_OPENPSA_MESSAGETYPE_FAX:
                //See above
                return false;
            break;
            default:
                return false;
            break;
        }
        debug_push_class(__CLASS__, __FUNCTION__);

        debug_add("status: {$status}, reg_next: {$reg_next}");
        if ($reg_next)
        {
            //register next batch
            $args = array
            (
                'batch' => $batch+1,
                'url_base' => $url_base,
            );
            debug_add("Registering batch #{$args['batch']} for {$args['url_base']}");
            $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
            $atstat = midcom_services_at_interface::register(time()+60, 'org.openpsa.directmarketing', 'background_send_message', $args);
            $_MIDCOM->auth->drop_sudo();
            if (!$atstat)
            {
                debug_add("FAILED to register batch #{$args['batch']} for {$args['url_base']}, errstr: " . midcom_application::get_error_string(), MIDCOM_LOG_ERROR);
                debug_pop();
                return false;
            }
        }
        else
        {
            // Last batch done, register sendCompleted if we're not in test mode
            if (!$this->test_mode)
            {
                $this->sendCompleted = time();
                $this->update();
            }
        }

        debug_pop();
        return $status;
    }


    function _qb_filter_results($results)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        //Make a map for receipt filtering
        $results_persons = array();
        $results_person_map = array();
        foreach ($results as $k => $member)
        {
            $results_persons[] = $member->person;
            $results_person_map[$member->person] = $k;
        }
        //Get receipts for our persons if any
        if (count($results_persons)>0)
        {
            // FIXME: Rewrite for collector
            $qb_receipts = new midgard_query_builder('org_openpsa_campaign_message_receipt');
            $qb_receipts->add_constraint('message', '=', $this->id);
            $qb_receipts->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_MESSAGERECEIPT_SENT);
            $qb_receipts->add_constraint('person', 'IN', $results_persons);
            $qb_receipts->end_group();

            $receipts = $qb_receipts->execute();
            //Filter results array by receipts
            if (is_array($receipts))
            {
                debug_add('Found ' . count($receipts) . ' send receipts for this chunk');
                if (count($receipts)>0)
                {
                    foreach ($receipts as $receipt)
                    {
                        if (   !isset($results_person_map[$receipt->person])
                            || !isset($results[$results_person_map[$receipt->person]]))
                        {
                            continue;
                        }
                        debug_add("Removing person {$receipt->person} from results");
                        unset($results[$results_person_map[$receipt->person]]);
                    }
                }
            }
        }
        debug_pop();
        return $results;
    }

    function _qb_chunk_limits(&$qb)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Processing chunk {$this->_chunk_num}");
        $this->_offset = $this->_chunk_num*$this->chunk_size;
        if ($this->_offset>0)
        {
            debug_add("Setting offset to {$this->_offset}");
            $qb->set_offset($this->_offset);
        }
        debug_add("Setting limit to {$this->chunk_size}");
        $qb->set_limit($this->chunk_size);
        debug_pop();
    }

    /**
     * Loops trough send filter in chunks, adds some common constraints and checks for send-receipts.
     */
    function _qb_send_loop($callback_name)
    {
        $ret = $this->_qb_single_chunk($callback_name);
        $this->_chunk_num++;
        //Trivial rate limiting
        sleep(1);
        return $ret;
    }

    /**
     * Sets the common constrains for campaign members queries
     */
    function _qb_common_constaints(&$qb)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add("Setting constraint campaign = {$this->campaign}");
        $qb->add_constraint('campaign', '=', $this->campaign);
        $qb->add_constraint('suspended', '<', time());
        if ($this->test_mode)
        {
            debug_add('TEST mode, adding constraints');
            $qb->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
        }
        else
        {
            debug_add('REAL mode, adding constraints');
            //Fail safe way, exclude those we know we do not want, in case some wanted members have incorrect type...
            // FIXME: use NOT IN
            $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_TESTER);
            $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_UNSUBSCRIBED);
            $qb->add_constraint('orgOpenpsaObtype', '<>', ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_BOUNCED);
        }
        $qb->add_order('person.lastname', 'ASC');
        $qb->add_order('person.firstname', 'ASC');
        $qb->add_order('person.username', 'ASC');
        $qb->add_order('person.id', 'ASC');
        debug_pop();
        return;
    }

    /**
     * Adds the common constraints and then returns "fast" (not ACL-checked) count of members matching any other
     * constraints the QB object passed has
     */
    function _qb_count_members($qb)
    {
        $this->_qb_common_constaints($qb);
        return $qb->count_unchecked();
        //return $qb->count();
    }

    /**
     * Returns the count of matching members and message receipts
     */
    function send_email_status()
    {
        $qb_mem = new midgard_query_builder('org_openpsa_campaign_member');
        $qb_mem->add_constraint('person.email', 'LIKE', '%@%');
        $this->_qb_common_constaints($qb_mem);
        $valid_members = $qb_mem->count();

        $qb_receipts = new midgard_query_builder('org_openpsa_campaign_message_receipt');
        $qb_receipts->add_constraint('message', '=', $this->id);
        $qb_receipts->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_MESSAGERECEIPT_SENT);
        $send_receipts = $qb_receipts->count();

        return array($valid_members, $send_receipts);
    }

    /**
     * Creates a random token string that can be used to track a single
     * email delivery. The returned token string will only contain
     * lowercase alphanumeric characters and will start with a lowercase
     * letter to avoid problems with special processing being triggered
     * by special characters in the token string.
     *
     * @return random token string
     */
    function _create_email_token()
    {
        //Testers need dummy token
        if ($this->test_mode)
        {
            return 'dummy';
        }
        //Use mt_rand if possible (faster, more random)
        if (function_exists('mt_rand'))
        {
            $rand = 'mt_rand';
        }
        else
        {
            $rand = 'rand';
        }
        $tokenchars = 'abcdefghijklmnopqrstuvwxyz0123456789';
        $token = $tokenchars[$rand(0, strlen($tokenchars) - 11)];
        for ($i = 1; $i < $this->token_size; $i++)
        {
            $token .= $tokenchars[$rand(0, strlen($tokenchars) - 1)];
        }
        //If token is not free or (very, very unlikely) matches our dummy token, recurse.
        if (   !org_openpsa_directmarketing_campaign_message_receipt_dba::token_is_free($token)
            || $token === 'dummy')
        {
            return $this->_create_email_token();
        }
        return $token;
    }

    /**
     * Inserts a link detector to the given HTML source. All outgoing
     * HTTP links in the source HTML are replaced with the given
     * link detector address so that the token "URL" is replaced with
     * encoded form of the original link. It is expected that the link detector
     * address points to a script that records the passed link and
     * forwards the client to the real link target.
     *
     * @param string $html the HTML source
     * @param string $address the link detector address
     * @return HTML source with the link detector
     */
    function _insert_link_detector($html, $address)
    {
        $address = addslashes($address);
        return preg_replace_callback(
            '/href="(http:\/\/.*?)"/i',
            create_function(
                '$match',
                'return "href=\\"" . str_replace("URL", rawurlencode($match[1]), "' . $address . '") . "\\"";'
            ),
            $html);
    }

    /**
     * Check is given member has denied contacts of given type
     *
     * @param object $member reference to campaign_member object related to the person
     * @param string $type type of contact, for example 'email' or 'sms'
     * @return boolean true if denied, false if allowed
     */
    function _check_member_deny(&$member, $type)
    {
        $person =& org_openpsa_contacts_person_dba::get_cached($member->person);
        if (!$this->_sanity_check_person($person, $member))
        {
            // Person object does not pass sanity checks, implicit deny...
            return true;
        }
        if (   $person->get_parameter('org.openpsa.directmarketing', "send_all_denied")
            || $person->get_parameter('org.openpsa.directmarketing', "send_{$type}_denied"))
        {
            debug_push_class(__CLASS__, __FUNCTION__);
            debug_add("Sending {$type} messages to person {$person->rname} is denied, unsubscribing member (member #{$member->id})");
            $member->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_UNSUBSCRIBED;
            $member->update();
            debug_pop();
            return true;
        }
        return false;
    }

    /**
     * Check that given person object is sane
     *
     * @param object $person reference to org_openpsa_contacts_person object
     * @param object $member reference to campaign_member object related to the person
     * @return boolean indicating sanity
     */
    function _sanity_check_person(&$person, &$member)
    {
        if (   !$person
            || !isset($person->guid)
            || empty($person->guid))
        {
            debug_push_class();
            debug_add("Person #{$member->person} deleted or missing, removing member (member #{$member->id})");
            $member->orgOpenpsaObtype = ORG_OPENPSA_OBTYPE_CAMPAIGN_MEMBER_UNSUBSCRIBED;
            $member->update();
            debug_pop();
            return false;
        }
        return true;
    }

    function _send_email_member($member, &$subject, &$content, &$from, &$data_array)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if ($this->_check_member_deny($member, 'email'))
        {
            debug_pop();
            return;
        }
        if (!isset($GLOBALS['org_openpsa_directmarketing_campaign_message_send_i']))
        {
            $GLOBALS['org_openpsa_directmarketing_campaign_message_send_i'] = 0;
        }
        $GLOBALS['org_openpsa_directmarketing_campaign_message_send_i']++;
        $token = $this->_create_email_token();
        $person =& org_openpsa_contacts_person_dba::get_cached($member->person);
        debug_add("Called for member #{$member->id}, (person #{$person->id}: {$person->rname})");
        $mail = new org_openpsa_mail();
        $mail->to = $person->email;
        $subject = $member->personalize_message($subject, ORG_OPENPSA_MESSAGETYPE_EMAIL_TEXT, $person);
        if ($this->test_mode)
        {
            $mail->subject = "[TEST] {$subject}";
        }
        else
        {
            $mail->subject = $subject;
        }
        $mail->from = $from;
        if (isset($data_array['reply-to']))
        {
            $mail->headers['Reply-To'] = $data_array['reply-to'];
        }
        //Make sure we have some backend and parameters for it defined
        if (!isset($data_array['mail_send_backend']))
        {
            $data_array['mail_send_backend'] = 'try_default';
        }
        if (!isset($data_array['mail_send_backend_params']))
        {
            $data_array['mail_send_backend_params'] = array();
        }
        //Check for bounce detector usage
        if (   array_key_exists('bounce_detector_address', $data_array)
            && !empty($data_array['bounce_detector_address']))
        {
            $bounce_address = str_replace('TOKEN', $token, $data_array['bounce_detector_address']);
            $mail->headers['Return-Path'] = $bounce_address;
            //Force bouncer as backend if default specified
            if (   !array_key_exists('mail_send_backend', $data_array)
                || empty($data_array['mail_send_backend'])
                || $data_array['mail_send_backend'] == 'try_default')
            {
                $data_array['mail_send_backend'] = 'bouncer';
            }
        }
        //Set some List-xxx headers to avoid auto-replies and in general to be a good netizen
        $mail->headers['List-Id'] = "<{$this->guid}@{$_SERVER['SERVER_NAME']}>";
        $mail->headers['List-Unsubscribe'] =  '<' . $member->get_unsubscribe_url(false, $person) . '>';

        debug_add('mail->from: ' . $mail->from . ', mail->to: ' . $mail->to . ', mail->subject: ' . $mail->subject);
        switch($this->orgOpenpsaObtype)
        {
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_TEXT:
                $mail->body = $member->personalize_message($content, $this->orgOpenpsaObtype, $person);
            break;
            case ORG_OPENPSA_MESSAGETYPE_EMAIL_HTML:
                $mail->html_body = $member->personalize_message($content, $this->orgOpenpsaObtype, $person);
                if (   array_key_exists('htmlemail_force_text_body', $data_array)
                    && strlen($data_array['htmlemail_force_text_body']) > 0)
                {
                    $mail->body = $member->personalize_message($data_array['htmlemail_force_text_body'], $this->orgOpenpsaObtype, $person);
                }
                // Allow sensing only HTML body if requested
                if (   array_key_exists('htmlemail_onlyhtml', $data_array)
                    && !empty($data_array['htmlemail_onlyhtml']))
                {
                    $mail->allow_only_html = true;
                }
                // Skip embedding if requested
                if (   array_key_exists('htmlemail_donotembed', $data_array)
                    && !empty($data_array['htmlemail_donotembed']))
                {
                    // Skip embedding, do something else ??
                }
                else
                {
                    //The mail class uses a caching scheme to avoid fetching embedded objects again.
                    list ($mail->html_body, $mail->embeds) = $mail->html_get_embeds($this, $mail->html_body, $mail->embeds);
                }

                //Handle link detection
                if (   array_key_exists('link_detector_address', $data_array)
                    && !empty($data_array['link_detector_address']))
                {
                    $link_address = str_replace('TOKEN', $token, $data_array['link_detector_address']);
                    $mail->html_body = $this->_insert_link_detector($mail->html_body, $link_address);
                }
            break;
            default:
                debug_add('Invalid message type, aborting', MIDCOM_LOG_ERROR);
                debug_pop();
                return array(false, $mail);
            break;
        }

        //Go trough DM2 types array for attachments
        reset($data_array['dm_types']);
        foreach ($data_array['dm_types'] as $field => $typedata)
        {
            if (   !isset($typedata->attachments_info)
                || empty($typedata->attachments_info))
            {
                continue;
            }

            // If you don't want to add the image as an attachment to the field, add show_attachment customdata-definition to
            // schema and set it to false
            if(    isset($typedata->storage->_schema->fields[$field])
                && is_array($typedata->storage->_schema->fields[$field])
                && isset($typedata->storage->_schema->fields[$field]['customdata'])
                && is_array($typedata->storage->_schema->fields[$field]['customdata'])
                && isset($typedata->storage->_schema->fields[$field]['customdata']['show_attachment'])
                && $typedata->storage->_schema->fields[$field]['customdata']['show_attachment'] === false
              )
            {
                continue;
            }

            foreach ($typedata->attachments_info as $key => $attachment_data)
            {
                $att = array();
                $att['name'] = $attachment_data['filename'];
                $att['mimetype'] = $attachment_data['mimetype'];
                $fp = $attachment_data['object']->open('r');
                if (!$fp)
                {
                    //Failed to open attachment for reading, skip the file
                    continue;
                }
                $att['content'] = '';
                /* PONDER: Should we cache the content somehow so that we only need to read it once per request ??
                           We would save some file opens at the expense of keeping the contents in memory (potentially very expensive) */
                while (!feof($fp))
                {
                    $att['content'] .=  fread($fp, 4096);
                }
                fclose($fp);
                debug_add("adding attachment '{$att['name']}' from field '{$field}' to attachments array");
                $mail->attachments[] = $att;
                unset($att);
            }
        }

        $status = $mail->send($data_array['mail_send_backend'], $data_array['mail_send_backend_params']);
        if ($status)
        {
            debug_add('Mail sent to: ' . $mail->to);
            if (!$this->test_mode)
            {
                $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
                $member->create_receipt($this->id, ORG_OPENPSA_MESSAGERECEIPT_SENT, $token);
                $_MIDCOM->auth->drop_sudo();
            }
        }
        else
        {
            $message = sprintf('FAILED to send mail to: %s, reason: %s', $mail->to, $mail->get_error_message());
            debug_add($message, MIDCOM_LOG_WARN);
            if (!$this->test_mode)
            {
                $params = array
                (
                    array
                    (
                        'domain' => 'org.openpsa.directmarketing',
                        'name' => 'send_error_message',
                        'value' => $message,
                    ),
                );
                $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
                $member->create_receipt($this->id, ORG_OPENPSA_MESSAGERECEIPT_FAILURE, $token, $params);
                $_MIDCOM->auth->drop_sudo();
            }
            if ($this->send_output)
            {
                $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.directmarketing', 'org.openpsa.directmarketing'), sprintf($_MIDCOM->i18n->get_string('FAILED to send mail to: %s, reason: %s', 'org.openpsa.directmarketing'), $mail->to, $mail->get_error_message()), 'error');
            }
        }
        unset($mail);
        debug_pop();
        return $status;
    }


    function _qb_single_chunk($callback_name, $level = 0)
    {
        $callback_method = "_callback_get_qb_{$callback_name}";
        if (!method_exists($this, $callback_method))
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "method '{$callback_method}' does not exist");
        }
        $qb = $this->$callback_method();
        debug_push_class(__CLASS__, __FUNCTION__);
        $this->_qb_common_constaints($qb);
        $this->_qb_chunk_limits($qb);

        $results = $qb->execute_unchecked();
        debug_add('Got ' . count($results) . ' initial results');
        if (   !is_array($results)
            || count($results)==0)
        {
            debug_add('Got failure or empty resultset, aborting');
            debug_pop();
            return false;
        }

        if ($this->test_mode)
        {
            debug_add('TEST mode, no receipt filtering will be done');
            debug_pop();
            return $results;
        }

        $results = $this->_qb_filter_results($results);

        debug_add('Have ' . count($results) . ' results left after filtering');
        debug_add("Recursion level is {$level}, limit is {$this->_chunk_max_recurse}");
        /* Make sure we still have results left, if not just recurse...
           (basically this is to avoid returning an empty array when everything is otherwise ok) */
        if (   count($results) == 0
            && ($level < $this->_chunk_max_recurse))
        {
            debug_add('All our results got filtered, recursing for another round');
            //Trivial rate limiting.
            sleep(1);
            $this->_chunk_num++;
            return $this->_qb_single_chunk($callback_name, $level+1);
        }

        reset($results);
        debug_pop();
        return $results;
    }

    function send_email_bg(&$batch, &$subject, &$content, &$from, &$data_array)
    {
        //TODO: Figure out how to recognize errors and pass the info on
        debug_push_class(__CLASS__, __FUNCTION__);
        $this->send_output = false;
        @ini_set('max_execution_time', 0);
        if (!$from)
        {
            $from = 'noreplyaddress@openpsa.org';
        }
        if (!$subject)
        {
            $subject = '[no subject]';
        }

        $this->_chunk_num = $batch-1;

        $results = $this->_qb_single_chunk('send_email');
        //The method above might have incremented the counter for internal reasons
        $batch = $this->_chunk_num+1;
        if ($results === false)
        {
            $ret = array();
            $ret[] = true; //All should be ok
            $ret[] = false; //Do not register another batch
            debug_pop();
            return $ret;
        }

        foreach ($results as $member)
        {
            $this->_send_email_member($member, $subject, $content, $from, $data_array);
        }

        $ret = array();
        $ret[] = true; //All should be ok
        $ret[] = true; //Register next batch to AT
        debug_pop();
        return $ret;
    }

    function &_callback_get_qb_send_email()
    {
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('person.email', 'LIKE', '%@%');
        return $qb;
    }

    /**
     * Sends an email to all members that have email address set
     */
    function send_email(&$subject, &$content, &$from, $data_array=array())
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        //TODO: Some sort of locking scheme
        @ini_set('max_execution_time', 0);
        if (!$from)
        {
            $from = 'noreplyaddress@openpsa.org';
        }
        if (!$subject)
        {
            $subject = '[no subject]';
        }

        //TODO: Rethink the styles, now we filter those who already had message sent to themm thus the total member count becomes meaningless
        if ($this->send_output)
        {
            midcom_show_style('send-start');
            flush();
            ob_flush(); //I Hope midcom doesn't wish to do any specific post-processing here...
        }

        while ($results = $this->_qb_send_loop('send_email'))
        {
            foreach ($results as $member)
            {
                $this->_send_email_member($member, $subject, $content, $from, $data_array);
            }
        }

        if ($this->send_output)
        {
            midcom_show_style('send-finish');
            flush();
            ob_flush(); //I Hope midcom doesn't wish to do any specific post-processing here...
        }

        debug_pop();
        return true;
    }

    /**
     * Function tries to normalize the phone number to a single string of numbers
     */
    function _normalize_phone($phone)
    {
        //Quite simplistic approach but works correctly on +358-(0)40-5401446
        return preg_replace("/(\([0-9]+\))|([^0-9+]+?)/", '', $phone);
    }

    /**
     * Returns the count of matching members and message receipts
     */
    function send_sms_status()
    {
        $qb_mem = new midgard_query_builder('org_openpsa_campaign_member_dba');
        $qb_mem->add_constraint('person.handphone', '<>', '');
        $this->_qb_common_constaints($qb_mem);
        $valid_members = $qb_mem->count();

        $qb_receipts = new midgard_query_builder('org_openpsa_campaign_message_receipt_dba');
        $qb_receipts->add_constraint('message', '=', $this->id);
        $qb_receipts->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_MESSAGERECEIPT_SENT);
        $send_receipts = $qb_receipts->count();

        return array($valid_members, $send_receipts);
    }

    function _send_sms_member(&$smsbroker, $member, &$content, &$from, &$data_array)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if ($this->_check_member_deny($member, 'sms'))
        {
            debug_pop();
            return;
        }
        if (!isset($GLOBALS['org_openpsa_directmarketing_campaign_message_send_i']))
        {
            $GLOBALS['org_openpsa_directmarketing_campaign_message_send_i'] = 0;
        }
        $GLOBALS['org_openpsa_directmarketing_campaign_message_send_i']++;
        $person =& org_openpsa_contacts_person_dba::get_cached($member->person);
        $person->handphone = $this->_normalize_phone($person->handphone);
        $content_p = $member->personalize_message($content, $this->orgOpenpsaObtype, $person);

        //TODO: Add sender support
        $status = $smsbroker->send_sms($person->handphone, $content_p, $from);

        if ($status)
        {
            debug_add('SMS sent to: ' . $person->handphone);
            if (!$this->test_mode)
            {
                $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
                $member->create_receipt($this->id, ORG_OPENPSA_MESSAGERECEIPT_SENT);
                $_MIDCOM->auth->drop_sudo();
            }
        }
        else
        {
            $message = sprintf('FAILED to send SMS to: %s, reason: %s', $person->handphone, $smsbroker->errstr);
            debug_add($message, MIDCOM_LOG_WARN);
            if (!$this->test_mode)
            {
                $params = array
                (
                    array
                    (
                        'domain' => 'org.openpsa.directmarketing',
                        'name' => 'send_error_message',
                        'value' => $message,
                    ),
                );
                $_MIDCOM->auth->request_sudo('org.openpsa.directmarketing');
                $member->create_receipt($this->id, ORG_OPENPSA_MESSAGERECEIPT_FAILURE, $token, $params);
                $_MIDCOM->auth->drop_sudo();
            }
            if ($this->send_output)
            {
                $_MIDCOM->uimessages->add($_MIDCOM->i18n->get_string('org.openpsa.directmarketing', 'org.openpsa.directmarketing'), sprintf($_MIDCOM->i18n->get_string('FAILED to send SMS to: %s, reason: %s', 'org.openpsa.directmarketing'), $person->handphone, $smsbroker->errstr), 'error');
            }
        }
        debug_pop();
        return $status;
    }

    function send_sms_bg(&$batch, &$content, &$from, &$data_array)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        //TODO: Some sort of locking scheme
        @ini_set('max_execution_time', 0);
        //Initializing SMS broker
        $smsbroker = call_user_func(array(str_replace('.', '_', $this->sms_lib), 'factory'), $this->sms_lib_api);
        if (!is_object($smsbroker))
        {
            debug_add(str_replace('.', '_', $this->sms_lib) . "::factory({$this->sms_lib_api}) returned: {$smsbroker}", MIDCOM_LOG_ERROR);
            $ret = array();
            $ret[] = false; //Error initializing broker
            $ret[] = false; //Do not register another batch
            debug_pop();
            return $ret;
        }
        $smsbroker->location = $this->sms_lib_location;
        $smsbroker->client_id = $this->sms_lib_client_id;
        $smsbroker->user = $this->sms_lib_user;
        $smsbroker->password = $this->sms_lib_password;

        $this->_chunk_num = $batch-1;

        $results = $this->_qb_single_chunk('send_sms');

        if (!$this->_check_sms_balance($smsbroker, $results))
        {
            //PONDER: Echo to output as well so cron can log it ?
            debug_add("Not enough credits to send to {$results_count} recipients, aborting", MIDCOM_LOG_ERROR);
            $ret = array();
            $ret[] = false; //Not enough credits
            $ret[] = false; //Do not register another batch
            debug_pop();
            return $ret;
        }

        //The method above might have incremented the counter for internal reasons
        $batch = $this->_chunk_num+1;
        if (!$results)
        {
            $ret = array();
            $ret[] = true; //All should be ok
            $ret[] = false; //Do not register another batch
            debug_pop();
            return $ret;
        }

        foreach ($results as $member)
        {
            $status = $this->_send_sms_member($smsbroker, $member, $content, $from, $data_array);
        }

        $ret = array();
        $ret[] = true; //All should be ok
        $ret[] = true; //Register next batch to AT
        debug_pop();
        return $ret;
    }

    function _check_sms_balance(&$smsbroker, $results)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        if (!method_exists('get_balance', $smsbroker))
        {
            debug_add('Broker does not have mechanism for checking balance, supposing infinite', MIDCOM_LOG_INFO);
            debug_pop();
            return true;
        }
        debug_add('Checking SMS broker balance');
        $balance = $smsbroker->get_balance();
        debug_add("Got balance '{$balance}'");
        if ($balance === false)
        {
            debug_add('Error while checking SMS broker balance, returning false to be safe', MIDCOM_LOG_ERROR);
            debug_pop();
            return false;
        }
        $results_count = count($results);
        //Non-numeric balance is supposed to be infinite
        if (   is_numeric($balance)
            && $balance < $results_count)
        {
            debug_add("Balance ({$balance}) is less than number of recipients ({$results_count})", MIDCOM_LOG_INFO);
            debug_pop();
            return false;
        }
        debug_pop();
        return true;
    }

    function &_callback_get_qb_send_sms()
    {
        $qb = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb->add_constraint('person.handphone', '<>', '');
        return $qb;
    }

    /**
     * Sends an SMS to all members that have handphone number set
     */
    function send_sms(&$content, &$from, &$data_array)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        //TODO: Some sort of locking scheme
        @ini_set('max_execution_time', 0);
        //Initializing SMS broker
        $smsbroker = call_user_func(array(str_replace('.', '_', $this->sms_lib), 'factory'), $this->sms_lib_api);
        if (!is_object($smsbroker))
        {
            debug_add(str_replace('.', '_', $this->sms_lib) . "::factory({$this->sms_lib_api}) returned: {$smsbroker}", MIDCOM_LOG_ERROR);
            return false;
        }
        $smsbroker->location = $this->sms_lib_location;
        $smsbroker->client_id = $this->sms_lib_client_id;
        $smsbroker->user = $this->sms_lib_user;
        $smsbroker->password = $this->sms_lib_password;

        //TODO: Rethink the styles, now we filter those who already had message sent to themm thus the total member count becomes meaningless
        if ($this->send_output)
        {
            midcom_show_style('send-start');
            flush();
            ob_flush(); //I Hope midcom doesn't wish to do any specific post-processing here...
        }

        while ($results = $this->_qb_send_loop('send_sms'))
        {
            //Check that we have enough credits before starting
            //PONDER: Should this be moved outside this loop and to use the (not very reliable) total member count ?
            if (!$this->_check_sms_balance($smsbroker, $results))
            {
                debug_add("Not enough credits to send to {$results_count} recipients, aborting", MIDCOM_LOG_ERROR);
                if ($this->send_output)
                {
                    //TODO: Throw some error to user level as well.
                }
                debug_pop();
                return false;
            }

            foreach ($results as $member)
            {
                $status = $this->_send_sms_member($smsbroker, $member, $content, $from, $data_array);
            }
        }
        if ($this->send_output)
        {
            midcom_show_style('send-finish');
            flush();
            ob_flush(); //I Hope midcom doesn't wish to do any specific post-processing here...
        }

        debug_pop();
        return true;
    }



    /**
     * Returns the count of matching members and message receipts
     */
    function send_mms_status()
    {
        $qb_mem = new midgard_query_builder('org_openpsa_campaign_member');
        $qb_mem->add_constraint('person.handphone', '<>', '');
        $this->_qb_common_constaints($qb_mem);
        $valid_members = $qb_mem->count();

        $qb_receipts = new midgard_query_builder('org_openpsa_campaign_message_receipt');
        $qb_receipts->add_constraint('message', '=', $this->id);
        $qb_receipts->add_constraint('orgOpenpsaObtype', '=', ORG_OPENPSA_MESSAGERECEIPT_SENT);
        $send_receipts = $qb_receipts->count();

        return array($valid_members, $send_receipts);
    }

    /**
     * Sends an MMS to all members that have handphone number set
     */
    function send_mms(&$content, &$from, &$data_array)
    {
        debug_push_class(__CLASS__, __FUNCTION__);
        debug_add('Not implemented yet', MIDCOM_LOG_ERROR);
        debug_pop();
        return false;
    }
}

?>