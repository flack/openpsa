<?php
/**
 * @package net.nemein.redirector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * @package net.nemein.redirector
 */
class net_nemein_redirector_handler_tinyurl extends midcom_baseclasses_components_handler
{
    /**
     * TinyURL object
     *
     * @var net_nemein_redirector_tinyurl_dba
     */
    private $_tinyurl;

    /**
     * TinyURL object array
     *
     * @var net_nemein_redirector_tinyurl_dba[]
     */
    private $_tinyurls = [];

    /**
     * @return \midcom\datamanager\controller
     */
    private function load_controller()
    {
        $dm = datamanager::from_schemadb($this->_config->get('schemadb_tinyurl'));
        $dm->set_storage($this->_tinyurl);
        return $dm->get_controller();
    }

    /**
     * Populate request data
     *
     * @param String $handler_id
     */
    private function _populate_request_data($handler_id)
    {
        if ($handler_id === 'edit') {
            $this->add_breadcrumb("{$this->_tinyurl->name}/", $this->_tinyurl->title);
            $this->add_breadcrumb("edit/{$this->_tinyurl->name}", $this->_l10n_midcom->get('edit'));
            $workflow = $this->get_workflow('delete', ['object' => $this->_tinyurl]);
            $this->_view_toolbar->add_item($workflow->get_button('delete/' . $this->_tinyurl->guid . '/'));
            $this->_view_toolbar->bind_to($this->_tinyurl);
        } elseif ($handler_id === 'create') {
            $this->add_breadcrumb("create/", sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('tinyurl')));
        }
    }

    /**
     * Get the item according to the given rule
     *
     * @param mixed $rule
     * @return net_nemein_redirector_tinyurl_dba
     */
    private function _get_item($rule)
    {
        $qb = net_nemein_redirector_tinyurl_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->guid);

        $qb->begin_group('OR');
        $qb->add_constraint('guid', '=', $rule);
        $qb->add_constraint('name', '=', $rule);
        $qb->end_group();
        $results = $qb->execute();

        if (empty($results)) {
            throw new midcom_error_notfound('Item not found');
        }

        return $results[0];
    }

    /**
     * Create a new TinyURL
     *
     * @param Request $request The request object
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _handler_create(Request $request, $handler_id, array &$data)
    {
        $this->_topic->require_do('midgard:create');

        $this->_tinyurl = new net_nemein_redirector_tinyurl_dba();
        $this->_tinyurl->node = $this->_topic->guid;

        // Load the controller
        $data['controller'] = $this->load_controller();

        if ($data['controller']->handle($request) == 'save') {
            return new midcom_response_relocate("edit/{$this->_tinyurl->name}");
        }

        // Set the request data
        $this->_populate_request_data($handler_id);
        return $this->show('tinyurl-create');
    }

    /**
     * Edit an existing TinyURL
     *
     * @param Request $request The request object
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit(Request $request, $handler_id, array $args, array &$data)
    {
        $this->_tinyurl = $this->_get_item($args[0]);
        $this->_tinyurl->require_do('midgard:update');

        // Edit controller
        $data['controller'] = $this->load_controller();
        $data['tinyurl'] = $this->_tinyurl;

        switch ($data['controller']->handle($request)) {
            case 'save':
                midcom::get()->uimessages->add($this->_l10n->get('net.nemein.redirector'), $this->_l10n_midcom->get('saved'));
                // Fall through

            case 'cancel':
                return new midcom_response_relocate('');
        }

        // Set the request data
        $this->_populate_request_data($handler_id);
        return $this->show('tinyurl-edit');
    }


    /**
     * Delete an existing TinyURL
     *
     * @param Request $request The request object
     * @param array $args The argument list.
     */
    public function _handler_delete(Request $request, array $args)
    {
        $this->_tinyurl = $this->_get_item($args[0]);
        $workflow = $this->get_workflow('delete', ['object' => $this->_tinyurl]);
        return $workflow->run($request);
    }

    /**
     * List TinyURLs
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array &$data)
    {
        // Get the topic link and relocate accordingly
        $data['url'] = net_nemein_redirector_viewer::topic_links_to($data);

        $qb = net_nemein_redirector_tinyurl_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->guid);

        $this->_tinyurls = $qb->execute();

        // Initialize the datamanager instance
        $data['datamanager'] = datamanager::from_schemadb($this->_config->get('schemadb_tinyurl'));

        $data['workflow'] = $this->get_workflow('datamanager');
        // Set the request data
        $this->_populate_request_data($handler_id);
    }

    /**
     * Show the list of TinyURLs
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('tinyurl-list-start');

        foreach ($this->_tinyurls as $tinyurl) {
            $data['tinyurl'] = $tinyurl;
            $data['datamanager']->set_storage($tinyurl);
            $data['view_tinyurl'] = $data['datamanager']->get_content_html();

            midcom_show_style('tinyurl-list-item');
        }

        midcom_show_style('tinyurl-list-end');
    }
}
