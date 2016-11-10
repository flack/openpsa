<?php
/**
 * @package net.nehmer.static
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * n.n.static admin page handler
 *
 * @package net.nehmer.static
 */
class net_nehmer_static_handler_admin extends midcom_baseclasses_components_handler
{
    /**
     * The content topic to use
     *
     * @var midcom_db_topic
     */
    private $_content_topic = null;

    /**
     * The article to operate on
     *
     * @var midcom_db_article
     */
    private $_article = null;

    /**
     * The schema database in use, available only while a datamanager is loaded.
     *
     * @var Array
     */
    private $_schemadb = null;

    /**
     * Maps the content topic from the request data to local member variables.
     */
    public function _on_initialize()
    {
        $this->_content_topic = $this->_request_data['content_topic'];
    }

    /**
     * Loads and prepares the schema database.
     *
     * Special treatment is done for the name field, which is set readonly for non-admins
     * if the simple_name_handling config option is set. (using an auto-generated urlname based
     * on the title, if it is missing.)
     *
     * The operations are done on all available schemas within the DB.
     */
    private function _load_schemadb()
    {
        $this->_schemadb =& $this->_request_data['schemadb'];
        if (   $this->_config->get('simple_name_handling')
            && !midcom::get()->auth->admin) {
            foreach (array_keys($this->_schemadb) as $name) {
                $this->_schemadb[$name]->fields['name']['readonly'] = true;
            }
        }
    }

    /**
     * Internal helper, loads the controller for the current article. Any error triggers a 500.
     *
     * @return midcom_helper_datamanager2_controller_simple
     */
    private function _load_controller()
    {
        $this->_load_schemadb();
        $controller = midcom_helper_datamanager2_controller::create('simple');
        $controller->schemadb =& $this->_schemadb;
        $controller->set_storage($this->_article);

        if (!$controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 controller instance for article {$this->_article->id}.");
        }
        return $controller;
    }

    /**
     * Displays an article edit view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_article = new midcom_db_article($args[0]);

        // Relocate for the correct content topic, let the true content topic take care of the ACL
        if ($this->_article->topic !== $this->_content_topic->id) {
            $nap = new midcom_helper_nav();
            $node = $nap->get_node($this->_article->topic);

            if (!empty($node[MIDCOM_NAV_ABSOLUTEURL])) {
                return new midcom_response_relocate($node[MIDCOM_NAV_ABSOLUTEURL] . "edit/{$args[0]}/");
            }
            throw new midcom_error_notfound("The article with GUID {$args[0]} was not found.");
        }

        $this->_article->require_do('midgard:update');
        midcom::get()->head->set_pagetitle(sprintf($this->_l10n_midcom->get('edit %s'), $this->_article->title));

        $workflow = $this->get_workflow('datamanager2', array
        (
            'controller' => $this->_load_controller(),
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        // Reindex the article
        $indexer = midcom::get()->indexer;
        net_nehmer_static_viewer::index($controller->datamanager, $indexer, $this->_content_topic);
        if ($this->_article->name == 'index') {
            return '';
        }
        return $this->_article->name . '/';
    }

    /**
     * Displays article link delete confirmation
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_deletelink($handler_id, array $args, array &$data)
    {
        $this->_article = new midcom_db_article($args[0]);

        $qb = net_nehmer_static_link_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_content_topic->id);
        $qb->add_constraint('article', '=', $this->_article->id);

        if ($qb->count() === 0) {
            throw new midcom_error_notfound('No links were found');
        }

        // Get the link
        $results = $qb->execute_unchecked();
        $this->_link = $results[0];
        $workflow = $this->get_workflow('delete', array
        (
            'object' => $this->_link,
            'label' => $this->_l10n->get('article link')
        ));
        return $workflow->run();
    }

    /**
     * Displays an article delete confirmation view.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_delete($handler_id, array $args, array &$data)
    {
        $this->_article = new midcom_db_article($args[0]);
        // Relocate to delete the link instead of the article itself
        if ($this->_article->topic !== $this->_content_topic->id) {
            return new midcom_response_relocate("delete/link/{$args[0]}/");
        }
        $workflow = $this->get_workflow('delete', array('object' => $this->_article));
        return $workflow->run();
    }
}
