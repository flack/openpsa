<?php
/**
 * @package midcom.admin.libconfig
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Asgard plugin for creating /sitegroup-config/_component_/config snippets
 *
 *
 * @package midcom.admin.libconfig
 */
class midcom_admin_libconfig_plugin extends midcom_baseclasses_components_plugin
{
    /**
     * Get the plugin handlers, which act alike with Request Switches of MidCOM
     * Baseclasses Components (midcom.baseclasses.components.request)
     *
     * @return mixed Array of the plugin handlers
     */
    public function _on_initialize()
    {
        $_MIDCOM->load_library('midgard.admin.asgard');
        $_MIDCOM->load_library('midcom.admin.libconfig');

        midcom::get('auth')->require_valid_user();
    }

    public function get_libraries()
    {
        $libs = array();

        foreach (midcom::get('componentloader')->manifests as $name => $manifest)
        {
            if (!array_key_exists('package.xml', $manifest->_raw_data))
            {
                // This component is not yet packaged, skip
                continue;
            }

            if ($manifest->purecode)
            {
                midcom::get('componentloader')->load_graceful($name);
                $configpath = MIDCOM_ROOT . midcom::get('componentloader')->path_to_snippetpath($name)."/config/config.inc";
                $lib = midcom_baseclasses_components_configuration::read_array_from_file("{$configpath}");

                if (!$lib)
                {
                    continue;
                }

                $libs[$name] = $manifest;
            }
        }
        return $libs;
    }

    public function navigation()
    {
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        $libs = midcom_admin_libconfig_plugin::get_libraries();

        echo '<ul class="midgard_admin_asgard_navigation">';

        foreach ($libs as $name => $manifest)
        {
            $label = midcom::get('i18n')->get_string($name, $name);
            echo "            <li class=\"status\"><a href=\"{$prefix}__mfa/asgard_midcom.admin.libconfig/view/{$name}/\">{$label}</a></li>\n";
        }

        echo "</ul>";
    }
}
?>