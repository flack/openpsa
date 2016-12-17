<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Handle the folder editing requests
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_edit extends midcom_baseclasses_components_handler
{
    /**
     * DM2 controller instance
     *
     * @var midcom_helper_datamanager2_controller
     */
    private $_controller;

    /**
     * ID of the handler
     */
    private $_handler_id;

    private $old_name;

    private $_new_topic;

    /**
     * Load either a create controller or an edit (simple) controller or trigger an error message
     */
    private function _load_controller()
    {
        // Get the configured schemas
        $schemadbs = $this->_config->get('schemadbs_folder');

        if ($this->_handler_id === 'createlink') {
            $schemadb = 'link';
        }
        // Check if a custom schema exists
        elseif (array_key_exists($this->_topic->component, $schemadbs)) {
            $schemadb = $this->_topic->component;
        } else {
            $schemadb = 'default';
        }

        if (!array_key_exists($schemadb, $schemadbs)) {
            throw new midcom_error('Configuration error. No ' . $schemadb . ' schema for topic has been defined!');
        }

        // Create the schema instance
        $schemadb = midcom_helper_datamanager2_schema::load_database($schemadbs[$schemadb]);

        foreach ($schemadb as $schema) {
            if (isset($schema->fields['name'])) {
                $schema->fields['name']['required'] = ($this->_handler_id === 'edit');
            }
        }
        switch ($this->_handler_id) {
            case 'edit':
                $this->_controller = midcom_helper_datamanager2_controller::create('simple');
                $this->_controller->schemadb = $schemadb;
                $this->_controller->set_storage($this->_topic);
                break;

            case 'create':
                $this->_controller = midcom_helper_datamanager2_controller::create('create');
                $this->_controller->schemadb = $schemadb;
                $this->_controller->schemaname = 'default';
                $this->_controller->callback_object =& $this;

                // Suggest to create the same type of a folder as the parent is
                $component_suggestion = $this->_topic->component;

                //Unless config told us otherwise
                if ($this->_config->get('default_component')) {
                    $component_suggestion = $this->_config->get('default_component');
                }

                $this->_controller->defaults = array(
                    'component' => $component_suggestion,
                );
                break;

            case 'createlink':
                $this->_controller = midcom_helper_datamanager2_controller::create('create');
                $this->_controller->schemadb =& $schemadb;
                $this->_controller->schemaname = 'link';
                $this->_controller->callback_object =& $this;
                break;

            default:
                throw new midcom_error('Unable to process the request, unknown handler id');
        }

        if (!$this->_controller->initialize()) {
            throw new midcom_error("Failed to initialize a DM2 controller instance for article {$this->_event->id}.");
        }
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    public function & dm2_create_callback(&$controller)
    {
        $this->_new_topic = new midcom_db_topic();
        $this->_new_topic->up = $this->_topic->id;

        if (!$this->_new_topic->create()) {
            debug_print_r('We operated on this object:', $this->_new_topic);
            throw new midcom_error('Failed to create a new topic, cannot continue. Last Midgard error was: '. midcom_connection::get_error_string());
        }

        return $this->_new_topic;
    }

    /**
     * Handler for folder editing. Checks for the permissions and folder integrity.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_edit($handler_id, array $args, array &$data)
    {
        $this->_topic->require_do('midcom.admin.folder:topic_management');

        $this->_handler_id = str_replace('____ais-folder-', '', $handler_id);

        if ($this->_handler_id == 'edit') {
            $this->_topic->require_do('midgard:update');
            $title = sprintf($this->_l10n->get('edit folder %s'), $this->_topic->get_label());
        } else {
            $this->_topic->require_do('midgard:create');
            $title = $this->_l10n->get('create folder');
            if ($this->_handler_id == 'createlink') {
                $this->_topic->require_do('midcom.admin.folder:symlinks');
                $title = $this->_l10n->get('create folder link');
            }
        }
        midcom::get()->head->set_pagetitle($title);

        // Load the DM2 controller
        $this->_load_controller();

        // Store the old name before editing
        $this->old_name = $this->_topic->name;
        // Symlink support requires that we use actual URL topic object here
        $urltopics = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_URLTOPICS);
        if ($urltopic = end($urltopics)) {
            $this->old_name = $urltopic->name;
        }

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.folder/folder.css');

        midcom::get()->head->set_pagetitle($title);

        $workflow = $this->get_workflow('datamanager2', array(
            'controller' => $this->_controller,
            'save_callback' => array($this, 'save_callback')
        ));
        return $workflow->run();
    }

    public function save_callback(midcom_helper_datamanager2_controller $controller)
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        if ($this->_handler_id === 'edit') {
            return $this->_update_topic($prefix, $this->old_name);
        }
        return $this->_create_topic($prefix);
    }

    private function _update_topic($prefix, $old_name)
    {
        if (   !empty($this->_topic->symlink)
            && !empty($this->_topic->component)) {
            $this->_topic->symlink = null;
            $this->_topic->update();
        }

        if ($_REQUEST['style'] === '__create') {
            $this->_topic->style = $this->_create_style($this->_topic->name);

            // Failed to create the new style template
            if ($this->_topic->style === '') {
                return false;
            }

            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('new style created'));

            if (!$this->_topic->update()) {
                midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), sprintf($this->_l10n->get('could not save folder: %s'), midcom_connection::get_error_string()));
                return false;
            }
        }

        midcom::get()->auth->request_sudo('midcom.admin.folder');
        // Because edit from a symlink edits its target, it is best to keep name properties in sync to get the expected behavior
        $qb_topic = midcom_db_topic::new_query_builder();
        $qb_topic->add_constraint('symlink', '=', $this->_topic->id);
        foreach ($qb_topic->execute() as $symlink_topic) {
            if ($symlink_topic->name !== $this->_topic->name) {
                $symlink_topic->name = $this->_topic->name;
                // This might fail if the URL name is already taken,
                // but in such case we can just ignore it silently which keeps the original value
                $symlink_topic->update();
            }
        }
        midcom::get()->auth->drop_sudo();

        midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('folder saved'));

        // Get the relocation url
        return preg_replace("/{$old_name}\/\$/", "{$this->_topic->name}/", $prefix);
    }

    private function _create_topic($prefix)
    {
        if (!empty($this->_new_topic->symlink)) {
            $name = $this->_new_topic->name;
            $target = $this->_new_topic;
            while (!empty($target->symlink)) {
                // Only direct symlinks are supported, but indirect symlinks are ok as we change them to direct ones here
                $this->_new_topic->symlink = $target->symlink;
                try {
                    $target = new midcom_db_topic($target->symlink);
                } catch (midcom_error $e) {
                    debug_add("Could not get target for symlinked topic #{$this->_new_topic->id}: " .
                        $e->getMessage(), MIDCOM_LOG_ERROR);

                    $this->_new_topic->purge();
                    throw new midcom_error(
                        "Refusing to create this symlink because its target folder was not found: " .
                        $e->getMessage()
                    );
                }
                $name = $target->name;
            }
            if ($this->_new_topic->up == $target->up) {
                $this->_new_topic->purge();
                throw new midcom_error(
                    "Refusing to create this symlink because it is located in the same
                    folder as its target"
                );
            }
            if ($this->_new_topic->up == $target->id) {
                $this->_new_topic->purge();
                throw new midcom_error(
                    "Refusing to create this symlink because its parent folder is the same
                    folder as its target."
                );
            }
            $this->_new_topic->update();
            if (!midcom_admin_folder_management::is_child_listing_finite($target)) {
                $this->_new_topic->purge();
                throw new midcom_error(
                    "Refusing to create this symlink because it would have created an
                    infinite loop situation."
                );
            }
            $this->_new_topic->name = $name;
            while (!$this->_new_topic->update() && midcom_connection::get_error() == MGD_ERR_DUPLICATE) {
                $this->_new_topic->name .= "-link";
            }
        }

        midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('folder created'));

        // Generate name if it is missing
        if (!$this->_new_topic->name) {
            $generator = midcom::get()->serviceloader->load('midcom_core_service_urlgenerator');
            $this->_new_topic->name = $generator->from_string($this->_new_topic->extra);
            $this->_new_topic->update();
        }

        // Get the relocation url
        return "{$prefix}{$this->_new_topic->name}/";
    }

    /**
     * Create a new style for the topic
     *
     * @param string $style_name Name of the style
     * @return string Style path
     */
    private function _create_style($style_name)
    {
        $style = new midcom_db_style();
        $style->name = $style_name;

        if (isset($GLOBALS['midcom_style_inherited'])) {
            $style->up = midcom::get()->style->get_style_id_from_path($GLOBALS['midcom_style_inherited']);
            debug_add("Style inherited from {$GLOBALS['midcom_style_inherited']}");
        }

        if (!$style->create()) {
            debug_print_r('Failed to create a new style due to ' . midcom_connection::get_error_string(), $style, MIDCOM_LOG_WARN);

            midcom::get()->uimessages->add('edit folder', sprintf($this->_l10n->get('failed to create a new style template: %s'), midcom_connection::get_error_string()), 'error');
            return '';
        }

        debug_print_r('New style created', $style);

        return midcom::get()->style->get_style_path_from_id($style->id);
    }
}
