<?php
/**
 * @package midcom.admin.folder
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\Finder\Finder;

/**
 * Folder management class.
 *
 * @package midcom.admin.folder
 */
class midcom_admin_folder_management extends midcom_baseclasses_components_plugin
{
    /**
     * Initializes the context data and toolbar objects
     */
    public function _on_initialize()
    {
        // Ensure we get the correct styles
        midcom::get()->style->prepend_component_styledir('midcom.admin.folder');

        $this->_request_data['folder'] = $this->_topic;
    }

    /**
     * List names of the non-purecore components
     */
    public static function get_component_list() : array
    {
        $components = [];

        // Loop through the list of components of component loader
        foreach (midcom::get()->componentloader->get_manifests() as $manifest) {
            // Skip purecode components
            if ($manifest->purecode) {
                continue;
            }

            // Skip components beginning with midcom or midgard
            if (   preg_match('/^(midcom|midgard)\./', $manifest->name)
                && $manifest->name != 'midcom.helper.search') {
                continue;
            }

            $components[$manifest->name] = [
                'name'        => $manifest->get_name_translated(),
                'description' => midcom::get()->i18n->get_string($manifest->description, $manifest->name)
            ];
        }

        // Sort the components in alphabetical order (by key i.e. component class name)
        asort($components);

        return $components;
    }

    /**
     * Populate user interface for editing and creating topics
     */
    public static function list_components(string $current_selection) : array
    {
        $list = [];
        $allowed = midcom::get()->config->get('component_listing_allowed', []);
        $excluded = midcom::get()->config->get('component_listing_excluded', []);

        foreach (self::get_component_list() as $component => $details) {
            if ($component !== $current_selection) {
                if (!empty($allowed) && !in_array($component, $allowed)) {
                    continue;
                }

                if (!empty($excluded) && in_array($component, $excluded)) {
                    continue;
                }
            }
            $list[$component] = "{$details['name']} ({$component})";
        }

        return $list;
    }

    /**
     * List available style templates
     */
    public static function list_styles(int $up = 0, string $prefix = '/', string $spacer = '') : array
    {
        static $style_array = [];

        $style_array[''] = midcom::get()->i18n->get_string('default', 'midcom.admin.folder');

        // Give an option for creating a new layout template
        $style_array['__create'] = midcom::get()->i18n->get_string('new layout template', 'midcom.admin.folder');

        $qb = midcom_db_style::new_query_builder();
        $qb->add_constraint('up', '=', $up);

        foreach ($qb->execute() as $style) {
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

    public static function list_theme_styles(array $styles) : array
    {
        $theme_styledir = OPENPSA2_THEME_ROOT . '/' . midcom::get()->config->get('theme') . '/style';
        if (is_dir($theme_styledir)) {
            $finder = new Finder();
            foreach ($finder->directories()->in($theme_styledir) as $dir) {
                $label = preg_replace('/.+?\//', '&nbsp;&nbsp;', $dir->getRelativePathname());
                $styles['theme:/' . $dir->getRelativePathname()] = $label;
            }
        }
        return $styles;
    }
}
