<?php
/**
 * @package org.openpsa.directmarketing
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package org.openpsa.directmarketing
 */
class org_openpsa_directmarketing_handler_message_report extends midcom_baseclasses_components_handler
{
    use org_openpsa_directmarketing_handler;

    /**
     * The message we're working on
     *
     * @var org_openpsa_directmarketing_campaign_message_dba
     */
    private $_message;

    /**
     * Builds the message report array
     */
    private function _analyze_message_report(array $data)
    {
        $this->_request_data['report'] = [
            'campaign_data' => [],
            'receipt_data' => []
        ];
        $qb_receipts = org_openpsa_directmarketing_campaign_messagereceipt_dba::new_query_builder();
        $qb_receipts->add_constraint('message', '=', $this->_message->id);
        $qb_receipts->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_messagereceipt_dba::SENT);
        $qb_receipts->add_order('metadata.created');
        $receipts = $qb_receipts->execute_unchecked();
        $receipt_data =& $this->_request_data['report']['receipt_data'];
        $receipt_data['first_send'] = $receipts[0]->metadata->created ?? 0;
        $receipt_data['last_send'] = end($receipts)->metadata->created ?? 0;
        $receipt_data['sent'] = count($receipts);
        $receipt_data['bounced'] = 0;
        foreach ($receipts as $receipt) {
            if ($receipt->bounced) {
                $receipt_data['bounced']++;
            }
        }

        $qb_failed = org_openpsa_directmarketing_campaign_messagereceipt_dba::new_query_builder();
        $qb_failed->add_constraint('message', '=', $this->_message->id);
        $qb_failed->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_messagereceipt_dba::FAILURE);
        $receipt_data['failed'] = $qb_failed->count();

        $this->_get_campaign_data($receipt_data['first_send']);

        $segmentation_param = false;
        if (!empty($data['message_array']['report_segmentation'])) {
            $segmentation_param = $data['message_array']['report_segmentation'];
        }
        $this->_get_link_data($segmentation_param);
    }

    private function _get_campaign_data(int $first_send)
    {
        $campaign_data =& $this->_request_data['report']['campaign_data'];
        $qb_unsub = org_openpsa_directmarketing_campaign_member_dba::new_query_builder();
        $qb_unsub->add_constraint('campaign', '=', $this->_message->campaign);
        $qb_unsub->add_constraint('orgOpenpsaObtype', '=', org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED);
        $qb_unsub->add_constraint('metadata.revised', '>', date('Y-m-d H:i:s', $first_send));
        $campaign_data['next_message'] = false;
        // Find "next message" and if present use its sendStarted as constraint for this query
        $qb_messages = org_openpsa_directmarketing_campaign_message_dba::new_query_builder();
        $qb_messages->add_constraint('campaign', '=', $this->_message->campaign);
        $qb_messages->add_constraint('id', '<>', $this->_message->id);
        $qb_messages->add_constraint('sendStarted', '>', $first_send);
        $qb_messages->add_order('sendStarted', 'DESC');
        $qb_messages->set_limit(1);
        $messages = $qb_messages->execute_unchecked();
        if (!empty($messages[0])) {
            $campaign_data['next_message'] = $messages[0];
            $qb_unsub->add_constraint('metadata.revised', '<', date('Y-m-d H:i:s', $messages[0]->sendStarted));
        }
        $campaign_data['unsubscribed'] = $qb_unsub->count_unchecked();
    }

    private function _get_link_data($segmentation_param)
    {
        $this->_request_data['report']['link_data'] = [];
        $link_data =& $this->_request_data['report']['link_data'];

        $link_data['counts'] = [];
        $link_data['percentages'] = ['of_links' => [], 'of_recipients' => []];
        $link_data['rules'] = [];
        $link_data['tokens'] = [];
        if ($segmentation_param) {
            $link_data['segments'] = [];
        }
        $segment_prototype = [
            'counts' => [],
            'percentages' => ['of_links' => [], 'of_recipients' => []],
            'rules' => [],
            'tokens' => []
        ];

        $qb_links = org_openpsa_directmarketing_link_log_dba::new_query_builder();
        $qb_links->add_constraint('message', '=', $this->_message->id);
        $qb_links->add_constraint('target', 'NOT LIKE', '%unsubscribe%');
        $links = $qb_links->execute_unchecked();

        $link_data['total'] = count($links);

        foreach ($links as $link) {
            $segment = '';
            $segment_notfound = false;
            if (   $segmentation_param
                && !empty($link->person)) {
                try {
                    $person = org_openpsa_contacts_person_dba::get_cached($link->person);
                    $segment = $person->get_parameter('org.openpsa.directmarketing.segments', $segmentation_param);
                } catch (midcom_error $e) {
                }
                if (empty($segment)) {
                    $segment = $this->_l10n->get('no segment');
                    $segment_notfound = true;
                }
                if (!isset($link_data['segments'][$segment])) {
                    $link_data['segments'][$segment] = $segment_prototype;
                }
                $segment_data =& $link_data['segments'][$segment];
            } else {
                $segment_data = $segment_prototype;
            }

            $this->_increment_totals($link_data, $link);
            $this->_increment_totals($segment_data, $link);
            $this->_calculate_percentages($link_data, $link);
            $this->_calculate_percentages($segment_data, $link);

            if (!isset($link_data['rules'][$link->target])) {
                $link_data['rules'][$link->target] = $this->_generate_link_rules($link);
            }
            if (!isset($segment_data['rules'][$link->target])) {
                $segment_data['rules'][$link->target] = $link_data['rules'][$link->target];

                if (!$segment_notfound) {
                    $segmentrule = [
                        'comment' => $this->_l10n->get('segment limits'),
                        'type' => 'AND',
                        'class' => org_openpsa_contacts_person_dba::class,
                        'rules' => [
                            [
                                'property' => 'parameter.domain',
                                'match' => '=',
                                'value' => 'org.openpsa.directmarketing.segments',
                            ],
                            [
                                'property' => 'parameter.name',
                                'match' => '=',
                                'value' => $segmentation_param,
                            ],
                            [
                                'property' => 'parameter.value',
                                'match' => '=',
                                'value' => $segment,
                            ],
                        ],
                    ];
                    // On a second thought, we cannot query for empty parameter values...
                    $segment_data['rules'][$link->target]['comment'] = sprintf($this->_l10n->get('all persons in market segment "%s" who have clicked on link "%s" in message #%d and have not unsubscribed from campaign #%d'), $segment, $link->target, $link->message, $this->_message->campaign);
                    $segment_data['rules'][$link->target]['classes'][] = $segmentrule;
                }
            }
        }
        arsort($link_data['counts']);
        arsort($link_data['percentages']['of_links']);
        arsort($link_data['percentages']['of_recipients']);

        if ($segmentation_param) {
            ksort($link_data['segments']);
            foreach ($link_data['segments'] as &$segment_data) {
                arsort($segment_data['counts']);
                arsort($segment_data['percentages']['of_links']);
                arsort($segment_data['percentages']['of_recipients']);
            }
        }
    }

    private function _generate_link_rules(org_openpsa_directmarketing_link_log_dba $link) : array
    {
        return [
            'comment' => sprintf($this->_l10n->get('all persons who have clicked on link "%s" in message #%d and have not unsubscribed from campaign #%d'), $link->target, $link->message, $this->_message->campaign),
            'type' => 'AND',
            'classes' => [
                [
                    'comment' => $this->_l10n->get('link and message limits'),
                    'type' => 'AND',
                    'class' => org_openpsa_directmarketing_link_log_dba::class,
                    'rules' => [
                        [
                            'property' => 'target',
                            'match' => '=',
                            'value' => $link->target,
                        ],
                        // PONDER: do we want to limit to this message only ??
                        [
                            'property' => 'message',
                            'match' => '=',
                            'value' => $link->message,
                        ],
                    ],
                ],
                // Add rule that prevents unsubscribed persons from ending up to the smart-campaign ??
                [
                    'comment' => $this->_l10n->get('not-unsubscribed -limits'),
                    'type' => 'AND',
                    'class' => org_openpsa_directmarketing_campaign_member_dba::class,
                    'rules' => [
                        [
                            'property' => 'orgOpenpsaObtype',
                            'match' => '<>',
                            'value' => org_openpsa_directmarketing_campaign_member_dba::UNSUBSCRIBED,
                        ],
                        [
                            'property' => 'campaign',
                            'match' => '=',
                            'value' => $this->_message->campaign,
                        ],
                    ],
                ],
            ],
        ];
    }

    private function _calculate_percentages(array &$array, org_openpsa_directmarketing_link_log_dba $link)
    {
        $this->_initialize_field($array['percentages']['of_links'], $link);
        $this->_initialize_field($array['percentages']['of_recipients'], $link);

        $link_data =& $this->_request_data['report']['link_data'];
        $array['percentages']['of_links'][$link->target]['total'] = ($array['counts'][$link->target]['total']/$link_data['total'])*100;
        $array['percentages']['of_links'][$link->target][$link->token] = ($array['counts'][$link->target][$link->token]/$link_data['total'])*100;

        $receipt_data =& $this->_request_data['report']['receipt_data'];
        $array['percentages']['of_recipients'][$link->target]['total'] = ((count($array['counts'][$link->target])-1)/($receipt_data['sent']-$receipt_data['bounced']))*100;
        $array['percentages']['of_recipients'][$link->target][$link->token] = ($array['counts'][$link->target][$link->token]/($receipt_data['sent']-$receipt_data['bounced']))*100;

        if (   !isset($array['percentages']['of_recipients']['total'])
            || $array['percentages']['of_recipients'][$link->target]['total'] > $array['percentages']['of_recipients']['total']) {
            $array['percentages']['of_recipients']['total'] = $array['percentages']['of_recipients'][$link->target]['total'];
        }
    }

    private function _initialize_field(array &$array, org_openpsa_directmarketing_link_log_dba $link)
    {
        if (!isset($array[$link->target])) {
            $array[$link->target] = [];
            $array[$link->target]['total'] = 0;
        }
        if (!isset($array[$link->target][$link->token])) {
            $array[$link->target][$link->token] = 0;
        }
    }

    private function _increment_totals(array &$array, org_openpsa_directmarketing_link_log_dba $link)
    {
        if (!isset($array['tokens'][$link->token])) {
            $array['tokens'][$link->token] = 0;
        }

        $this->_initialize_field($array['counts'], $link);

        $array['counts'][$link->target]['total']++;
        $array['counts'][$link->target][$link->token]++;
        $array['tokens'][$link->token]++;
    }

    private function _create_campaign_from_link(Request $request, string $identifier) : midcom_response_relocate
    {
        $rules = org_openpsa_directmarketing_campaign_ruleresolver::parse($request->request->get('oo_dirmar_rule_' . $identifier));
        $campaign = new org_openpsa_directmarketing_campaign_dba();
        $campaign->orgOpenpsaObtype = org_openpsa_directmarketing_campaign_dba::TYPE_SMART;
        $campaign->rules = $rules;
        $campaign->description = $rules['comment'];
        $campaign->title = sprintf($this->_l10n->get('from link "%s"'), $request->request->get('oo_dirmar_label_' . $identifier));
        $campaign->testers[midcom_connection::get_user()] = true;
        $campaign->node = $this->_topic->id;
        if (!$campaign->create()) {
            throw new midcom_error('Could not create campaign: ' . midcom_connection::get_error_string());
        }
        $campaign->schedule_update_smart_campaign_members();
        return new midcom_response_relocate($this->router->generate('view_campaign', ['guid' => $campaign->guid]));
    }

    public function _handler_report(Request $request, string $guid, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        $this->_message = new org_openpsa_directmarketing_campaign_message_dba($guid);
        $data['message'] = $this->_message;

        $data['message_array'] = datamanager::from_schemadb($this->_config->get('schemadb_message'))
            ->set_storage($this->_message)
            ->get_content_raw();

        $this->load_campaign($this->_message->campaign);

        $identifier = $request->request->get('oo_dirmar_userule');
        if ($request->request->has('oo_dirmar_rule_' . $identifier)) {
            return $this->_create_campaign_from_link($request, $identifier);
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . "/org.openpsa.core/list.css");

        $this->add_breadcrumb($this->router->generate('message_view', ['guid' => $guid]), $this->_message->title);
        $this->add_breadcrumb(
            $this->router->generate('message_report', ['guid' => $guid]),
            sprintf($this->_l10n->get('report for message %s'), $this->_message->title)
        );

        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('message_view', ['guid' => $guid]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get("back"),
                MIDCOM_TOOLBAR_GLYPHICON => 'eject',
            ],
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('compose4person', [
                    'guid' => $guid,
                    'person' => midcom::get()->auth->user->guid
                ]),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('preview message'),
                MIDCOM_TOOLBAR_GLYPHICON => 'search',
                MIDCOM_TOOLBAR_ACCESSKEY => 'p',
                MIDCOM_TOOLBAR_ENABLED => $this->_message->can_do('midgard:read'),
                MIDCOM_TOOLBAR_OPTIONS => ['target' => '_BLANK'],
            ]
        ];
        $this->_view_toolbar->add_items($buttons);
        $this->_analyze_message_report($data);

        return $this->show('show-message-report');
    }

    public function _handler_status(string $guid)
    {
        $message_obj = new org_openpsa_directmarketing_campaign_message_dba($guid);
        $sender = new org_openpsa_directmarketing_sender($message_obj);
        $result = $sender->get_status();
        $response = new midcom_response_json;
        $response->members = $result[0];
        $response->receipts = $result[1];

        return $response;
    }
}
