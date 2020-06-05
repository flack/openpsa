<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use midcom\datamanager\schemadb;
use midcom\datamanager\controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle the folder editing requests
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_edit extends midcom_baseclasses_components_handler
{
    /**
     * Controller instance
     *
     * @var controller
     */
    private $_controller;

    /**
     * ID of the handler
     */
    private $_handler_id;

    private $old_name;

    private $edit_topic;

    private function _load_controller()
    {
        // Get the configured schemas
        $schemadbs = $this->_config->get('schemadbs_folder');

        // Check if a custom schema exists
        if (array_key_exists($this->_topic->component, $schemadbs)) {
            $schemadb = $this->_topic->component;
        } else {
            $schemadb = 'default';
        }

        if (!array_key_exists($schemadb, $schemadbs)) {
            throw new midcom_error('Configuration error. No ' . $schemadb . ' schema for topic has been defined!');
        }

        $schemadb = schemadb::from_path($schemadbs[$schemadb]);

        foreach ($schemadb->all() as $schema) {
            if ($schema->has_field('name')) {
                $schema->get_field('name')['required'] = $this->_handler_id === 'edit';
            }
        }
        $defaults = [];
        if ($this->_handler_id == 'create') {
            // Suggest to create the same type of a folder as the parent is
            $component_suggestion = $this->_topic->component;

            //Unless config told us otherwise
            if ($this->_config->get('default_component')) {
                $component_suggestion = $this->_config->get('default_component');
            }

            $defaults['component'] = $component_suggestion;
        }

        $dm = new datamanager($schemadb);
        $this->_controller = $dm
            ->set_defaults($defaults)
            ->set_storage($this->edit_topic)
            ->get_controller();
    }

    /**
     * Handler for folder editing. Checks for the permissions and folder integrity.
     */
    public function _handler_edit(Request $request, string $handler_id)
    {
        $this->_topic->require_do('midcom.admin.folder:topic_management');
        $this->_handler_id = $handler_id;

        if ($this->_handler_id == 'edit') {
            $this->_topic->require_do('midgard:update');
            $title = sprintf($this->_l10n->get('edit folder %s'), $this->_topic->get_label());
            $this->edit_topic = $this->_topic;
        } else {
            $this->_topic->require_do('midgard:create');
            $title = $this->_l10n->get('create folder');
            $this->edit_topic = new midcom_db_topic();
            $this->edit_topic->up = $this->_topic->id;
        }
        midcom::get()->head->set_pagetitle($title);

        $this->_load_controller();

        // Store the old name before editing
        $this->old_name = $this->_topic->name;

        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.folder/folder.css');
        midcom::get()->head->set_pagetitle($title);

        $workflow = $this->get_workflow('datamanager', [
            'controller' => $this->_controller,
            'save_callback' => [$this, 'save_callback']
        ]);
        return $workflow->run($request);
    }

    public function save_callback()
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        if ($this->_handler_id === 'edit') {
            return $this->_update_topic($prefix, $this->old_name);
        }
        return $this->_create_topic($prefix);
    }

    private function _update_topic(string $prefix, string $old_name)
    {
        if (isset($this->_controller->get_form_values()['style']) && $this->_controller->get_form_values()['style'] == '__create') {
            $this->edit_topic->style = $this->_create_style($this->edit_topic->name);

            // Failed to create the new style template
            if ($this->edit_topic->style === '') {
                return false;
            }

            midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('new style created'));

            if (!$this->edit_topic->update()) {
                midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), sprintf($this->_l10n->get('could not save folder: %s'), midcom_connection::get_error_string()));
                return false;
            }
        }

        midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('folder saved'));

        // Get the relocation url
        if (midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ROOTTOPIC)->id === $this->edit_topic->id) {
            return $prefix;
        }
        return preg_replace("/{$old_name}\/\$/", "{$this->edit_topic->name}/", $prefix);
    }

    private function _create_topic(string $prefix) : string
    {
        midcom::get()->uimessages->add($this->_l10n->get('midcom.admin.folder'), $this->_l10n->get('folder created'));

        // Generate name if it is missing
        if (!$this->edit_topic->name) {
            $this->edit_topic->name = midcom_helper_misc::urlize($this->edit_topic->extra);
            $this->edit_topic->update();
        }

        // Get the relocation url
        return "{$prefix}{$this->edit_topic->name}/";
    }

    /**
     * Create a new style for the topic
     *
     * @param string $style_name Name of the style
     * @return string Style path
     */
    private function _create_style(string $style_name) : string
    {
        $style = new midcom_db_style();
        $style->name = $style_name;

        if ($inherited = midcom_core_context::get()->get_inherited_style()) {
            $style->up = midcom_db_style::id_from_path($inherited);
            debug_add("Style inherited from {$inherited}");
        }

        if (!$style->create()) {
            debug_print_r('Failed to create a new style due to ' . midcom_connection::get_error_string(), $style, MIDCOM_LOG_WARN);

            midcom::get()->uimessages->add($this->_l10n->get('edit folder'), sprintf($this->_l10n->get('failed to create a new style template: %s'), midcom_connection::get_error_string()), 'error');
            return '';
        }

        debug_print_r('New style created', $style);

        return midcom_db_style::path_from_id($style->id);
    }
}
