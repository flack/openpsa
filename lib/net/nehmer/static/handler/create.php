<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * n.n.static create page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_create extends midcom_baseclasses_components_handler
{
    private midcom_db_article $article;

    private function load_controller(string $schemaname, array $defaults) : controller
    {
        if ($this->_config->get('simple_name_handling')) {
            $this->_request_data['schemadb']->get($schemaname)->get_field('name')['hidden'] = true;
        }
        $dm = new datamanager($this->_request_data['schemadb']);

        return $dm
            ->set_defaults($defaults)
            ->set_storage($this->article, $schemaname)
            ->get_controller();
    }

    /**
     * Displays an article create view.
     */
    public function _handler_create(Request $request, string $handler_id, array $args)
    {
        $this->_topic->require_do('midgard:create');
        $this->article = new midcom_db_article();
        $this->article->topic = $this->_topic->id;

        $defaults = [];
        if ($handler_id == 'createindex') {
            $defaults['name'] = 'index';
        }
        $controller = $this->load_controller($args[0], $defaults);

        $title = sprintf($this->_l10n_midcom->get('create %s'), $this->_l10n->get($controller->get_datamanager()->get_schema()->get('description')));
        midcom::get()->head->set_pagetitle($title);

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        // Reindex the article
        $indexer = midcom::get()->indexer;
        net_nehmer_static_viewer::index($controller->get_datamanager(), $indexer, $this->_topic);

        if ($callback = $this->_config->get('callback_function')) {
            $callback($this->article, $this->_topic);
        }

        if ($this->article->name == 'index') {
            return '';
        }
        return $this->article->name . '/';
    }
}
