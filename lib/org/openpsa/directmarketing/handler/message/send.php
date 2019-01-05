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
class org_openpsa_directmarketing_handler_message_send extends midcom_baseclasses_components_handler
{
    use org_openpsa_directmarketing_handler;

    /**
     * @var datamanager
     */
    private $_datamanager;

    private function load_datamanager(org_openpsa_directmarketing_campaign_message_dba $message)
    {
        $this->_datamanager = datamanager::from_schemadb($this->_config->get('schemadb_message'))
            ->set_storage($message);
    }

    /**
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_send_bg(array $args, array &$data)
    {
        midcom::get()->auth->request_sudo($this->_component);

        //Load message
        $data['message'] = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        // TODO: Check that campaign is in this topic

        $this->load_datamanager($data['message']);

        $data['batch_number'] = $args[1];
        midcom_services_at_entry_dba::get_cached($args[2]);

        ignore_user_abort();
        midcom::get()->skip_page_style = true;
        midcom::get()->auth->drop_sudo();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_send_bg($handler_id, array &$data)
    {
        midcom::get()->auth->request_sudo($this->_component);
        debug_add('Forcing content type: text/plain');
        midcom::get()->header('Content-type: text/plain');
        $data['sender'] = $this->_get_sender($data);
        $composed = $this->_prepare_send($data);
        $bgstat = $data['sender']->send_bg($data['batch_url_base_full'], $data['batch_number'], $composed, $data['compose_from'], $data['compose_subject']);
        if (!$bgstat) {
            echo "ERROR\n";
        } else {
            echo "Batch #{$data['batch_number']} DONE\n";
        }
        midcom::get()->auth->drop_sudo();
    }

    private function _prepare_send(array &$data)
    {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($nap->get_current_node());
        $compose_url = $node[MIDCOM_NAV_RELATIVEURL] . 'message/compose/' . $data['message']->guid;
        $data['batch_url_base_full'] = $node[MIDCOM_NAV_RELATIVEURL] . 'message/send_bg/' . $data['message']->guid;
        debug_add("compose_url: {$compose_url}");
        debug_add("batch_url base: {$data['batch_url_base_full']}");
        $le_backup = ini_set('log_errors', true);
        $de_backup = ini_set('display_errors', false);
        ob_start();
        midcom::get()->dynamic_load($compose_url);
        $composed = ob_get_clean();
        ini_set('display_errors', $de_backup);
        ini_set('log_errors', $le_backup);
        //We force the content-type since the compositor might have set it to something else in compositor for preview purposes
        debug_add('Forcing content type: text/html');
        midcom::get()->header('Content-type: text/html');

        //PONDER: Should we leave these entirely for the methods to parse from the array ?
        $data['compose_subject'] = '';
        $data['compose_from'] = '';
        if (array_key_exists('subject', $data['message_array'])) {
            $data['compose_subject'] = &$data['message_array']['subject'];
        }
        if (array_key_exists('from', $data['message_array'])) {
            $data['compose_from'] = &$data['message_array']['from'];
        }

        return $composed;
    }

    /**
     * @param array $data Request data
     * @throws midcom_error
     * @return org_openpsa_directmarketing_sender
     */
    private function _get_sender(array &$data)
    {
        $data['message_array'] = $this->_datamanager->get_content_raw();
        $data['message_array']['dm_storage'] = $this->_datamanager->get_storage();
        if (!array_key_exists('content', $data['message_array'])) {
            throw new midcom_error('"content" not defined in schema');
        }

        $settings = [
            'mail_send_backend' => 'mail_send_backend',
            'bouncer_address' => 'bounce_detector_address',
            'linkdetector_address' => 'link_detector_address',
        ];

        foreach ($settings as $config_name => $target_name) {
            if ($value = $this->_config->get($config_name)) {
                $data['message_array'][$target_name] = $value;
            }
        }

        $sender = new org_openpsa_directmarketing_sender($data['message'], $data['message_array']);
        if ($token_size = $this->_config->get('token_size')) {
            $sender->token_size = $token_size;
        }
        return $sender;
    }

    /**
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_send(array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        //Load message
        $data['message'] = new org_openpsa_directmarketing_campaign_message_dba($args[0]);
        $data['campaign'] = $this->load_campaign($data['message']->campaign);

        $this->add_breadcrumb($this->router->generate('message_view', ['guid' => $data['message']->guid]), $data['message']->title);
        $this->add_breadcrumb("", $this->_l10n->get('send'));

        $this->load_datamanager($data['message']);

        $this->_request_data['send_start'] = time();

        ignore_user_abort();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_send($handler_id, array &$data)
    {
        $data['sender'] = $this->_get_sender($data);
        $composed = $this->_prepare_send($data);
        // TODO: Figure out the correct use of style elements, this is how it was but it's not exactly optimal...
        switch ($handler_id) {
            case 'test_send_message':
                // on-line send
                $data['sender']->test_mode = true;
                $data['sender']->send_output = true;
                $data['sender']->send($data['compose_subject'], $composed, $data['compose_from']);
                break;
            default:
                // Schedule background send
                if (!$data['sender']->register_send_job(1, $data['batch_url_base_full'], $data['send_start'])) {
                    throw new midcom_error("Job registration failed: " . midcom_connection::get_error_string());
                }
                midcom_show_style('send-start');
                break;
        }
    }
}
