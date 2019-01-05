<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;

/**
 * n.n.static admin page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_admin extends midcom_baseclasses_components_handler
{
    /**
     * The article to operate on
     *
     * @var midcom_db_article
     */
    private $article;

    /**
     * @return \midcom\datamanager\controller
     */
    private function load_controller()
    {
        if (    $this->_config->get('simple_name_handling')
             && !midcom::get()->auth->admin) {
            foreach ($this->_request_data['schemadb']->all() as $schema) {
                $field =& $schema->get_field('name');
                $field['readonly'] = true;
            }
        }
        $dm = new datamanager($this->_request_data['schemadb']);

        return $dm
            ->set_storage($this->article)
            ->get_controller();
    }

    /**
     * Displays an article edit view.
     *
     * @param array $args The argument list.
     */
    public function _handler_edit(array $args)
    {
        $this->article = new midcom_db_article($args[0]);

        // Relocate for the correct content topic, let the true content topic take care of the ACL
        if ($this->article->topic !== $this->_topic->id) {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($this->article->topic);

            if (!empty($node[MIDCOM_NAV_ABSOLUTEURL])) {
                return new midcom_response_relocate($node[MIDCOM_NAV_ABSOLUTEURL] . "edit/{$args[0]}/");
            }
            throw new midcom_error_notfound("The article with GUID {$args[0]} was not found.");
        }

        $this->article->require_do('midgard:update');
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->article->title));

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->load_controller(),
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run();
    }

    public function save_callback(controller $controller)
    {
        // Reindex the article
        $indexer = midcom::get()->indexer;
        net_nehmer_static_viewer::index($controller->get_datamanager(), $indexer, $this->_topic);
        if ($this->article->name == 'index') {
            return '';
        }
        return $this->article->name . '/';
    }

    /**
     * Displays an article delete confirmation view.
     *
     * @param array $args The argument list.
     */
    public function _handler_delete(array $args)
    {
        $this->article = new midcom_db_article($args[0]);
        if ($this->article->topic !== $this->_topic->id) {
            throw new midcom_error_forbidden('Article does not belong to this topic');
        }
        $workflow = $this->get_workflow('delete', ['object' => $this->article]);
        return $workflow->run();
    }
}
