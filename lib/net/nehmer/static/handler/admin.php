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
 * n.n.static admin page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_admin extends midcom_baseclasses_components_handler
{
    private midcom_db_article $article;

    private function load_controller() : controller
    {
        if (    $this->_config->get('simple_name_handling')
             && !midcom::get()->auth->admin) {
            foreach ($this->_request_data['schemadb']->all() as $schema) {
                $schema->get_field('name')['readonly'] = true;
            }
        }
        $dm = new datamanager($this->_request_data['schemadb']);

        return $dm
            ->set_storage($this->article)
            ->get_controller();
    }

    /**
     * Displays an article edit view.
     */
    public function _handler_edit(Request $request, string $guid)
    {
        $this->article = new midcom_db_article($guid);

        // Relocate for the correct content topic, let the true content topic take care of the ACL
        if ($this->article->topic !== $this->_topic->id) {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($this->article->topic);

            if (!empty($node[MIDCOM_NAV_ABSOLUTEURL])) {
                return new midcom_response_relocate($node[MIDCOM_NAV_ABSOLUTEURL] . "edit/{$guid}/");
            }
            throw new midcom_error_notfound("The article with GUID {$guid} was not found.");
        }

        $this->article->require_do('midgard:update');
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->article->title));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller(),
            'save_callback' => $this->save_callback(...)
        ]);
        return $workflow->run($request);
    }

    public function save_callback(controller $controller)
    {
        // Reindex the article
        $indexer = midcom::get()->indexer;
        net_nehmer_static_viewer::index($controller->get_datamanager(), $indexer, $this->_topic);
        if ($this->article->name == 'index') {
            return $this->router->generate('index');
        }
        return $this->router->generate('view', ['name' => $this->article->name]);
    }

    /**
     * Displays an article delete confirmation view.
     */
    public function _handler_delete(Request $request, string $guid)
    {
        $this->article = new midcom_db_article($guid);
        if ($this->article->topic !== $this->_topic->id) {
            throw new midcom_error_forbidden('Article does not belong to this topic');
        }
        $workflow = $this->get_workflow('delete', ['object' => $this->article]);
        return $workflow->run($request);
    }
}
