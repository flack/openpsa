<?php
use Symfony\Component\Finder\Finder;
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Folder management class.
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_management extends midcom_baseclasses_components_plugin
{
    /**
     * Anchor prefix stores the link back to the edited content topic
     *
     * @var string
     */
    private $_anchor_prefix = null;

    /**
     * Initializes the context data and toolbar objects
     */
    public function _on_initialize()
    {
        $config = $this->_request_data['plugin_config'];
        if ($config) {
            foreach ($config as $key => $value) {
                $this->$key = $value;
            }
        }
        $this->_anchor_prefix = $this->_request_data['plugin_anchorprefix'];

        // Ensure we get the correct styles
        midcom::get()->style->prepend_component_styledir('midcom.admin.folder');

        $this->_request_data['folder'] = $this->_topic;
    }

    /**
     * Get the plugin handlers, which act alike with Request Switches of MidCOM
     * Baseclasses Components (midcom.baseclasses.components.request)
     *
     * @return mixed Array of the plugin handlers
     */
    public function get_plugin_handlers()
    {
        $return = parent::get_plugin_handlers();

        if (midcom::get()->config->get('symlinks')) {
            /**
             * Create a new topic symlink
             *
             * Match /createlink/
             */
            $return['createlink'] = array(
                'handler' => array('midcom_admin_folder_handler_edit', 'edit'),
                'fixed_args' => array('createlink'),
            );
        }
        return $return;
    }

    /**
     * List names of the non-purecore components
     *
     * @param string $parent_component  Name of the parent component, which will pop the item first on the list
     * @return array containing names of the components
     */
    public static function get_component_list($parent_component = '')
    {
        $components = array();

        // Loop through the list of components of component loader
        foreach (midcom::get()->componentloader->manifests as $manifest) {
            // Skip purecode components
            if ($manifest->purecode) {
                continue;
            }

            // Skip components beginning with midcom or midgard
            if (   preg_match('/^(midcom|midgard)\./', $manifest->name)
                && $manifest->name != 'midcom.helper.search') {
                continue;
            }

            $components[$manifest->name] = array(
                'name'        => $manifest->get_name_translated(),
                'description' => midcom::get()->i18n->get_string($manifest->description, $manifest->name),
                'state'       => @$manifest->state,
                'version'     => $manifest->version,
            );
        }

        // Sort the components in alphabetical order (by key i.e. component class name)
        asort($components);

        // Set the parent component to be the first if applicable
        if (   $parent_component !== ''
            && array_key_exists($parent_component, $components)) {
            $temp = array();
            $temp[$parent_component] = $components[$parent_component];
            unset($components[$parent_component]);

            $components = array_merge($temp, $components);
        }

        return $components;
    }

    /**
     * Populate user interface for editing and creating topics
     *
     * @return array Containing a list of components
     */
    public static function list_components($parent_component = '', $all = false)
    {
        $list = array();

        $urltopics = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_URLTOPICS);
        if ($urltopic = end($urltopics)) {
            if (empty($urltopic->component)) {
                $list[''] = '';
            }
        }

        foreach (self::get_component_list() as $component => $details) {
            if (   $component !== $parent_component
                && !$all) {
                if (   is_array(midcom::get()->config->get('component_listing_allowed'))
                    && !in_array($component, midcom::get()->config->get('component_listing_allowed'))) {
                    continue;
                }

                if (   is_array(midcom::get()->config->get('component_listing_excluded'))
                    && in_array($component, midcom::get()->config->get('component_listing_excluded'))) {
                    continue;
                }
            }
            $list[$component] = "{$details['name']} ({$component} {$details['version']})";
        }

        return $list;
    }

    /**
     * List available style templates
     */
    public static function list_styles($up = 0, $prefix = '/', $spacer = '')
    {
        static $style_array = array();

        $style_array[''] = midcom::get()->i18n->get_string('default', 'midcom.admin.folder');

        // Give an option for creating a new layout template
        $style_array['__create'] = midcom::get()->i18n->get_string('new layout template', 'midcom.admin.folder');

        $qb = midcom_db_style::new_query_builder();
        $qb->add_constraint('up', '=', $up);
        $styles = $qb->execute();

        foreach ($styles as $style) {
            $style_string = "{$prefix}{$style->name}";

            // Hide common unwanted material with heuristics
            if (preg_match('/(asgard|empty)/i', $style_string)) {
                continue;
            }

            $style_array[$style_string] = "{$spacer}{$style->name}";
            self::list_styles($style->id, $style_string . '/', $spacer . '&nbsp;&nbsp;');
        }

        return self::list_theme_styles($style_array);
    }

    public static function list_theme_styles(array $styles)
    {
        $theme_styledir = OPENPSA2_THEME_ROOT . '/' . midcom::get()->config->get('theme') . '/style';
        $finder = new Finder();
        foreach ($finder->directories()->in($theme_styledir) as $dir) {
            $label = preg_replace('/.+?\//', '&nbsp;&nbsp;', $dir->getRelativePathname());
            $styles['theme:/' . $dir->getRelativePathname()] = $label;
        }
        return $styles;
    }

    /**
     * Checks if the folder has finite (healthy) tree below it.
     *
     * @param object $topic The folder to be checked.
     * @return boolean Indicating success
     */
    public static function is_child_listing_finite($topic, $stop = array())
    {
        if (!empty($topic->symlink)) {
            midcom::get()->auth->request_sudo('midcom.admin.folder');
            try {
                $topic = new midcom_db_topic($topic->symlink);
            } catch (midcom_error $e) {
                debug_add("Could not get target for symlinked topic #{$topic->id}: " .
                          $e->getMessage(), MIDCOM_LOG_ERROR);
            }
            midcom::get()->auth->drop_sudo();
        }

        if (in_array($topic->id, $stop)) {
            return false;
        }

        $stop[] = $topic->id;

        midcom::get()->auth->request_sudo('midcom.admin.folder');
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('up', '=', $topic->id);
        $results = $qb->execute();
        midcom::get()->auth->drop_sudo();

        foreach ($results as $topic) {
            if (!self::is_child_listing_finite($topic, $stop)) {
                return false;
            }
        }

        return true;
    }
}
