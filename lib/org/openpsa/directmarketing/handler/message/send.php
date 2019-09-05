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
     * @param string $guid The object's GUID
     * @param integer $batch_number the batch number
     * @param string $job The AT entry's GUID
     * @param array $data The local request data.
     */
    public function _handler_send_bg($guid, $batch_number, $job, array &$data)
    {
        midcom::get()->auth->request_sudo($this->_component);

        //Load message
        $data['message'] = new org_openpsa_directmarketing_campaign_message_dba($guid);
        // TODO: Check that campaign is in this topic

        $this->load_datamanager($data['message']);

        $data['batch_number'] = $batch_number;
        midcom_services_at_entry_dba::get_cached($job);

        ignore_user_abort();
        midcom::get()->skip_page_style = true;
        midcom::get()->auth->drop_sudo();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_send_bg($handler_id, array &$data)
    {
        midcom::get()->auth->request_sudo($this->_component);
        $sender = $this->_get_sender($data);
        $composed = $this->compose($data);
        debug_add('Forcing content type: text/plain');
        midcom::get()->header('Content-Type: text/plain');
        $bgstat = $sender->send_bg($data['batch_url_base_full'], $data['batch_number'], $composed);
        if (!$bgstat) {
            echo "ERROR\n";
        } else {
            echo "Batch #{$data['batch_number']} DONE\n";
        }
        midcom::get()->auth->drop_sudo();
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

        //PONDER: Should we leave these entirely for the methods to parse from the array ?
        $subject = $data['message_array']['subject'] ?? '';
        $from = $data['message_array']['from'] ?? '';

        $sender = new org_openpsa_directmarketing_sender($data['message'], $data['message_array'], $from, $subject);
        if ($token_size = $this->_config->get('token_size')) {
            $sender->token_size = $token_size;
        }
        return $sender;
    }

    private function compose(array &$data) : string
    {
        $nap = new midcom_helper_nav();
        $node = $nap->get_node($nap->get_current_node());
        $compose_url = $node[MIDCOM_NAV_RELATIVEURL] . 'message/compose/' . $data['message']->guid .'/';
        $data['batch_url_base_full'] = $node[MIDCOM_NAV_RELATIVEURL] . 'message/send_bg/' . $data['message']->guid . '/';
        debug_add("compose_url: {$compose_url}");
        debug_add("batch_url base: {$data['batch_url_base_full']}");
        $le_backup = ini_set('log_errors', true);
        $de_backup = ini_set('display_errors', false);
        ob_start();
        midcom::get()->dynamic_load($compose_url);
        $composed = ob_get_clean();
        ini_set('display_errors', $de_backup);
        ini_set('log_errors', $le_backup);

        return $composed;
    }

    /**
     * @param string $guid The object's GUID
     * @param array $data The local request data.
     */
    public function _handler_send($guid, array &$data)
    {
        midcom::get()->auth->require_valid_user();
        //Load message
        $data['message'] = new org_openpsa_directmarketing_campaign_message_dba($guid);
        $data['campaign'] = $this->load_campaign($data['message']->campaign);

        $this->add_breadcrumb($this->router->generate('message_view', ['guid' => $guid]), $data['message']->title);
        $this->add_breadcrumb("", $this->_l10n->get('send'));

        $this->load_datamanager($data['message']);

        $this->_request_data['send_start'] = time();

        ignore_user_abort();
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $data The local request data.
     */
    public function _show_send($handler_id, array &$data)
    {
        $sender = $this->_get_sender($data);
        $composed = $this->compose($data);
        //We force the content-type since the compositor might have set it to something else in compositor for preview purposes
        debug_add('Forcing content type: text/html');
        midcom::get()->header('Content-Type: text/html');

        if ($handler_id == 'test_send_message') {
            // on-line send
            if ($sender->send_test($composed)) {
                midcom_show_style('send-finish');
            }
        } else {
            // Schedule background send
            if (!$sender->register_send_job(1, $data['batch_url_base_full'], $data['send_start'])) {
                throw new midcom_error("Job registration failed: " . midcom_connection::get_error_string());
            }
            midcom_show_style('send-status');
        }
    }
}
