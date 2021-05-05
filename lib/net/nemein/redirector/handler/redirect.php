<?php
/**
 * @package net.nemein.redirector
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package net.nemein.redirector
 */
class net_nemein_redirector_handler_redirect extends midcom_baseclasses_components_handler
{
    /**
     * Process the redirect request
     */
    public function _handler_redirect(array $args)
    {
        $mc = net_nemein_redirector_tinyurl_dba::new_collector('node', $this->_topic->guid);
        $mc->add_constraint('name', '=', $args[0]);
        $mc->add_value_property('code');
        $mc->add_value_property('url');
        $mc->execute();

        $results = $mc->list_keys();

        // No results found
        if (empty($results)) {
            throw new midcom_error_notfound($args[0] . ' could not be found');
        }

        // Catch first the configuration option for showing editing interface instead
        // of redirecting administrators
        if (   $this->_topic->can_do('net.nemein.redirector:noredirect')
            && !$this->_config->get('admin_redirection')) {
            return new midcom_response_relocate("{$this->_topic->name}/edit/{$args[0]}/");
        }
        $guid = key($results);
        $url = $mc->get_subkey($guid, 'url');
        $code = $mc->get_subkey($guid, 'code');

        // Redirection HTTP code
        if (!$code) {
            $code = $this->_config->get('redirection_code');
        }

        return new midcom_response_relocate($url, $code);
    }

    /**
     * Process the index request
     */
    public function _handler_index(array &$data)
    {
        // Get the topic link and relocate accordingly
        $url = net_nemein_redirector_viewer::topic_links_to($this->_config);

        if (!$this->_config->get('redirection_metatag')) {
            return new midcom_response_relocate($url, $this->_config->get('redirection_code'));
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
}
