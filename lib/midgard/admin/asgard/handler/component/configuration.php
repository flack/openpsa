<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Component configuration handler
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_handler_component_configuration extends midcom_baseclasses_components_handler
{
    use midgard_admin_asgard_handler;

    private $_controller;

    /**
     * @var midcom_db_topic
     */
    private $folder;

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midgard.admin.asgard/libconfig.css');
    }

    private function _prepare_toolbar(bool $is_view)
    {
        $view_url = $this->router->generate('components_configuration', ['component' => $this->_request_data['name']]);
        $edit_url = $this->router->generate('components_configuration_edit', ['component' => $this->_request_data['name']]);
        $buttons = [[
            MIDCOM_TOOLBAR_URL => $view_url,
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('view'),
            MIDCOM_TOOLBAR_GLYPHICON => 'eye',
            MIDCOM_TOOLBAR_ENABLED => !$is_view
        ], [
            MIDCOM_TOOLBAR_URL => $edit_url,
            MIDCOM_TOOLBAR_LABEL => $this->_l10n_midcom->get('edit'),
            MIDCOM_TOOLBAR_GLYPHICON => 'pencil',
            MIDCOM_TOOLBAR_ENABLED => $is_view
        ]];
        $this->_request_data['asgard_toolbar']->add_items($buttons);
    }

    /**
     * Set the breadcrumb data
     */
    private function _prepare_breadcrumbs()
    {
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get($this->_component));
        $this->add_breadcrumb($this->router->generate('components'), $this->_l10n->get('components'));

        $this->add_breadcrumb(
            $this->router->generate('components_component', ['component' => $this->_request_data['name']]),
            $this->_i18n->get_string($this->_request_data['name'], $this->_request_data['name'])
        );
        $this->add_breadcrumb(
            $this->router->generate('components_configuration', ['component' => $this->_request_data['name']]),
            $this->_l10n_midcom->get('component configuration')
        );
    }

    private function _load_configs(string $component) : midcom_helper_configuration
    {
        $config = midcom_baseclasses_components_configuration::get($component, 'config');

        if ($this->folder) {
            $topic_config = new midcom_helper_configuration($this->folder, $component);
            $config->store($topic_config->_local, false);
        }

        return $config;
    }

    public function _handler_view(string $component, array &$data)
    {
        $data['name'] = $component;
        $data['config'] = $this->_load_configs($data['name']);

        $data['view_title'] = sprintf($this->_l10n->get('configuration for %s'), $data['name']);
        $this->_prepare_toolbar(true);
        $this->_prepare_breadcrumbs();
        return $this->get_response();
    }

    public function _show_view(string $handler_id, array &$data)
    {
        midcom_show_style('midgard_admin_asgard_component_configuration_header');

        foreach ($data['config']->_global as $key => $value) {
            $data['key'] = $this->_i18n->get_string($key, $data['name']);
            $data['global'] = $this->render($value);

            if (isset($data['config']->_local[$key])) {
                $data['local'] = $this->render($data['config']->_local[$key]);
            } else {
                $data['local'] = $this->render(null);
            }

            midcom_show_style('midgard_admin_asgard_component_configuration_item');
        }
        midcom_show_style('midgard_admin_asgard_component_configuration_footer');
    }

    private function render($value)
    {
        $type = gettype($value);

        switch ($type) {
            case 'boolean':
                $result = '<i class="fa fa-' . ($value === true ? 'check' : 'times') . '"></i>';
                break;
            case 'array':
                $content = '<ul>';
                foreach ($value as $key => $val) {
                    $content .= "<li>{$key} => " . $this->render($val) . ",</li>\n";
                }
                $content .= '</ul>';
                $result = "<ul>\n<li>array</li>\n<li>(\n{$content}\n)</li>\n</ul>\n";
                break;
            case 'object':
                $result = '<strong>Object</strong>';
                break;
            case 'NULL':
                $result = '<strong>N/A</strong>';
                break;
            default:
                $result = $value;
        }

        return $result;
    }

    public function _handler_edit(Request $request, string $handler_id, array &$data, string $component, string $folder = null)
    {
        $data['name'] = $component;
        if ($folder) {
            $this->folder = new midcom_db_topic($folder);
            if ($this->folder->component != $data['name']) {
                throw new midcom_error_notfound("Folder {$folder} not found for configuration.");
            }
            $this->folder->require_do('midgard:update');
        }
        $data['config'] = $this->_load_configs($data['name']);

        $schemadb = (new midgard_admin_asgard_schemadb_config($component, $data['config'], isset($folder)))->create();
        $this->_controller = (new datamanager($schemadb))->get_controller();

        switch ($this->_controller->handle($request)) {
            case 'save':
                if (!$this->save_configuration($data)) {
                    midcom::get()->uimessages->add(
                        $this->_l10n_midcom->get('component configuration'),
                        sprintf($this->_l10n->get('configuration save failed: %s'), midcom_connection::get_error_string()),
                        'error'
                    );
                    // back to edit
                    break;
                }
                midcom::get()->uimessages->add(
                    $this->_l10n_midcom->get('component configuration'),
                    $this->_l10n->get('configuration saved successfully')
                );

                // FALL-THROUGH (i.e. relocate to view)

            case 'cancel':
                if ($this->folder) {
                    return new midcom_response_relocate($this->router->generate('object_view', ['guid' => $this->folder->guid]));
                }
                return new midcom_response_relocate($this->router->generate('components_configuration', ['component' => $data['name']]));
        }

        $data['controller'] = $this->_controller;

        if ($this->folder) {
            midgard_admin_asgard_plugin::bind_to_object($this->folder, $handler_id, $data);
            $data['view_title'] = sprintf($this->_l10n->get('edit configuration for %s folder %s'), $data['name'], $this->folder->extra);
        } else {
            $this->_prepare_toolbar(false);
            $data['view_title'] = sprintf($this->_l10n->get('edit configuration for %s'), $data['name']);
            $this->_prepare_breadcrumbs();
            $this->add_breadcrumb(
                $this->router->generate('components_configuration_edit', ['component' => $data['name']]),
                $this->_l10n_midcom->get('edit')
            );
        }

        return $this->get_response('midgard_admin_asgard_component_configuration_edit');
    }

    private function save_configuration(array $data) : bool
    {
        $raw = $this->_controller->get_datamanager()->get_content_raw();
        $values = [];

        foreach ($this->_request_data['config']->get_all() as $key => $val) {
            if (!isset($raw[$key]) || $raw[$key] === '') {
                continue;
            }
            $newval = $raw[$key];

            if (is_array($val)) {
                //try make sure entries have the same format before deciding if there was a change
                eval("\$newval = $newval;");
            }

            if ($newval != $val) {
                $values[$key] = $newval;
            }
        }

        if ($this->folder) {
            // Editing folder configuration
            return $this->save_topic($values);
        }
        return $this->save_snippet($values);
    }

    /**
     * Save configuration values to a topic as "serialized" array
     */
    private function save_snippet(array $values) : bool
    {
        $config = var_export($values, true);
        // Remove opening and closing array( ) lines, because that's the way midcom likes it
        $config = preg_replace('/^.*?\n/', '', $config);
        $config = preg_replace('/(\n.*?|\))$/', '', $config);

        $basedir = midcom::get()->config->get('midcom_sgconfig_basedir');
        $sg_snippetdir = new midcom_db_snippetdir();
        if (!$sg_snippetdir->get_by_path($basedir)) {
            // Create config snippetdir
            $sg_snippetdir = new midcom_db_snippetdir();
            $sg_snippetdir->name = $basedir;
            // remove leading slash from name
            $sg_snippetdir->name = preg_replace("/^\//", "", $sg_snippetdir->name);
            if (!$sg_snippetdir->create()) {
                throw new midcom_error("Failed to create snippetdir {$basedir}: " . midcom_connection::get_error_string());
            }
        }

        $lib_snippetdir = new midcom_db_snippetdir();
        if (!$lib_snippetdir->get_by_path("{$basedir}/{$this->_request_data['name']}")) {
            $lib_snippetdir = new midcom_db_snippetdir();
            $lib_snippetdir->up = $sg_snippetdir->id;
            $lib_snippetdir->name = $this->_request_data['name'];
            if (!$lib_snippetdir->create()) {
                throw new midcom_error("Failed to create snippetdir {$basedir}/{$lib_snippetdir->name}: " . midcom_connection::get_error_string());
            }
        }

        $snippet = new midcom_db_snippet();
        if (!$snippet->get_by_path("{$basedir}/{$this->_request_data['name']}/config")) {
            $sn = new midcom_db_snippet();
            $sn->snippetdir = $lib_snippetdir->id;
            $sn->name = 'config';
            $sn->code = $config;
            return $sn->create();
        }

        $snippet->code = $config;
        return $snippet->update();
    }

    /**
     * Save configuration values to a topic as parameters
     */
    private function save_topic(array $config) : bool
    {
        $success = true;
        foreach ($this->_request_data['config']->_global as $global_key => $global_value) {
            if (   isset($config[$global_key])
                && $config[$global_key] != $global_value) {
                continue;
                // Skip the ones we will set next
            }

            // Clear unset params
            if ($this->folder->get_parameter($this->_request_data['name'], $global_key)) {
                $success = $this->folder->delete_parameter($this->_request_data['name'], $global_key) && $success;
            }
        }

        foreach ($config as $key => $value) {
            if (   is_array($value)
                || is_object($value)) {
                /**
                 * See http://trac.midgard-project.org/ticket/1442
                 $topic->set_parameter($this->_request_data['name'], var_export($value, true));
                 */
                continue;
            }

            if ($value === false) {
                $value = '0';
            }
            $success = $this->folder->set_parameter($this->_request_data['name'], $key, $value) && $success;
        }
        return $success;
    }
}
