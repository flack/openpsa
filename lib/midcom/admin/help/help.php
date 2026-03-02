<?php
/**
 * @package midcom.admin.help
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midgard\portable\storage\connection;

/**
 * Online help display
 *
 * @package midcom.admin.help
 */
class midcom_admin_help_help extends midcom_baseclasses_components_plugin
{
    private array $mgdtypes = [
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

    public function _on_initialize()
    {
        midcom::get()->auth->require_valid_user();

        midcom::get()->skip_page_style = true;
        // doing this here as this component most probably will not be called by itself.
        midcom::get()->style->prepend_component_styledir('midcom.admin.help');
    }

    /**
     * Get component's documentation directory path
     */
    private static function get_documentation_dir(string $component) : string
    {
        return midcom::get()->componentloader->path_to_snippetpath($component) . '/documentation/';
    }

    public static function generate_file_path(string $help_id, string $component, ?string $language = null) : ?string
    {
        $language ??= midcom::get()->i18n->get_current_language();

        $file = self::get_documentation_dir($component) . "{$help_id}.{$language}.txt";
        if (!file_exists($file)) {
            if ($language != midcom::get()->i18n->get_fallback_language()) {
                // Try MidCOM's default fallback language
                return self::generate_file_path($help_id, $component, midcom::get()->i18n->get_fallback_language());
            }
            return null;
        }

        return $file;
    }

    private function get_help_title(string $help_id, string $component) : string
    {
        if ($path = self::generate_file_path($help_id, $component)) {
            $file_contents = file($path);
            if (trim($file_contents[0])) {
                return trim($file_contents[0]);
            }
        }

        return $this->_l10n->get("help_" . $help_id);
    }

    /**
     * Load the file from the component's documentation directory.
     */
    private function _load_file(string $help_id, string $component) : ?string
    {
        // Try loading the file
        if ($file = self::generate_file_path($help_id, $component)) {
            // Load the contents
            $help_contents = file_get_contents($file);

            // Replace static URLs (URLs for screenshots etc)
            return str_replace('MIDCOM_STATIC_URL', MIDCOM_STATIC_URL, $help_contents);
        }
        return null;
    }

    /**
     * Load a help file and markdownize it
     */
    public function get_help_contents(string $help_id, string $component) : ?string
    {
        if ($text = $this->_load_file($help_id, $component)) {
            $parsedown = new Parsedown();
            return $parsedown->text($text);
        }
        return null;
    }

    public function list_files(string $component, bool $with_index = false) : array
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

    private function _add_virtual_files(array $files, string $component) : array
    {
        $options = [
            'mgdschemas' => midcom::get()->dbclassloader->get_component_classes($component),
            'urlmethods' => $this->read_url_methods($component)
        ];
        if ($component != 'midcom') {
            $options['handlers'] = $this->read_component_handlers($component);
        }
        foreach (array_filter($options) as $key => $values) {
            $files[$key] = [
                'path' => '/' . $key,
                'subject' => $this->_l10n->get('help_' . $key),
                'lang' => 'en',
            ];
            $this->_request_data[$key] = $values;
        }

        return $files;
    }

    private function _list_physical_files(string $component) : array
    {
        $component_dir = self::get_documentation_dir($component);
        if (!is_dir($component_dir)) {
            return [];
        }

        $files = [];
        $pattern = $component_dir . '*.{' . $this->_i18n->get_current_language() . ',' . $this->_i18n->get_fallback_language() . '}.txt';

        foreach (glob($pattern, GLOB_NOSORT|GLOB_BRACE) as $path) {
            $entry = basename($path);
            if (   str_starts_with($entry, 'index')
                || str_starts_with($entry, 'handler')
                || str_starts_with($entry, 'urlmethod')) {
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

    private function read_component_handlers(string $component) : array
    {
        $data = [];

        $viewer = midcom::get()->componentloader->get_interface_class($component)->get_viewer(new midcom_db_topic);
        $routes = $viewer->get_router()->getRouteCollection()->all();
        foreach ($routes as $handler_id => $route) {
            $details = [
                'route' => preg_replace('/args_(\d+)/', 'args[\1]', $route->getPath())
            ];

            [$details['controller'], $details['action']] = explode('::', $route->getDefault('_controller'), 2);

            if (self::generate_file_path('handlers_' . $handler_id, $component)) {
                $details['info'] = $this->get_help_contents('handlers_' . $handler_id, $component);
                $details['handler_help_url'] = 'handlers_' . $handler_id;
            }
            $data[$handler_id] = $details;
        }

        return $data;
    }

    private function read_url_methods(string $component) : array
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
            $info_id = "urlmethod_" . str_replace('.php', '', $file);

            $data[$file] = [
                'url' => '/midcom-exec-' . $component . '/' . $file,
            ];

            if (self::generate_file_path($info_id, $component)) {
                $data[$file]['handler_help_url'] = $info_id;
                $data[$file]['description'] = $this->get_help_contents($info_id, $component);
            }
        }

        return $data;
    }

    private function read_schema_properties()
    {
        foreach (array_keys($this->_request_data['mgdschemas']) as $mgdschema_class) {
            $this->_request_data['properties'][$mgdschema_class] = [];
            $mrp = new midgard_reflection_property($mgdschema_class);
            $class_props = connection::get_em()->getClassMetadata($mgdschema_class)->get_schema_properties();

            foreach (array_diff($class_props, ['action', 'metadata']) as $prop) {
                $this->_request_data['properties'][$mgdschema_class][$prop] = [
                    'value' => $mrp->description($prop) ?? '',
                    'link' => $mrp->is_link($prop),
                    'link_name' => $mrp->get_link_name($prop),
                    'link_target' => $mrp->get_link_target($prop),
                    'midgard_type' => $this->mgdtypes[$mrp->get_midgard_type($prop)]
                ];
            }
        }
    }

    private function _prepare_breadcrumb(string $handler_id)
    {
        $this->add_breadcrumb($this->router->generate('welcome'), $this->_l10n->get('help'));

        if (in_array($handler_id, ['help', 'component'])) {
            $this->add_breadcrumb(
                $this->router->generate('component', ['component' => $this->_request_data['component']]),
                $this->_i18n->get_string($this->_request_data['component'], $this->_request_data['component'])
            );
        }
    }

    public function _handler_welcome(string $handler_id, array &$data)
    {
        $data['view_title'] = $this->_l10n->get($this->_component);
        midcom::get()->head->set_pagetitle($data['view_title']);

        $data['components'] = [];
        $data['libraries'] = [];

        foreach (midcom::get()->componentloader->get_manifests() as $manifest) {
            $type = $manifest->purecode ? 'libraries' : 'components';
            $title = $manifest->get_name_translated();
            if ($title == $manifest->name) {
                $title = '';
            }

            $data[$type][$manifest->name] = [
                'name' => $manifest->name,
                'title' => $title,
                'icon' => midcom::get()->componentloader->get_component_icon($manifest->name),
                'description' => $manifest->description,
            ];
        }

        asort($data['components']);
        asort($data['libraries']);

        $this->_prepare_breadcrumb($handler_id);
    }

    /**
     * Shows the help system main screen
     */
    public function _show_welcome(string $handler_id, array &$data)
    {
        midcom_show_style('midcom_admin_help_header');
        midcom_show_style('midcom_admin_help_about');
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

    public function _handler_component(string $handler_id, string $component, array &$data)
    {
        $data['component'] = $component;
        $data['view_title'] = sprintf($this->_l10n->get('help for %s'), $this->_i18n->get_string($component, $component));
        midcom::get()->head->set_pagetitle($data['view_title']);

        $data['help_files'] = $this->list_files($component);
        $data['html'] = $this->get_help_contents('index', $component);
        $this->_prepare_breadcrumb($handler_id);
    }

    /**
     * Shows the component help ToC.
     */
    public function _show_component(string $handler_id, array &$data)
    {
        midcom_show_style('midcom_admin_help_header');

        midcom_show_style('midcom_admin_help_show');
        midcom_show_style('midcom_admin_help_component');

        midcom_show_style('midcom_admin_help_footer');
    }

    public function _handler_help(string $handler_id, string $component, string $help_id, array &$data)
    {
        $data['help_id'] = $help_id;
        $data['component'] = $component;
        $data['help_files'] = $this->list_files($component);

        if ($help_id == 'mgdschemas') {
            $this->read_schema_properties();
        }
        $data['html'] = $this->get_help_contents($help_id, $component);

        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/midcom.admin.help/twisty.js');
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/stock-icons/font-awesome-4.7.0/css/font-awesome.min.css');

        // Table of contents navi
        $data['view_title'] = sprintf(
            $this->_l10n->get('help for %s in %s'),
            $this->get_help_title($help_id, $component),
            $this->_i18n->get_string($component, $component)
        );
        midcom::get()->head->set_pagetitle($data['view_title']);
        $this->_prepare_breadcrumb($handler_id);
        if (in_array($help_id, ['handlers', 'urlmethods', 'mgdschemas'])) {
            $this->add_breadcrumb("", $this->_l10n->get($help_id));
        } else {
            $this->add_breadcrumb("", $this->get_help_title($help_id, $component));
        }
    }

    /**
     * Shows the help page.
     */
    public function _show_help(string $handler_id, array &$data)
    {
        midcom_show_style('midcom_admin_help_header');
        midcom_show_style('midcom_admin_help_show');
        if (in_array($data['help_id'], ['handlers', 'mgdschemas', 'urlmethods'])) {
            midcom_show_style('midcom_admin_help_' . $data['help_id']);
        } elseif (!$data['html']) {
            $data['html'] = $this->get_help_contents('notfound', $this->_component);
            midcom_show_style('midcom_admin_help_show');
            midcom_show_style('midcom_admin_help_component');
        }
        midcom_show_style('midcom_admin_help_footer');
    }
}
