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

    /**
     * Loads and prepares the schema database.
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb'));

        $operations = array();
        $operations['save'] = '';
        $operations['preview'] = $this->_l10n->get('preview');
        $operations['cancel'] = '';
        foreach ($this->_schemadb as $schema) {
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
        if (!$this->_controller->initialize()) {
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

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n->get('edit %s'), $this->_page->title));

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $this->_controller,
            'save_callback' => array($this, 'save_callback')
        ));

        foreach ($data['schemadb'] as $name => $schema) {
            if ($name == $this->_controller->datamanager->schema->name) {
                // The page is already of this type, skip
                continue;
            }
            $label = sprintf($this->_l10n->get('change to %s'), $this->_l10n->get($schema->description));
            $workflow->add_post_button("change/{$this->_page->name}/", $label, array('change_to' => $name));
        }

        $response = $workflow->run();

        if ($workflow->get_state() == 'preview') {
            $this->add_preview();
        } elseif ($workflow->get_state() == 'save') {
            $indexer = midcom::get()->indexer;
            net_nemein_wiki_viewer::index($this->_controller->datamanager, $indexer, $this->_topic);
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('page %s saved'), $this->_page->title));
        }

        return $response;
    }

    private function add_preview()
    {
        $preview_page = $this->_page;
        foreach ($this->_controller->datamanager->schema->fields as $name => $type_definition) {
            if (!is_a($this->_controller->datamanager->types[$name], 'midcom_helper_datamanager2_type_text')) {
                // Skip fields of other types
                continue;
            }
            switch ($type_definition['storage']) {
                case 'parameter':
                case 'configuration':
                case 'metadata':
                    // Skip
                    continue;
                default:
                    $location = $type_definition['storage']['location'];
            }
            $preview_page->$location = $this->_controller->datamanager->types[$name]->convert_to_storage();
        }

        // Load DM for rendering the page
        $datamanager = new midcom_helper_datamanager2_datamanager($this->_schemadb);
        $datamanager->autoset_storage($preview_page);
        $wikipage_view = $datamanager->get_content_html();
        // Replace wikiwords
        // TODO: We should somehow make DM2 do this so it would also work in AJAX previews
        $parser = new net_nemein_wiki_parser($preview_page);
        $preview = $parser->get_markdown($wikipage_view['content']);

        $form = $this->_controller->formmanager->form;
        $form->addElement('static', 'preview', $this->_l10n->get('preview'), '<div class="wiki_preview">' . $preview . '</div>');
        $element = array_pop($form->_elements);
        array_unshift($form->_elements, $element);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_change($handler_id, array $args, array &$data)
    {
        if (empty($_POST['change_to'])) {
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
