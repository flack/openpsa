<?php
/**
 * @package net.nemein.redirector
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Response;

/**
 * @package net.nemein.redirector
 */
class net_nemein_redirector_handler_redirect extends midcom_baseclasses_components_handler
{
    /**
     * Process the redirect request
     */
    public function _handler_redirect(string $tinyurl)
    {
        $mc = net_nemein_redirector_tinyurl_dba::new_collector('node', $this->_topic->guid);
        $mc->add_constraint('name', '=', $tinyurl);
        $mc->add_value_property('code');
        $mc->add_value_property('url');
        $mc->execute();

        $results = $mc->list_keys();

        // No results found
        if (empty($results)) {
            throw new midcom_error_notfound($tinyurl . ' could not be found');
        }

        // Catch first the configuration option for showing editing interface instead
        // of redirecting administrators
        if (   $this->_topic->can_do('net.nemein.redirector:noredirect')
            && !$this->_config->get('admin_redirection')) {
            return new midcom_response_relocate($this->router->generate('edit', ['tinyurl' => $tinyurl]));
        }
        $guid = key($results);

        return $this->redirect($mc->get_subkey($guid, 'url'), $mc->get_subkey($guid, 'code') ?: null);
    }

    private function redirect(string $url, ?int $code = null) : Response
    {
        if (!$this->_config->get('redirection_metatag')) {
            return new midcom_response_relocate($url, $code ?: $this->_config->get('redirection_code'));
        }

        // Metatag redirection
        $data['redirection_url'] = $url;
        $data['redirection_speed'] = $this->_config->get('redirection_metatag_speed');

        midcom::get()->head->add_meta_head([
            'http-equiv' => 'refresh',
            'content' => "{$data['redirection_speed']};url={$url}",
            ]);

        return $this->show('redirection-page');
    }

    /**
     * Process the index request
     */
    public function _handler_index(array &$data)
    {
        $data['url'] = net_nemein_redirector_viewer::topic_links_to($this->_config);

        if (   $this->_config->get('redirection_type') !== null
            && (   !$this->_topic->can_do('net.nemein.redirector:noredirect')
                || $this->_config->get('admin_redirection'))) {
            return $this->redirect($data['url']);
        }

        // List TinyURLs
        $qb = net_nemein_redirector_tinyurl_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->guid);

        $data['tinyurls'] = $qb->execute();
        $data['workflow'] = $this->get_workflow('datamanager');
    }

    /**
     * Show the list of TinyURLs
     */
    public function _show_index(string $handler_id, array &$data)
    {
        midcom_show_style('tinyurl-list-start');

        // Initialize the datamanager instance
        $datamanager = datamanager::from_schemadb($this->_config->get('schemadb_tinyurl'));

        foreach ($data['tinyurls'] as $tinyurl) {
            $data['tinyurl'] = $tinyurl;
            $datamanager->set_storage($tinyurl);
            $data['view_tinyurl'] = $datamanager->get_content_html();

            midcom_show_style('tinyurl-list-item');
        }

        midcom_show_style('tinyurl-list-end');
    }
}
