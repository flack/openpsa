<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Wikipage edit handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_edit extends midcom_baseclasses_components_handler
{
    /**
     * The wikipage we're editing
     *
     * @var net_nemein_wiki_wikipage
     */
    private $_page = null;

    /**
     * The Controller of the article used for editing
     *
     * @var midcom_helper_datamanager2_controller_simple
     */
    private $_controller = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    private $_preview = false;

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $operations = Array();
        $operations['save'] = '';
        $operations['preview'] = $this->_l10n->get('preview');
        $operations['cancel'] = '';
        foreach ($this->_schemadb as $schema)
        {
            $schema->operations = $operations;
        }
    }

    /**
     * Internal helper, loads the controller for the current article. Any error triggers a 500.
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $this->_controller = midcom_helper_datamanager2_controller::create('simple');
        $this->_controller->schemadb =& $this->_schemadb;
        $this->_controller->set_storage($this->_page);
        if (! $this->_controller->initialize())
        {
            throw new midcom_error("Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
        }
    }

    /**
     * Check the edit request
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_page = $this->_master->load_page($args[0]);
        $this->_page->require_do('midgard:update');

        $this->_load_controller();

        switch ($this->_controller->process_form())
        {
            case 'preview':
                $this->_preview = true;
                $data['formmanager'] = $this->_controller->formmanager;
                break;
            case 'save':
                // Reindex the article
                $indexer = midcom::get()->indexer;
                net_nemein_wiki_viewer::index($this->_controller->datamanager, $indexer, $this->_topic);
                midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_request_data['l10n']->get('page %s saved'), $this->_page->title), 'ok');
                // *** FALL-THROUGH ***
            case 'cancel':
                if ($this->_page->name == 'index')
                {
                    return new midcom_response_relocate('');
                }
                return new midcom_response_relocate("{$this->_page->name}/");
        }

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "{$this->_page->name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('view'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_left.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'v',
            )
        );
        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "delete/{$this->_page->name}/",
                MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('delete'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/trash.png',
                MIDCOM_TOOLBAR_ACCESSKEY => 'd',
                MIDCOM_TOOLBAR_ENABLED => $this->_page->can_do('midgard:delete'),
            )
        );

        foreach (array_keys($this->_request_data['schemadb']) as $name)
        {
            if ($name == $this->_controller->datamanager->schema->name)
            {
                // The page is already of this type, skip
                continue;
            }

            $this->_view_toolbar->add_item
            (
                array
                (
                    MIDCOM_TOOLBAR_URL => "change/{$this->_page->name}/",
                    MIDCOM_TOOLBAR_LABEL => sprintf
                    (
                        $this->_l10n->get('change to %s'),
                        $this->_l10n->get($this->_request_data['schemadb'][$name]->description)
                    ),
                    MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/stock_refresh.png',
                    MIDCOM_TOOLBAR_POST => true,
                    MIDCOM_TOOLBAR_POST_HIDDENARGS => Array
                    (
                        'change_to' => $name,
                    ),
                    MIDCOM_TOOLBAR_ENABLED => $this->_page->can_do('midgard:update'),
                )
            );
        }

        $this->bind_view_to_object($this->_page, $this->_controller->datamanager->schema->name);

        $data['view_title'] = sprintf($this->_l10n->get('edit %s'), $this->_page->title);
        midcom::get()->head->set_pagetitle($data['view_title']);

        // Set the breadcrumb pieces
        $this->add_breadcrumb("{$this->_page->name}/", $this->_page->title);
        $this->add_breadcrumb("edit/{$this->_page->name}/", $this->_l10n_midcom->get('edit'));

        // Set the help object in the toolbar
        $this->_view_toolbar->add_help_item('markdown', 'net.nemein.wiki');
    }

    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_edit($handler_id, array &$data)
    {
        $data['controller'] = $this->_controller;
        $data['preview_mode'] = $this->_preview;

        if ($this->_preview)
        {
            // Populate preview page with values from form
            $data['preview_page'] = $this->_page;
            foreach ($this->_controller->datamanager->schema->fields as $name => $type_definition)
            {
                if (!is_a($this->_controller->datamanager->types[$name], 'midcom_helper_datamanager2_type_text'))
                {
                    // Skip fields of other types
                    continue;
                }
                switch ($type_definition['storage'])
                {
                    case 'parameter':
                    case 'configuration':
                    case 'metadata':
                        // Skip
                        continue;
                    default:
                        $location = $type_definition['storage']['location'];
                }
                $data['preview_page']->$location = $this->_controller->datamanager->types[$name]->convert_to_storage();
            }

            // Load DM for rendering the page
            $datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
            $datamanager->autoset_storage($data['preview_page']);

            $data['wikipage_view'] = $datamanager->get_content_html();
            $data['wikipage'] = $data['preview_page'];
            $data['autogenerate_toc'] = false;
            $data['display_related_to'] = false;

            // Replace wikiwords
            // TODO: We should somehow make DM2 do this so it would also work in AJAX previews
            $parser = new net_nemein_wiki_parser($data['preview_page']);
            $data['wikipage_view']['content'] = $parser->get_markdown($data['wikipage_view']['content']);
        }

        midcom_show_style('view-wikipage-edit');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_change($handler_id, array $args, array &$data)
    {
        if (empty($_POST['change_to']))
        {
            throw new midcom_error_forbidden('Only POST requests are allowed here.');
        }

        $this->_page = $this->_master->load_page($args[0]);
        $this->_page->require_do('midgard:update');

        // Change schema to redirect
        $this->_page->set_parameter('midcom.helper.datamanager2', 'schema_name', $_POST['change_to']);

        // Redirect to editing
        return new midcom_response_relocate("edit/{$this->_page->name}/");
    }
}
