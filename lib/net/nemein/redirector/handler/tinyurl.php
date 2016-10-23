<?php
/**
 * @package net.nemein.redirector
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package net.nemein.redirector
 */
class net_nemein_redirector_handler_tinyurl extends midcom_baseclasses_components_handler
implements midcom_helper_datamanager2_interfaces_create
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
    private $_tinyurls = array();

    /**
     * Datamanager2 instance
     *
     * @var midcom_helper_datamanager2_datamanager
     */
    private $_datamanager = null;

    public function load_schemadb()
    {
        return midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_tinyurl'));
    }

    public function get_schema_name()
    {
        return 'tinyurl';
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function &dm2_create_callback (&$controller)
    {
        $this->_tinyurl = new net_nemein_redirector_tinyurl_dba();
        $this->_tinyurl->node = $this->_topic->guid;

        if (!$this->_tinyurl->create())
        {
            throw new midcom_error('Failed to create a new TinyURL object. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_tinyurl;
    }

    /**
     * Populate request data
     *
     * @param String $handler_id
     */
    private function _populate_request_data($handler_id)
    {
        if ($this->_tinyurl)
        {
            $this->add_breadcrumb("{$this->_tinyurl->name}/", $this->_tinyurl->title);
            $this->_view_toolbar->bind_to($this->_tinyurl);
        }

        switch ($handler_id)
        {
            case 'edit':
            case 'delete':
                $this->add_breadcrumb("{$this->_tinyurl->name}/{$handler_id}", $this->_l10n->get($this->_l10n_midcom->get($handler_id)));
                break;

            case 'create':
                $this->add_breadcrumb("create/", sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get('tinyurl')));
                break;
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

        if (empty($results))
        {
            throw new midcom_error_notfound('Item not found');
        }

        return new $results[0];
    }

    /**
     * Create a new TinyURL
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_create($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midgard:create');

        // Load the controller
        $data['controller'] = $this->get_controller('create');

        switch ($data['controller']->process_form())
        {
            case 'save':
                return new midcom_response_relocate("edit/{$this->_tinyurl->name}");
        }

        // Set the request data
        $this->_populate_request_data($handler_id);
    }

    /**
     * Show the creation form
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_create($handler_id, array &$data)
    {
        midcom_show_style('tinyurl-create');
    }

    /**
     * Edit an existing TinyURL
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_tinyurl = $this->_get_item($args[0]);
        $this->_tinyurl->require_do('midgard:update');

        // Edit controller
        $data['controller'] = $this->get_controller('simple', $this->_tinyurl);
        $data['tinyurl'] = $this->_tinyurl;

        switch ($data['controller']->process_form())
        {
            case 'save':
                midcom::get()->uimessages->add($this->_l10n->get('net.nemein.redirector'), $this->_l10n_midcom->get('saved'));
                // Fall through

            case 'cancel':
                return new midcom_response_relocate('');
        }

        // Set the request data
        $this->_populate_request_data($handler_id);
    }

    /**
     * Show the creation form
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        midcom_show_style('tinyurl-edit');
    }

    /**
     * List TinyURLs
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        // Get the topic link and relocate accordingly
        $data['url'] = net_nemein_redirector_viewer::topic_links_to($data);

        $qb = net_nemein_redirector_tinyurl_dba::new_query_builder();
        $qb->add_constraint('node', '=', $this->_topic->guid);

        $this->_tinyurls = $qb->execute();

        // Initialize the datamanager instance
        $schemadb = $this->load_schemadb();
        $this->_datamanager = new midcom_helper_datamanager2_datamanager($schemadb);

        $data['workflow'] = $this->get_workflow('datamanager2');
        // Set the request data
        $this->_populate_request_data($handler_id);
    }

    /**
     * Show the list of TinyURL's
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_list($handler_id, array &$data)
    {
        midcom_show_style('tinyurl-list-start');

        $data['datamanager'] = $this->_datamanager;

        foreach ($this->_tinyurls as $tinyurl)
        {
            $data['tinyurl'] = $tinyurl;
            $data['datamanager']->autoset_storage($tinyurl);
            $data['view_tinyurl'] = $data['datamanager']->get_content_html();

            midcom_show_style('tinyurl-list-item');
        }

        midcom_show_style('tinyurl-list-end');
    }
}
