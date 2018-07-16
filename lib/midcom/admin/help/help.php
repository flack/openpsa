<?php
/**
 * @package midcom.admin.help
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Michelf\MarkdownExtra;
use midgard\portable\storage\connection;

/**
 * Online help display
 *
 * @package midcom.admin.help
 */
class midcom_admin_help_help extends midcom_baseclasses_components_plugin
{
    private $mgdtypes = [
        MGD_TYPE_STRING => "string",
        MGD_TYPE_INT => "integer",
        MGD_TYPE_UINT => "unsigned integer",
        MGD_TYPE_FLOAT => "float",
        MGD_TYPE_BOOLEAN => "boolean",
        MGD_TYPE_TIMESTAMP => "datetime",
        MGD_TYPE_LONGTEXT => "longtext",
        MGD_TYPE_GUID => "guid",
        MGD_TYPE_NONE => 'none'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.help/style-editor.css');

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.admin.help/twisty.js');
    }

    public function _on_initialize()
    {
        midcom::get()->skip_page_style = true;
        // doing this here as this component most probably will not be called by itself.
        midcom::get()->style->prepend_component_styledir('midcom.admin.help');
    }

    /**
     * Get component's documentation directory path
     *
     * @param string $component Component name
     * @return string Component documentation directory path
     */
    private static function get_documentation_dir($component)
    {
        if (!midcom::get()->componentloader->is_installed($component)) {
            throw new midcom_error("Failed to generate documentation path for component {$component} as it is not installed.");
        }
        return midcom::get()->componentloader->path_to_snippetpath($component) . '/documentation/';
    }

    public static function generate_file_path($help_id, $component, $language = null)
    {
        if ($language === null) {
            $language = midcom::get()->i18n->get_current_language();
        }

        $file = self::get_documentation_dir($component) . "{$help_id}.{$language}.txt";
        if (!file_exists($file)) {
            if ($language != midcom::get()->config->get('i18n_fallback_language')) {
                // Try MidCOM's default fallback language
                return self::generate_file_path($help_id, $component, midcom::get()->config->get('i18n_fallback_language'));
            }
            return null;
        }

        return $file;
    }

    private function get_help_title($help_id, $component)
    {
        if ($path = self::generate_file_path($help_id, $component)) {
            $file_contents = file($path);
            if (trim($file_contents[0])) {
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
        if (!$file) {
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
    public function get_help_contents($help_id, $component)
    {
        $text = $this->_load_file($help_id, $component);
        if (!$text) {
            return false;
        }
        return MarkdownExtra::defaultTransform($text);
    }

    public function list_files($component, $with_index = false)
    {
        $files = $this->_list_physical_files($component);
        $files = $this->_add_virtual_files($files, $component);

        ksort($files);
        // prepend 'index' URL if required
        if ($with_index) {
            $files = array_merge([
                'index' => [
                    'path' => '/',
                    'subject' => $this->_l10n->get('help_index'),
                    'lang' => 'en',
                ]],
                $files
            );
        }
        return $files;
    }

    private function _add_virtual_files($files, $component)
    {
        // Schemas
        $this->_request_data['mgdschemas'] = midcom::get()->dbclassloader->get_component_classes($component);
        if (count($this->_request_data['mgdschemas'])) {
            $files['mgdschemas'] = [
                'path' => '/mgdschemas',
                'subject' => $this->_l10n->get('help_mgdschemas'),
                'lang' => 'en',
            ];
        }

        // URL Methods
        $this->_request_data['urlmethods'] = $this->read_url_methods($component);
        if (count($this->_request_data['urlmethods'])) {
            $files['urlmethods'] = [
                'path' => '/urlmethods',
                'subject' => $this->_l10n->get('help_urlmethods'),
                'lang' => 'en',
            ];
        }

        // Break if dealing with MidCOM Core docs
        if ($component == 'midcom') {
            ksort($files);
            return $files;
        }

        // handlers
        $this->_request_data['request_switch_info'] = $this->read_component_handlers($component);
        if (count($this->_request_data['request_switch_info'])) {
            $files['handlers'] = [
                'path' => '/handlers',
                'subject' => $this->_l10n->get('help_handlers'),
                'lang' => 'en',
            ];
        }

        return $files;
    }

    private function _list_physical_files($component)
    {
        $component_dir = self::get_documentation_dir($component);
        if (!is_dir($component_dir)) {
            return [];
        }

        $files = [];
        $pattern = $component_dir . '*.{' . midcom::get()->i18n->get_current_language() . ',' . midcom::get()->config->get('i18n_fallback_language') . '}.txt';

        foreach (glob($pattern, GLOB_NOSORT|GLOB_BRACE) as $path) {
            $entry = basename($path);
            if (    substr($entry, 0, 5) == 'index'
                 || substr($entry, 0, 7) == 'handler'
                 || substr($entry, 0, 9) == 'urlmethod') {
                // Ignore dotfiles, handlers & index.lang.txt
                continue;
            }

            $filename_parts = explode('.', $entry);

            $files[$filename_parts[0]] = [
                'path' => $path,
                'subject' => $this->get_help_title($filename_parts[0], $component),
                'lang' => $filename_parts[1],
            ];
        }

        return $files;
    }

    private function read_component_handlers($component)
    {
        $data = [];

        $handler = midcom::get()->componentloader->get_interface_class($component);
        if (empty($handler->_context_data[midcom_core_context::get()->id]['handler'])) {
            return $data;
        }

        /** @var midcom_baseclasses_components_request $request */
        $request =& $handler->_context_data[midcom_core_context::get()->id]['handler'];
        $routes = $request->get_router()->getRouteCollection()->all();
        foreach ($routes as $request_handler_id => $route) {
            $details = [];

            // Build the dynamic_loadable URI, starting from topic path
            $details['route'] = str_replace(midcom_connection::get_url('prefix') . '/', '', midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX));

            // Add fixed arguments
            $details['route'] .= preg_replace('/args_(\d+)/', 'args[\1]', $route->getPath());
            list ($details['controller'], $details['action']) = explode('::', $route->getDefault('_controller'), 2);

            if (self::generate_file_path('handlers_' . $request_handler_id, $component)) {
                $details['info'] = $this->get_help_contents('handlers_' . $request_handler_id, $component);
                $details['handler_help_url'] = 'handlers_' . $request_handler_id;
            }
            $data[$request_handler_id] = $details;
        }

        return $data;
    }

    private function read_url_methods($component)
    {
        $data = [];

        $exec_path = midcom::get()->componentloader->path_to_snippetpath($component) . '/exec/';
        if (   !is_dir($exec_path)
            || !is_readable($exec_path)) {
            // Directory not accessible, skip loading it
            return $data;
        }

        foreach (glob($exec_path . '/*.php', GLOB_NOSORT) as $path) {
            $file = basename($path);
            $data[$file] = [];

            $info_id = "urlmethod_" . str_replace('.php', '', $file);

            $data[$file]['url'] = '/midcom-exec-' . $component . '/' . $file;
            $data[$file]['description'] = $this->get_help_contents($info_id, $component);

            if (self::generate_file_path($info_id, $component)) {
                $data[$file]['handler_help_url'] = $info_id;
            }
        }

        return $data;
    }

    private function read_schema_properties()
    {
        foreach (array_keys($this->_request_data['mgdschemas']) as $mgdschema_class) {
            $mrp = new midgard_reflection_property($mgdschema_class);
            $class_props = connection::get_em()->getClassMetadata($mgdschema_class)->get_schema_properties();

            unset($class_props['metadata']);
            $default_properties = [];
            $additional_properties = [];

            foreach ($class_props as $prop) {
                switch ($prop) {
                    case 'action':
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
        return [
            'value' => $mrp->description($prop),
            'link' => $mrp->is_link($prop),
            'link_name' => $mrp->get_link_name($prop),
            'link_target' => $mrp->get_link_target($prop),
            'midgard_type' => $this->mgdtypes[$mrp->get_midgard_type($prop)]
        ];
    }

    private function _load_component_data($name)
    {
        $component_array = [];
        $component_array['name'] = $name;
        $component_array['title'] = '';
        if (midcom::get()->i18n->get_l10n($name)->string_exists($name)) {
            $component_array['title'] = midcom::get()->i18n->get_string($name, $name);
        }
        $component_array['icon'] = midcom::get()->componentloader->get_component_icon($name);

        if (isset(midcom::get()->componentloader->manifests[$name])) {
            $manifest = midcom::get()->componentloader->manifests[$name];
            $component_array['purecode'] = $manifest->purecode;
            $component_array['description'] = $manifest->description;
        }
        return $component_array;
    }

    private function _list_components()
    {
        $this->_request_data['components'] = [];
        $this->_request_data['libraries'] = [];

        foreach (midcom::get()->componentloader->manifests as $name => $manifest) {
            $type = ($manifest->purecode) ? 'libraries' : 'components';

            $component_array = $this->_load_component_data($name);

            $this->_request_data[$type][$name] = $component_array;
        }

        asort($this->_request_data['components']);
        asort($this->_request_data['libraries']);
    }

    private function _prepare_breadcrumb($handler_id)
    {
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get('midcom.admin.help'));

        if (   $handler_id == 'help'
            || $handler_id == 'component') {
            $this->add_breadcrumb(
                $this->router->generate('component', ['component' => $this->_request_data['component']]),
                sprintf($this->_l10n->get('help for %s'), midcom::get()->i18n->get_string($this->_request_data['component'], $this->_request_data['component']))
            );
        }

        if ($handler_id == 'help') {
            if (   $this->_request_data['help_id'] == 'handlers'
                || $this->_request_data['help_id'] == 'urlmethods'
                || $this->_request_data['help_id'] == 'mgdschemas') {
                $this->add_breadcrumb("", $this->_l10n->get($this->_request_data['help_id']));
            } else {
                $this->add_breadcrumb("", $this->get_help_title($this->_request_data['help_id'], $this->_request_data['component']));
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
        $list_types = ['components', 'libraries'];

        foreach ($list_types as $list_type) {
            $data['list_type'] = $list_type;
            midcom_show_style('midcom_admin_help_list_header');
            foreach ($data[$list_type] as $component_data) {
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

        if (!midcom::get()->componentloader->is_installed($data['component'])) {
            throw new midcom_error_notfound("Component {$data['component']} is not installed.");
        }

        if ($data['component'] != 'midcom') {
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
        if (!midcom::get()->componentloader->is_installed($data['component'])) {
            throw new midcom_error_notfound("Component {$data['component']} is not installed.");
        }

        if ($data['component'] != 'midcom') {
            midcom::get()->componentloader->load($data['component']);
        }

        $data['help_files'] = $this->list_files($data['component']);

        if ($data['help_id'] == 'mgdschemas') {
            $this->read_schema_properties();
        }
        $data['html'] = $this->get_help_contents($data['help_id'], $data['component']);

        // Table of contents navi
        $data['view_title'] = sprintf(
            $this->_l10n->get('help for %s in %s'),
            $this->get_help_title($data['help_id'], $data['component']),
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
        switch ($this->_request_data['help_id']) {
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
            default:
                midcom_show_style('midcom_admin_help_show');

                if (!$this->_request_data['html']) {
                    $this->_request_data['html'] = $this->get_help_contents('notfound', 'midcom.admin.help');
                    midcom_show_style('midcom_admin_help_show');
                    midcom_show_style('midcom_admin_help_component');
                }
        }
        midcom_show_style('midcom_admin_help_footer');
    }
}
