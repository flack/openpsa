<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Discussion forum index
 *
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_report extends midcom_baseclasses_components_handler
{
    /**
     * The message which has been created
     *
     * @var org_openpsa_directmarketing_message
     */
    private $_message = null;

    private $_datamanager = false;

    /**
     * Internal helper, loads the datamanager for the current message. Any error triggers a 500.
     */
    private function _load_datamanager()
    {
        $schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_message'));
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);
    }

    /**
     * Builds the message report array
     */
    private function _analyze_message_report(&$data)
    {
        midcom::get('auth')->require_valid_user();

        $this->_request_data['report'] = array();
        $qb_receipts = org_openpsa_directmarketing_campaign_messagereceipt_dba::new_query_builder();
        $qb_receipts->add_constraint('message', '=', $this->_request_data['message']->id);
        $qb_receipts->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_messagereceipt_dba::SENT);
        $receipts = $qb_receipts->execute_unchecked();
        $this->_request_data['report']['receipt_data'] = array();
        $receipt_data =& $this->_request_data['report']['receipt_data'];
        $receipt_data['first_send'] = false;
        $receipt_data['last_send'] = false;
        $receipt_data['sent'] = count($receipts);
        $receipt_data['bounced'] = 0;
        foreach ($receipts as $receipt)
        {
            if (   $receipt_data['first_send'] === false
                || $receipt->timestamp < $receipt_data['first_send'])
            {
                $receipt_data['first_send'] = $receipt->timestamp;
            }
            if (   $receipt_data['last_send'] === false
                || $receipt->timestamp > $receipt_data['last_send'])
            {
                $receipt_data['last_send'] = $receipt->timestamp;
            }
            if ($receipt->bounced)
            {
                $receipt_data['bounced']++;
            }
        }

        $this->_get_campaign_data($receipt_data['first_send']);

        $segmentation_param = false;
        if (   isset($data['message_array']['report_segmentation'])
            && !empty($data['message_array']['report_segmentation']))
        {
            $segmentation_param = $data['message_array']['report_segmentation'];
        }
        $this->_get_link_data($segmentation_param);
    }

    private function _get_campaign_data($first_send)
    {
        $this->_request_data['report']['campaign_data'] = array();
        $campaign_data =& $this->_request_data['report']['campaign_data'];
        $campaign_data['unsubscribed'] = 0;
        $qb_unsub = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb_unsub->add_constraint('campaign', '=', $this->_request_data['message']->campaign);
        $qb_unsub->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);
        $qb_unsub->add_constraint('metadata.revised', '>', date('Y-m-d H:i:s', $first_send));
        $campaign_data['next_message'] = false;
        // Find "next message" and if present use its sendStarted as constraint for this query
        $qb_messages = org_openpsa_directmarketing_campaign_message_dba::new_query_builder();
        $qb_messages->add_constraint('campaign', '=',  $this->_request_data['message']->campaign);
        $qb_messages->add_constraint('id', '<>',  $this->_request_data['message']->id);
        $qb_messages->add_constraint('sendStarted', '>', $first_send);
        $qb_messages->add_order('sendStarted', 'DESC');
        $qb_messages->set_limit(1);
        $messages = $qb_messages->execute_unchecked();
        if (   is_array($messages)
            && isset($messages[0]))
        {
            $campaign_data['next_message'] = $messages[0];
            $qb_unsub->add_constraint('metadata.revised', '<', date('Y-m-d H:i:s', $messages[0]->sendStarted));
        }
        $campaign_data['unsubscribed'] = $qb_unsub->count_unchecked();
    }

    private function _get_link_data($segmentation_param)
    {
        $this->_request_data['report']['link_data'] = array();
        $link_data =& $this->_request_data['report']['link_data'];

        $link_data['counts'] = array();
        $link_data['percentages'] = array('of_links' => array(), 'of_recipients' => array());
        $link_data['rules'] = array();
        $link_data['tokens'] = array();
        if ($segmentation_param)
        {
            $link_data['segments'] = array();
        }
        $segment_prototype = array();
        $segment_prototype['counts'] = array();
        $segment_prototype['percentages'] = array('of_links' => array(), 'of_recipients' => array());
        $segment_prototype['rules'] = array();
        $segment_prototype['tokens'] = array();

        $qb_links = org_openpsa_directmarketing_link_log_dba::new_query_builder();
        $qb_links->add_constraint('message', '=', $this->_request_data['message']->id);
        $qb_links->add_constraint('target', 'NOT LIKE', '%unsubscribe%');
        $links = $qb_links->execute_unchecked();

        $link_data['total'] = count($links);

        foreach ($links as $link)
        {
            $segment = '';
            $segment_notfound = false;
            if (   $segmentation_param
                && !empty($link->person))
            {
                try
                {
                    $person = org_openpsa_contacts_person_dba::get_cached($link->person);
                    $segment = $person->parameter('org.openpsa.directmarketing.segments', $segmentation_param);
                }
                catch (midcom_error $e){}
                if (empty($segment))
                {
                    $segment = $this->_l10n->get('no segment');
                    $segment_notfound = true;
                }
                if (!isset($link_data['segments'][$segment]))
                {
                    $link_data['segments'][$segment] = $segment_prototype;
                }
                $segment_data =& $link_data['segments'][$segment];
            }
            else
            {
                $segment_data = $segment_prototype;
            }

            $this->_increment_totals($link_data, $link);
            $this->_increment_totals($segment_data, $link);
            $this->_calculate_percentages($link_data, $link);
            $this->_calculate_percentages($segment_data, $link);

            if (!isset($link_data['rules'][$link->target]))
            {
                $link_data['rules'][$link->target] = array
                (
                    'comment' => sprintf($this->_l10n->get('all persons who have clicked on link "%s" in message #%d and have not unsubscribed from campaign #%d'), $link->target, $link->message, $this->_request_data['message']->campaign),
                    'type' => 'AND',
                    'classes' => array
                    (
                        array
                        (
                            'comment' => $this->_l10n->get('link and message limits'),
                            'type' => 'AND',
                            'class' => 'org_openpsa_directmarketing_link_log_dba',
                            'rules' => array
                            (
                                array
                                (
                                    'property' => 'target',
                                    'match' => '=',
                                    'value' => $link->target,
                                ),
                                // PONDER: do we want to limit to this message only ??
                                array
                                (
                                    'property' => 'message',
                                    'match' => '=',
                                    'value' => $link->message,
                                ),
                            ),
                        ),
                        // Add rule that prevents unsubscribed persons from ending up to the smart-campaign ??
                        array
                        (
                            'comment' => $this->_l10n->get('not-unsubscribed -limits'),
                            'type' => 'AND',
                            'class' => 'org_openpsa_directmarketing_campaign_member_dba',
                            'rules' => array
                            (
                                array
                                (
                                    'property' => 'orgOpenpsaObtype',
                                    'match' => '<>',
                                    'value' => org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED,
                                ),
                                array
                                (
                                    'property' => 'campaign',
                                    'match' => '=',
                                    'value' => $this->_request_data['message']->campaign,
                                ),
                            ),
                        ),
                    ),
                );
            }
            if (!isset($segment_data['rules'][$link->target]))
            {
                $segment_data['rules'][$link->target] = $link_data['rules'][$link->target];

                if (!$segment_notfound)
                {
                    $segmentrule = array
                    (
                        'comment' => $this->_l10n->get('segment limits'),
                        'type' => 'AND',
                        'class' => 'org_openpsa_contacts_person_dba',
                        'rules' => array
                        (
                            array
                            (
                                'property' => 'parameter.domain',
                                'match' => '=',
                                'value' => 'org.openpsa.directmarketing.segments',
                            ),
                            array
                            (
                                'property' => 'parameter.name',
                                'match' => '=',
                                'value' => $segmentation_param,
                            ),
                            array
                            (
                                'property' => 'parameter.value',
                                'match' => '=',
                                'value' => $segment,
                            ),
                        ),
                    );
                    // On a second thought, we cannot query for empty parameter values...
                    $segment_data['rules'][$link->target]['comment'] = sprintf($this->_l10n->get('all persons in market segment "%s" who have clicked on link "%s" in message #%d and have not unsubscribed from campaign #%d'), $segment, $link->target, $link->message, $this->_request_data['message']->campaign);
                    $segment_data['rules'][$link->target]['classes'][] = $segment;
                }
            }
        }
        arsort($link_data['counts']);
        arsort($link_data['percentages']['of_links']);
        arsort($link_data['percentages']['of_recipients']);

        if ($segmentation_param)
        {
            ksort($link_data['segments']);
            foreach ($link_data['segments'] as $segment => $dummy)
            {
                $segment_data =& $link_data['segments'][$segment];
                arsort($segment_data['counts']);
                arsort($segment_data['percentages']['of_links']);
                arsort($segment_data['percentages']['of_recipients']);
            }
        }
    }

    private function _calculate_percentages(&$array, &$link)
    {
        $this->_initialize_field($array['percentages']['of_links'], $link);
        $this->_initialize_field($array['percentages']['of_recipients'], $link);

        $link_data =& $this->_request_data['report']['link_data'];
        $array['percentages']['of_links'][$link->target]['total'] = ($array['counts'][$link->target]['total']/$link_data['total'])*100;
        $array['percentages']['of_links'][$link->target][$link->token] = ($array['counts'][$link->target][$link->token]/$link_data['total'])*100;

        $receipt_data =& $this->_request_data['report']['receipt_data'];
        $array['percentages']['of_recipients'][$link->target]['total'] = ((count($array['counts'][$link->target])-1)/($receipt_data['sent']-$receipt_data['bounced']))*100;
        $array['percentages']['of_recipients'][$link->target][$link->token] = ($array['counts'][$link->target][$link->token]/($receipt_data['sent']-$receipt_data['bounced']))*100;

        if(   (!isset($array['percentages']['of_recipients']['total']))
           || $array['percentages']['of_recipients'][$link->target]['total'] > $array['percentages']['of_recipients']['total'])
        {
            $array['percentages']['of_recipients']['total'] = $array['percentages']['of_recipients'][$link->target]['total'];
        }
    }

    private function _initialize_field(&$array, &$link)
    {
        if (!isset($array[$link->target]))
        {
            $array[$link->target] = array();
            $array[$link->target]['total'] = 0;
        }
        if (!isset($array[$link->target][$link->token]))
        {
            $array[$link->target][$link->token] = 0;
        }
    }

    private function _increment_totals(&$array, &$link)
    {
        if (!isset($array['tokens'][$link->token]))
        {
            $array['tokens'][$link->token] = 0;
        }

        $this->_initialize_field($array['counts'], $link);

        $array['counts'][$link->target]['total']++;
        $array['counts'][$link->target][$link->token]++;
        $array['tokens'][$link->token]++;
    }


    private function _create_campaign_from_link()
    {
        $campaign = new org_openpsa_directmarketing_campaign_dba();
        $campaign->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_dba::TYPE_SMART;
        $eval = '$tmp_array = ' . $_POST['org_openpsa_directmarketing_campaign_rule_' . $_POST['org_openpsa_directmarketing_campaign_userule']] . ';';
        $eval_ret = @eval($eval);
        if ($eval_ret === false)
        {
            return false;
        }
        $campaign->rules = $tmp_array;
        $campaign->description = $tmp_array['comment'];
        $campaign->title = sprintf($this->_l10n->get('from link "%s"'), $_POST['org_openpsa_directmarketing_campaign_label_' . $_POST['org_openpsa_directmarketing_campaign_userule']]);
        $campaign->testers[midcom_connection::get_user()] = true;
        $campaign->node = $this->_topic->id;
        if (!$campaign->create())
        {
            return false;
        }
        $campaign->schedule_update_smart_campaign_members();
        midcom::get()->relocate(midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX)
            . "campaign/edit/{$campaign->guid}/");
        // This will exit()
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_report($handler_id, array $args, array &$data)
    {
        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $data['message'] =& $this->_message;

        $this->_load_datamanager();
        $this->_datamanager->autoset_storage($data['message']);
        $data['message_array'] = $this->_datamanager->get_content_raw();

        $this->_campaign = $this->_master->load_campaign($this->_message->campaign);
        $data['campaign'] =& $this->_campaign;
        $this->set_active_leaf('campaign_' . $data['campaign']->id);

        if (   isset($_POST['org_openpsa_directmarketing_campaign_userule'])
            && isset($_POST['org_openpsa_directmarketing_campaign_rule_' . $_POST['org_openpsa_directmarketing_campaign_userule']])
            && !empty($_POST['org_openpsa_directmarketing_campaign_rule_' . $_POST['org_openpsa_directmarketing_campaign_userule']]))
        {
            $this->_create_campaign_from_link();
        }

        $this->add_breadcrumb("campaign/{$this->_campaign->guid}/", $this->_campaign->title);
        $this->add_breadcrumb("message/{$this->_message->guid}/", $this->_message->title);
        $this->add_breadcrumb("message/report/{$this->_message->guid}/", $this->_l10n->get('message report'));

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "message/{$this->_request_data['message']->guid}/",
                MIDCOM_TOOLBAR_LABEL => $this->_request_data['l10n_midcom']->get("back"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
            )
        );
        if (   !empty(midcom::get('auth')->user)
            && !empty(midcom::get('auth')->user->guid))
        {
            $preview_url = "message/compose/{$this->_message->guid}/" . midcom::get('auth')->user->guid .'/';
        }
        else
        {
            $preview_url = "message/compose/{$this->_message->guid}/";
        }
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => $preview_url,
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('preview message'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/view.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                MIDCOM_TOOLBAR_ENABLED => $this->_message->can_do('midgard:read'),
                MIDCOM_TOOLBAR_OPTIONS => array('target' => '_BLANK'),
            )
        );
        $this->_analyze_message_report($data);
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_report($handler_id, array &$data)
    {
        midcom_show_style('show-message-report');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_status($handler_id, array $args, array &$data)
    {
        $this->_request_data['message_obj'] = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $response = new midcom_response_xml;
        $response->result = $this->_request_data['message_obj']->send_status();
        if ($response->result == false)
        {
            $response->status = 'message->send_status returned false';
        }
        else
        {
            $response->members = $stat[0];
            $response->receipts = $stat[1];
        }
        return $response;
    }
}
?>