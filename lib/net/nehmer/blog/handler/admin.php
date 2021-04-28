<?php
/**
 * @package net.nehmer.blog
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * n.n.blog admin page handler
 *
 * @package net.nehmer.blog
 */
class net_nehmer_blog_handler_admin extends midcom_baseclasses_components_handler
{
    use net_nehmer_blog_handler;

    /**
     * @var midcom_db_article
     */
    private $article;

    /**
     * Displays an article edit view.
     */
    public function _handler_edit(Request $request, array $args, array &$data)
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

        $schemadb = $data['schemadb'];
        if (   $this->_config->get('simple_name_handling')
            && !midcom::get()->auth->can_user_do('midcom:urlname')) {
            foreach ($schemadb->all() as $schema) {
                $schema->get_field('name')['readonly'] = true;
            }
        }

        $dm = new datamanager($schemadb);
        $controller = $dm->set_storage($this->article)
            ->get_controller();

        midcom::get()->head->set_pagetitle($this->_l10n->get('edit article'));

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
        net_nehmer_blog_viewer::index($controller->get_datamanager(), $indexer, $this->_topic);
        return $this->get_url($this->article);
    }

    /**
     * Handles article deletion
     */
    public function _handler_delete(Request $request, array $args)
    {
        $this->article = new midcom_db_article($args[0]);
        if ($this->article->topic !== $this->_topic->id) {
            throw new midcom_error_forbidden('Article does not belong to this topic');
        }
        $workflow = $this->get_workflow('delete', ['object' => $this->article]);
        return $workflow->run($request);
    }
}
