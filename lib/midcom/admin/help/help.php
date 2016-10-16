<?php
/**
 * @package midcom.admin.help
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Michelf\MarkdownExtra;
use midgard\introspection\helper;

/**
 * Online help display
 *
 * @package midcom.admin.help
 */
class midcom_admin_help_help extends midcom_baseclasses_components_plugin
{
    private $mgdtypes = array
    (
        MGD_TYPE_STRING => "string",
        MGD_TYPE_INT => "integer",
        MGD_TYPE_UINT => "unsigned integer",
        MGD_TYPE_FLOAT => "float",
        //MGD_TYPE_DOUBLE => "double",
        MGD_TYPE_BOOLEAN => "boolean",
        MGD_TYPE_TIMESTAMP => "datetime",
        MGD_TYPE_LONGTEXT => "longtext",
        MGD_TYPE_GUID => "guid",
    );

    public function __construct()
    {
        parent::__construct();
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.help/style-editor.css');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL.'/midcom.admin.help/twisty.js');
        if (defined('MGD_TYPE_NONE'))
        {
            $this->mgdtypes[MGD_TYPE_NONE] = 'none';
        }
    }

    public function _on_initialize()
    {
        midcom::get()->skip_page_style = true;
        // doing this here as this component most probably will not be called by itself.
        midcom::get()->style->prepend_component_styledir('midcom.admin.help');
    }

    static function check_component($component)
    {
        if (   !midcom::get()->componentloader->is_installed($component)
            && $component != 'midcom')
        {
            throw new midcom_error("Failed to generate documentation path for component {$component} as it is not installed.");
        }
    }

    /**
     * Get component's documentation directory path
     *
     * @param string $component Component name
     * @return string Component documentation directory path
     */
    static function get_documentation_dir($component)
    {
        if ($component == 'midcom')
        {
            return MIDCOM_ROOT . "/midcom/documentation/";
        }
        self::check_component($component);
        $component_dir = str_replace('.', '/', $component);
        return MIDCOM_ROOT . "/{$component_dir}/documentation/";
    }

    /**
     * Check if help file exists
     *
     * @param string $help_id Help name ID
     * @param string $component Component name
     * @return bool True of false
     */
    static function help_exists($help_id, $component)
    {
        if ($file = self::generate_file_path($help_id, $component))
        {
            return (file_exists($file));
        }

        return false;
    }

    static function generate_file_path($help_id, $component, $language = null)
    {
        if ($language === null)
        {
            $language = midcom::get()->i18n->get_current_language();
        }

        $file = self::get_documentation_dir($component) . "{$help_id}.{$language}.txt";
        if (!file_exists($file))
        {
            if ($language != midcom::get()->config->get('i18n_fallback_language'))
            {
                // Try MidCOM's default fallback language
                return self::generate_file_path($help_id, $component, midcom::get()->config->get('i18n_fallback_language'));
            }
            return null;
        }

        return $file;
    }

    static function get_help_title($help_id, $component)
    {
        if ($path = self::generate_file_path($help_id, $component))
        {
            $file_contents = file($path);
            if (trim($file_contents[0]))
            {
                return trim($file_contents[0]);
            }
        }

        return midcom::get()->i18n->get_string("help_" . $help_id, 'midcom.admin.help');
    }

    /**
     * Load the file from the component's documentation directory.
     */
    private function _load_file($help_id, $component)
    {
        // Try loading the file
        $file = self::generate_file_path($help_id, $component);
        if (!$file)
        {
            return false;
        }

        // Load the contents
        $help_contents = file_get_contents($file);

        // Replace static URLs (URLs for screenshots etc)
        return str_replace('MIDCOM_STATIC_URL', MIDCOM_STATIC_URL, $help_contents);
    }

    /**
     * Load a help file and markdownize it
     */
    function get_help_contents($help_id, $component)
    {
        $text = $this->_load_file($help_id, $component);
        if (!$text)
        {
            return false;
        }

        // Finding [callback:some_method_of_viewer]
        if (preg_match_all('/(\[callback:(.+?)\])/', $text, $regs))
        {
            foreach ($regs[1] as $i => $value)
            {
                if ($component != midcom_core_context::get()->get_key(MIDCOM_CONTEXT_COMPONENT))
                {
                    $text = str_replace($value, "\n\n    __Note:__ documentation part _{$regs[2][$i]}_ from _{$component}_ is unavailable in this MidCOM context.\n\n", $text);
                }
                else
                {
                    $method_name = "help_{$regs[2][$i]}";
                    if (method_exists($this->_master, $method_name))
                    {
                        $text = str_replace($value, $this->_master->$method_name(), $text);
                    }
                }
            }
        }

        return MarkdownExtra::defaultTransform($text);
    }

    public function list_files($component, $with_index = false)
    {
        $files = $this->_list_physical_files($component);
        $files = $this->_add_virtual_files($files, $component);

        ksort($files);
        // prepend 'index' URL if required
        if ($with_index)
        {
            $files = array_merge
            (
                array
                (
                    'index' => array
                    (
                        'path' => '/',
                        'subject' => $this->_l10n->get('help_index'),
                        'lang' => 'en',
                    ),
                ),
                $files
            );
        }
        return $files;
    }

    private function _add_virtual_files($files, $component)
    {
        // Schemas
        $this->_request_data['mgdschemas'] = midcom::get()->dbclassloader->get_component_classes($component);
        if (count($this->_request_data['mgdschemas']))
        {
            $files['mgdschemas'] = array
            (
                'path' => '/mgdschemas',
                'subject' => $this->_l10n->get('help_mgdschemas'),
                'lang' => 'en',
            );
        }

        // URL Methods
        $this->_request_data['urlmethods'] = $this->read_url_methods($component);
        if (count($this->_request_data['urlmethods']))
        {
            $files['urlmethods'] = array
            (
                'path' => '/urlmethods',
                'subject' => $this->_l10n->get('help_urlmethods'),
                'lang' => 'en',
            );
        }

        // Break if dealing with MidCOM Core docs
        if ($component == 'midcom')
        {
            ksort($files);
            return $files;
        }

        // handlers
        $this->_request_data['request_switch_info'] = $this->read_component_handlers($component);
        if (count($this->_request_data['request_switch_info']))
        {
            $files['handlers'] = array
            (
                'path' => '/handlers',
                'subject' => $this->_l10n->get('help_handlers'),
                'lang' => 'en',
            );
        }

        // Dependencies
        $this->_request_data['dependencies'] = midcom::get()->componentloader->get_component_dependencies($component);
        if (count($this->_request_data['dependencies']))
        {
            $files['dependencies'] = array
            (
                'path' => '/dependencies',
                'subject' => $this->_l10n->get('help_dependencies'),
                'lang' => 'en',
            );
        }
        return $files;
    }

    private function _list_physical_files($component)
    {
        $component_dir = self::get_documentation_dir($component);
        if (!is_dir($component_dir))
        {
            return array();
        }

        $files = array();
        $pattern = $component_dir . '*.{' . midcom::get()->i18n->get_current_language() . ',' . midcom::get()->config->get('i18n_fallback_language') . '}.txt';

        foreach (glob($pattern, GLOB_NOSORT|GLOB_BRACE) as $path)
        {
            $entry = basename($path);
            if (    substr($entry, 0, 5) == 'index'
                 || substr($entry, 0, 7) == 'handler'
                 || substr($entry, 0, 9) == 'urlmethod')
            {
                // Ignore dotfiles, handlers & index.lang.txt
                continue;
            }

            $filename_parts = explode('.', $entry);

            $files[$filename_parts[0]] = array
            (
                'path' => $path,
                'subject' => self::get_help_title($filename_parts[0], $component),
                'lang' => $filename_parts[1],
            );
        }

        return $files;
    }

    function read_component_handlers($component)
    {
        $data = array();

        // TODO: We're using "private" members here, better expose them through a method
        $handler = midcom::get()->componentloader->get_interface_class($component);
        $request =& $handler->_context_data[midcom_core_context::get()->id]['handler'];
        if (!isset($request->_request_switch))
        {
            // No request switch available, skip loading it
            return $data;
        }

        foreach ($request->_request_switch as $request_handler_id => $request_data)
        {
            if (substr($request_handler_id, 0, 12) == '____ais-help')
            {
                // Skip self
                continue;
            }

            $data[$request_handler_id] = array();

            // Build the dynamic_loadable URI, starting from topic path
            $data[$request_handler_id]['route'] = str_replace(midcom_connection::get_url('prefix'), '', midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX));
            // Add fixed arguments
            $data[$request_handler_id]['route'] .= implode('/', $request_data['fixed_args']) . '/';
            // Add variable_arguments
            $i = 0;
            while ($i < $request_data['variable_args'])
            {
                $data[$request_handler_id]['route'] .= '{$args[' . $i . ']}/';
                $i++;
            }

            if (is_array($request_data['handler']))
            {
                $data[$request_handler_id]['controller'] = $request_data['handler'][0];

                if (is_object($data[$request_handler_id]['controller']))
                {
                    $data[$request_handler_id]['controller'] = get_class($data[$request_handler_id]['controller']);
                }

                $data[$request_handler_id]['action'] = $request_data['handler'][1];
            }

            if (self::help_exists('handlers_' . $request_handler_id, $component))
            {
                $data[$request_handler_id]['info'] = self::get_help_contents('handlers_' . $request_handler_id, $component);
                $data[$request_handler_id]['handler_help_url'] = 'handlers_' . $request_handler_id;
            }
        }

        return $data;
    }

    function read_url_methods($component)
    {
        $data = array();

        if ($component == 'midcom')
        {
            $exec_path = MIDCOM_ROOT . '/midcom/exec';
        }
        else
        {
            $component_path = str_replace('.', '/', $component);
            $exec_path = MIDCOM_ROOT . '/' . $component_path . '/exec';
        }

        if (   !is_dir($exec_path)
            || !is_readable($exec_path))
        {
            // Directory not accessible, skip loading it
            return $data;
        }

        foreach (glob($exec_path . '/*.php', GLOB_NOSORT) as $path)
        {
            $file = basename($path);
            $data[$file] = array();

            $info_id = "urlmethod_" . str_replace('.php', '', $file);

            $data[$file]['url'] = '/midcom-exec-' . $component . '/' . $file;
            $data[$file]['description'] = self::get_help_contents($info_id, $component);

            if (self::help_exists($info_id, $component))
            {
                $data[$file]['handler_help_url'] = $info_id;
            }
        }

        return $data;
    }

    function read_schema_properties()
    {
        $helper = new helper;
        foreach (array_keys($this->_request_data['mgdschemas']) as $mgdschema_class)
        {
            $mrp = new midgard_reflection_property($mgdschema_class);
            $class_props = $helper->get_all_properties($mgdschema_class);
            unset($class_props['metadata']);
            $default_properties = array();
            $additional_properties = array();

            foreach ($class_props as $prop)
            {
                switch ($prop)
                {
                    case 'action':
                    case 'sid':
                        // Midgard-internal properties, skip
                        break;
                    case 'guid':
                    case 'id':
                        $default_properties[$prop] = $this->_get_property_data($mrp, $prop);
                        break;
                    default:
                        $additional_properties[$prop] = $this->_get_property_data($mrp, $prop);
                        break;
                }
            }
            ksort($default_properties);
            ksort($additional_properties);

            $this->_request_data['properties'][$mgdschema_class] = array_merge($default_properties, $additional_properties);
        }
    }

    private function _get_property_data(midgard_reflection_property $mrp, $prop)
    {
        return array
        (
            'value' => $mrp->description($prop),
            'link' => $mrp->is_link($prop),
            'link_name' => $mrp->get_link_name($prop),
            'link_target' => $mrp->get_link_target($prop),
            'midgard_type' => $this->mgdtypes[$mrp->get_midgard_type($prop)]
        );
    }

    private function _load_component_data($name)
    {
        $component_array = array();
        $component_array['name'] = $name;
        $component_array['title'] = midcom::get()->i18n->get_string($name, $name);
        $component_array['icon'] = midcom::get()->componentloader->get_component_icon($name);

        if (!isset(midcom::get()->componentloader->manifests[$name]))
        {
            return $component_array;
        }

        $manifest = midcom::get()->componentloader->manifests[$name];
        $component_array['purecode'] = $manifest->purecode;

        if (isset($manifest->_raw_data['package.xml']['description']))
        {
            $component_array['description'] = $manifest->_raw_data['package.xml']['description'];
        }
        else
        {
            $component_array['description'] = '';
        }

        $component_array['version'] = $manifest->_raw_data['version'];

        $component_array['maintainers'] = array();
        if (isset($manifest->_raw_data['package.xml']['maintainers']))
        {
            $component_array['maintainers'] = $manifest->_raw_data['package.xml']['maintainers'];
        }

        return $component_array;
    }

    private function _list_components()
    {
        $this->_request_data['core_components'] = array();
        $this->_request_data['components'] = array();
        $this->_request_data['libraries'] = array();
        $this->_request_data['core_libraries'] = array();

        $this->_request_data['core_components']['midcom'] = $this->_load_component_data('midcom');

        foreach (midcom::get()->componentloader->manifests as $name => $manifest)
        {
            if (!array_key_exists('package.xml', $manifest->_raw_data))
            {
                // This component is not yet packaged, skip
                continue;
            }

            $type = ($manifest->purecode) ? 'libraries' : 'components';

            if (midcom::get()->componentloader->is_core_component($name))
            {
                $type = 'core_' . $type;
            }

            $component_array = $this->_load_component_data($name);

            $this->_request_data[$type][$name] = $component_array;
        }

        asort($this->_request_data['core_components']);
        asort($this->_request_data['components']);
        asort($this->_request_data['libraries']);
        asort($this->_request_data['core_libraries']);
    }

    private function _prepare_breadcrumb($handler_id)
    {
        $this->add_breadcrumb("__ais/help/", $this->_l10n->get('midcom.admin.help'));

        if (   $handler_id == '____ais-help-help'
            || $handler_id == '____ais-help-component')
        {
            $this->add_breadcrumb
            (
                "__ais/help/{$this->_request_data['component']}/",
                sprintf($this->_l10n->get('help for %s'), midcom::get()->i18n->get_string($this->_request_data['component'], $this->_request_data['component']))
            );
        }

        if ($handler_id == '____ais-help-help')
        {
            if (   $this->_request_data['help_id'] == 'handlers'
                || $this->_request_data['help_id'] == 'dependencies'
                || $this->_request_data['help_id'] == 'urlmethods'
                || $this->_request_data['help_id'] == 'mgdschemas')
            {
                $this->add_breadcrumb
                (
                    "__ais/help/{$this->_request_data['component']}/{$this->_request_data['help_id']}",
                    $this->_l10n->get($this->_request_data['help_id'])
                );
            }
            else
            {
                $this->add_breadcrumb
                (
                    "__ais/help/{$this->_request_data['component']}/{$this->_request_data['help_id']}",
                    self::get_help_title($this->_request_data['help_id'], $this->_request_data['component'])
                );
            }
        }
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_welcome($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        $data['view_title'] = $this->_l10n->get($this->_component);
        midcom::get()->head->set_pagetitle($data['view_title']);

        $this->_list_components();

        $this->_prepare_breadcrumb($handler_id);
    }

    /**
     * Shows the help system main screen
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_welcome($handler_id, array &$data)
    {
        midcom_show_style('midcom_admin_help_header');
        $list_types = array('core_components','core_libraries','components','libraries');

        foreach ($list_types as $list_type)
        {
            $data['list_type'] = $list_type;
            midcom_show_style('midcom_admin_help_list_header');
            foreach ($data[$list_type] as $component_data)
            {
                $data['component_data'] = $component_data;
                midcom_show_style('midcom_admin_help_list_item');
            }
            midcom_show_style('midcom_admin_help_list_footer');
        }

        midcom_show_style('midcom_admin_help_footer');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_component($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        $data['component'] = $args[0];

        if (!midcom::get()->componentloader->is_installed($data['component']))
        {
            throw new midcom_error_notfound("Component {$data['component']} is not installed.");
        }

        if ($data['component'] != 'midcom')
        {
            midcom::get()->componentloader->load($data['component']);
        }

        $data['view_title'] = sprintf($this->_l10n->get('help for %s'), $this->_i18n->get_string($data['component'], $data['component']));
        midcom::get()->head->set_pagetitle($data['view_title']);

        $data['help_files'] = $this->list_files($data['component']);
        $data['html'] = $this->get_help_contents('index', $data['component']);
        $this->_prepare_breadcrumb($handler_id);
    }

    /**
     * Shows the component help ToC.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_component($handler_id, array &$data)
    {
        midcom_show_style('midcom_admin_help_header');

        midcom_show_style('midcom_admin_help_show');
        midcom_show_style('midcom_admin_help_component');

        midcom_show_style('midcom_admin_help_footer');
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_help($handler_id, array $args, array &$data)
    {
        midcom::get()->auth->require_valid_user();

        $data['help_id'] = $args[1];
        $data['component'] = $args[0];
        if (!midcom::get()->componentloader->is_installed($data['component']))
        {
            throw new midcom_error_notfound("Component {$data['component']} is not installed.");
        }

        if ($data['component'] != 'midcom')
        {
            midcom::get()->componentloader->load($data['component']);
        }

        $data['help_files'] = $this->list_files($data['component']);

        switch ($data['help_id'])
        {
            case 'mgdschemas':
                $this->read_schema_properties();
                // Fall through
            default:
                $data['html'] = $this->get_help_contents($data['help_id'], $data['component']);
        }

        // Table of contents navi
        $data['view_title'] = sprintf
        (
            $this->_l10n->get('help for %s in %s'),
            self::get_help_title($data['help_id'], $data['component']),
            $this->_i18n->get_string($data['component'], $data['component'])
        );
        midcom::get()->head->set_pagetitle($data['view_title']);
        $this->_prepare_breadcrumb($handler_id);
    }

    /**
     * Shows the help page.
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array &$data The local request data.
     */
    public function _show_help($handler_id, array &$data)
    {
        midcom_show_style('midcom_admin_help_header');
        switch($this->_request_data['help_id'])
        {
            case 'handlers':
                midcom_show_style('midcom_admin_help_handlers');
                break;
            case 'mgdschemas':
                midcom_show_style('midcom_admin_help_show');
                midcom_show_style('midcom_admin_help_mgdschemas');
                break;
            case 'urlmethods':
                midcom_show_style('midcom_admin_help_show');
                midcom_show_style('midcom_admin_help_urlmethods');
                break;
            case 'dependencies':
                midcom_show_style('midcom_admin_help_show');
                $data['list_type'] = 'dependencies';
                midcom_show_style('midcom_admin_help_list_header');
                foreach ($data['dependencies'] as $component)
                {
                    $data['component_data'] = $this->_load_component_data($component);
                    midcom_show_style('midcom_admin_help_list_item');
                }
                midcom_show_style('midcom_admin_help_list_footer');
                break;
            default:
                midcom_show_style('midcom_admin_help_show');

                if (!$this->_request_data['html'])
                {
                    $this->_request_data['html'] = $this->get_help_contents('notfound', 'midcom.admin.help');
                    midcom_show_style('midcom_admin_help_show');
                    midcom_show_style('midcom_admin_help_component');
                }
        }
        midcom_show_style('midcom_admin_help_footer');
    }
}
