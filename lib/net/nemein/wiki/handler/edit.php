<?php
/**
 * @package net.nemein.wiki
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Wikipage edit handler
 *
 * @package net.nemein.wiki
 */
class net_nemein_wiki_handler_edit extends midcom_baseclasses_components_handler
{
    use net_nemein_wiki_handler;

    /**
     * The wikipage we're editing
     *
     * @var net_nemein_wiki_wikipage
     */
    private $page;

    /**
     * The Controller of the article used for editing
     *
     * @var controller
     */
    private $controller;

    /**
     * @var schemadb
     */
    private $schemadb;

    /**
     * Internal helper, loads the controller for the current article. Any error triggers a 500.
     */
    private function load_controller()
    {
        $operations = [
            'save' => '',
            'preview' => $this->_l10n->get('preview'),
            'cancel' => ''
        ];

        $this->schemadb = schemadb::from_path($this->_config->get('schemadb'));

        foreach ($this->schemadb->all() as $schema) {
            $schema->set('operations', $operations);
            if ($schema->has_field('title')) {
                $schema->get_field('title')['hidden'] = true;
            }
        }

        $dm = new datamanager($this->schemadb);
        $this->controller = $dm
            ->set_storage($this->page)
            ->get_controller();
    }

    /**
     * Check the edit request
     *
     * @param Request $request The request object
     * @param string $wikipage The page's name
     */
    public function _handler_edit(Request $request, $wikipage)
    {
        $this->page = $this->load_page($wikipage);
        $this->page->require_do('midgard:update');

        $this->load_controller();

        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->page->title));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->controller,
            'save_callback' => [$this, 'save_callback']
        ]);

        foreach ($this->schemadb->all() as $name => $schema) {
            if ($name == $this->controller->get_datamanager()->get_schema()->get_name()) {
                // The page is already of this type, skip
                continue;
            }
            $label = sprintf($this->_l10n->get('change to %s'), $this->_l10n->get($schema->get('description')));
            $workflow->add_post_button("change/{$this->page->name}/", $label, ['change_to' => $name]);
        }

        $response = $workflow->run($request);

        if ($workflow->get_state() == 'preview') {
            $this->add_preview();
        } elseif ($workflow->get_state() == 'save') {
            $indexer = midcom::get()->indexer;
            net_nemein_wiki_viewer::index($this->controller->get_datamanager(), $indexer, $this->_topic);
            midcom::get()->uimessages->add($this->_l10n->get($this->_component), sprintf($this->_l10n->get('page %s saved'), $this->page->title));
        }

        return $response;
    }

    private function add_preview()
    {
        $parser = new net_nemein_wiki_parser($this->page);
        $preview = $parser->get_html();

        midcom::get()->head->add_jscript('const wikipage_preview = ' . json_encode(['content' => '<div class="wiki_preview">' . $preview . '</div>']));
        midcom::get()->head->add_jquery_state_script('$("form.datamanager2 .form").prepend(wikipage_preview.content)');
    }

    /**
     * @param Request $request The request object
     * @param string $wikipage The page's name
     */
    public function _handler_change(Request $request, $wikipage)
    {
        if (!$request->request->has('change_to')) {
            throw new midcom_error_forbidden('Only POST requests are allowed here.');
        }

        $this->page = $this->load_page($wikipage);
        $this->page->require_do('midgard:update');

        // Change schema to redirect
        $this->page->set_parameter('midcom.helper.datamanager2', 'schema_name', $request->request->get('change_to'));

        // Redirect to editing
        return new midcom_response_relocate("edit/{$this->page->name}/");
    }
}
