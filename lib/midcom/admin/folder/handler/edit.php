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
     * DM2 schema
     *
     * @var midcom_helper_datamanager2_schema $_schema
     */
    private $_schemadb;

    /**
     * DM2 controller instance
     *
     * @var midcom_helper_datamanager2_controller $_controller
     */
    private $_controller;

    /**
     * ID of the handler
     */
    private $_handler_id;

    /**
     * Load the schemadb and other midcom.admin.folder specific stuff
     */
    public function _on_initialize()
    {
        // Load the configuration
        $_MIDCOM->componentloader->load('midcom.admin.folder');
    }

    /**
     * Load either a create controller or an edit (simple) controller or trigger an error message
     */
    private function _load_controller()
    {
        // Get the configured schemas
        $schemadbs = $this->_config->get('schemadbs_folder');

        // Check if a custom schema exists
        if (array_key_exists($this->_topic->component, $schemadbs))
        {
            $schemadb = $schemadbs[$this->_topic->component];
        }
        else
        {
            if (!array_key_exists('default', $schemadbs))
            {
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Configuration error. No default schema for topic has been defined!');
                // This will exit
            }

            $schemadb = $schemadbs['default'];
        }

        $GLOBALS['midcom_admin_folder_mode'] = $this->_handler_id;

        // Create the schema instance
        $this->_schemadb = midcom_helper_datamanager2_schema::load_database($schemadb);

        switch ($this->_handler_id)
        {
            case 'edit':
                $this->_controller = midcom_helper_datamanager2_controller::create('simple');
                $this->_controller->schemadb =& $this->_schemadb;
                $this->_controller->set_storage($this->_topic);
                break;

            case 'create':
                foreach ($this->_schemadb as $schema)
                {
                    if (isset($schema->fields['name']))
                    {
                        $schema->fields['name']['required'] = 0;
                    }
                }
                $this->_controller = midcom_helper_datamanager2_controller::create('create');
                $this->_controller->schemadb =& $this->_schemadb;
                $this->_controller->schemaname = 'default';
                $this->_controller->callback_object =& $this;

                // Suggest to create the same type of a folder as the parent is
                $component_suggestion = $this->_topic->component;

                //Unless config told us otherwise
                if ($this->_config->get('default_component'))
                {
                    $component_suggestion = $this->_config->get('default_component');
                }

                $this->_controller->defaults = array
                (
                    'component' => $component_suggestion,
                );
                break;

            case 'createlink':
                if (!array_key_exists('link', $schemadbs))
                {
                     $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Configuration error. No link schema for topic has been defined!');
                    // This will exit
                }
                $schemadb = $schemadbs['link'];
                // Create the schema instance
                $this->_schemadb = midcom_helper_datamanager2_schema::load_database($schemadb);

                $this->_schemadb->default->fields['name']['required'] = 0;
                $this->_controller = midcom_helper_datamanager2_controller::create('create');
                $this->_controller->schemadb =& $this->_schemadb;
                $this->_controller->schemaname = 'link';
                $this->_controller->callback_object =& $this;
                break;

            default:
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT, 'Unable to process the request, unknown handler id');
                // This will exit
        }

        if (! $this->_controller->initialize())
        {
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT, "Failed to initialize a DM2 controller instance for article {$this->_event->id}.");
            // This will exit.
        }
    }

    /**
     * DM2 creation callback, binds to the current content topic.
     */
    function & dm2_create_callback (&$controller)
    {
        $this->_new_topic = new midcom_db_topic();
        $this->_new_topic->up = $this->_topic->id;

        if (! $this->_new_topic->create())
        {
            debug_print_r('We operated on this object:', $this->_new_topic);
            $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                'Failed to create a new topic, cannot continue. Last Midgard error was: '. midcom_connection::get_error_string());
            // This will exit.
        }

        return $this->_new_topic;
    }


    /**
     * Handler for folder editing. Checks for the permissions and folder integrity.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success
     */
    public function _handler_edit($handler_id, $args, &$data)
    {
        $this->_topic->require_do('midcom.admin.folder:topic_management');

        $this->_handler_id = str_replace('____ais-folder-', '', $handler_id);

        if ($this->_handler_id == 'create' || $this->_handler_id == 'createlink')
        {
            $this->_topic->require_do('midgard:create');
            if ($this->_handler_id == 'createlink')
            {
                $this->_topic->require_do('midcom.admin.folder:symlinks');
            }
        }
        else
        {
            $this->_topic->require_do('midgard:update');
        }

        // Load the DM2 controller
        $this->_load_controller();

        // Get the content topic prefix
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

        // Store the old name before editing
        $old_name = $this->_topic->name;
        // Symlink support requires that we use actual URL topic object here
        if ($urltopic = end($_MIDCOM->get_context_data(MIDCOM_CONTEXT_URLTOPICS)))
        {
            $old_name = $urltopic->name;
        }

        switch ($this->_controller->process_form())
        {
            case 'cancel':
                $_MIDCOM->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('cancelled'));
                $_MIDCOM->relocate($prefix);
                break;

            case 'save':
                if ($this->_handler_id === 'edit')
                {
                    $url = $this->_update_topic($prefix, $old_name);
                }
                else
                {
                    $url = $this->_create_topic($prefix);
                }
                $_MIDCOM->relocate($url);
                // This will exit
        }

        if ($this->_handler_id == 'create')
        {
            $data['title'] = $this->_l10n->get('create folder');
        }
        else if ($this->_handler_id == 'createlink')
        {
            $data['title'] = $this->_l10n->get('create folder link');
        }
        else
        {
            $data['title'] = sprintf($this->_l10n->get('edit folder %s'), $data['topic']->extra);
        }

        // Hide the button in toolbar
        $this->_node_toolbar->hide_item('__ais/folder/' . $this->_handler_id . '/');

        // Add the view to breadcrumb trail
        $this->add_breadcrumb('__ais/folder/edit/', $data['title']);

        $data['topic'] =& $this->_topic;
        $data['controller'] =& $this->_controller;

        // Set page title
        $_MIDCOM->set_pagetitle($data['title']);

        // Set the help object in the toolbar
        $help_toolbar = $_MIDCOM->toolbars->get_help_toolbar();
        $help_toolbar->add_help_item('edit_folder', 'midcom.admin.folder', null, null, 1);

        // Ensure we get the correct styles
        $_MIDCOM->style->prepend_component_styledir('midcom.admin.folder');

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.folder/folder.css');

        return true;
    }

    private function _update_topic($prefix, $old_name)
    {
        if (   !empty($this->_topic->symlink)
            && !empty($this->_topic->component))
        {
            $this->_topic->symlink = null;
            $this->_topic->update();
        }

        if ($_REQUEST['style'] === '__create')
        {
            $this->_topic->style = $this->_create_style($this->_topic->name);

            // Failed to create the new style template
            if ($this->_topic->style === '')
            {
                return false;
            }

            $_MIDCOM->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('new style created'));

            if (! $this->_topic->update())
            {
                $_MIDCOM->uimessages->add($this->_l10n->get('midcom.admin.folder'), sprintf($this->_l10n->get('could not save folder: %s'), midcom_connection::get_error_string()));
                return false;
            }
        }

        $_MIDCOM->auth->request_sudo('midcom.admin.folder');
        // Because edit from a symlink edits its target, it is best to keep name properties in sync to get the expected behavior
        $qb_topic = midcom_db_topic::new_query_builder();
        $qb_topic->add_constraint('symlink', '=', $this->_topic->id);
        foreach ($qb_topic->execute() as $symlink_topic)
        {
            if (empty($symlink_topic->symlink))
            {
                debug_add("Symlink topic is not a symlink. Query must have failed. " .
                    "Constraint was: #{$this->_topic->id}", MIDCOM_LOG_ERROR);
            }
            else
            {
                if ($symlink_topic->name !== $this->_topic->name)
                {
                    $symlink_topic->name = $this->_topic->name;
                    // This might fail if the URL name is already taken,
                    // but in such case we can just ignore it silently which keeps the original value
                    $symlink_topic->update();
                }
            }
        }
        $_MIDCOM->auth->drop_sudo();

        $_MIDCOM->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('folder saved'));

        // Get the relocation url
        $url = preg_replace("/{$old_name}\/\$/", "{$this->_topic->name}/", $prefix);
        return $url;
    }

    private function create_topic($prefix)
    {
        if (!empty($this->_new_topic->symlink))
        {
            $name = $this->_new_topic->name;
            $topic = $this->_new_topic;
            while (!empty($topic->symlink))
            {
                // Only direct symlinks are supported, but indirect symlinks are ok as we change them to direct ones here
                $this->_new_topic->symlink = $topic->symlink;
                $topic = new midcom_db_topic($topic->symlink);
                if (!$topic->guid)
                {
                    debug_add("Could not get target for symlinked topic #{$this->_new_topic->id}: " .
                        midcom_connection::get_error_string(), MIDCOM_LOG_ERROR);
                    $topic = $this->_new_topic;

                    $this->_new_topic->purge();
                    $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                        "Refusing to create this symlink because its target folder was not found: " .
                        midcom_connection::get_error_string()
                    );
                    // This will exit

                    break;
                }
                $name = $topic->name;
            }
            if ($this->_new_topic->up == $topic->up)
            {
                $this->_new_topic->purge();
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Refusing to create this symlink because it is located in the same " .
                    "folder as its target. You must have made a mistake. Sorry, but this " .
                    "was for your own good."
                );
                // This will exit
            }
            if ($this->_new_topic->up == $topic->id)
            {
                $this->_new_topic->purge();
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Refusing to create this symlink because its parent folder is the same " .
                    "folder as its target. You must have made a mistake because this would " .
                    "have created an infinite loop situation. The whole site would have " .
                    "been completely and irrevocably broken if this symlink would have been " .
                    "allowed to exist. Infinite loops can not be allowed. Sorry, but this " .
                    "was for your own good."
                );
                // This will exit
            }
            $this->_new_topic->update();
            if (!midcom_admin_folder_folder_management::is_child_listing_finite($topic))
            {
                $this->_new_topic->purge();
                $_MIDCOM->generate_error(MIDCOM_ERRCRIT,
                    "Refusing to create this symlink because it would have created an " .
                    "infinite loop situation. The whole site would have been completely " .
                    "and irrevocably broken if this symlink would have been allowed to " .
                    "exist. Please redesign your usage of symlinks. Infinite loops can " .
                    "not be allowed. Sorry, but this was for your own good."
                );
                // This will exit
            }
            $this->_new_topic->name = $name;
            while (!$this->_new_topic->update() && midcom_connection::get_error() == MGD_ERR_DUPLICATE)
            {
                $this->_new_topic->name .= "-link";
            }
        }

        $_MIDCOM->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('folder created'));

        // Generate name if it is missing
        if (!$this->_new_topic->name)
        {
            $this->_new_topic->name = midcom_helper_misc::generate_urlname_from_string($this->_new_topic->extra);
            $this->_new_topic->update();
        }

        // Get the relocation url
        $url = "{$prefix}{$this->_new_topic->name}/";
        return $url;
    }

    /**
     * Create a new style for the topic
     *
     * @param string $style_name Name of the style
     * @return string Style path
     */
    private function _create_style($style_name)
    {
        if (isset($GLOBALS['midcom_style_inherited']))
        {
            $up = $_MIDCOM->style->get_style_id_from_path($GLOBALS['midcom_style_inherited']);
            debug_add("Style inherited from {$GLOBALS['midcom_style_inherited']}");
        }
        else
        {
            $up = $_MIDGARD['style'];
            debug_add("No inherited style found, placing the new style under host style (ID: {$_MIDGARD['style']}");
        }

        $style = new midcom_db_style();
        $style->name = $style_name;
        $style->up = $up;

        if (!$style->create())
        {
            debug_print_r('Failed to create a new style due to ' . midcom_connection::get_error_string(), $style, MIDCOM_LOG_WARN);

            $_MIDCOM->uimessages->add('edit folder', sprintf($_MIDCOM->i18n->get_string('failed to create a new style template: %s', 'midcom.admin.folder'), midcom_connection::get_error_string()), 'error');
            return '';
        }

        debug_print_r('New style created', $style);

        return $_MIDCOM->style->get_style_path_from_id($style->id);
    }

    /**
     * Shows the _Edit folder_ page.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param mixed &$data The local request data.
     */
    public function _show_edit($handler_id, &$data)
    {
        // Show the style element
        if ($this->_handler_id === 'create')
        {
            $data['page_title'] = sprintf($this->_i18n->get_string("create folder", 'midcom.admin.folder'));
        }
        else
        {
            $topic_title = $this->_topic->extra;
            if (!$topic_title)
            {
                $topic_title = $this->_topic->name;
            }
            $data['page_title'] = sprintf($this->_i18n->get_string("{$this->_handler_id} folder %s", 'midcom.admin.folder'), $topic_title);
        }

        midcom_show_style('midcom-admin-show-folder-actions');
    }
}
?>
