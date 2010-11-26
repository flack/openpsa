<?php
/**
 * @package midcom.admin.libconfig
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: plugin.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Asgard plugin for creating /sitegroup-config/_component_/config snippets
 *
 *
 * @package midcom.admin.libconfig
 */
class midcom_admin_libconfig_plugin extends midcom_baseclasses_components_request
{
    /**
     * Get the plugin handlers, which act alike with Request Switches of MidCOM
     * Baseclasses Components (midcom.baseclasses.components.request)
     *
     * @access public
     * @return mixed Array of the plugin handlers
     */
    public function get_plugin_handlers()
    {
        $_MIDCOM->load_library('midgard.admin.asgard');
        $_MIDCOM->load_library('midcom.admin.libconfig');

        $_MIDCOM->auth->require_valid_user();

        return array
        (
            /**
             * List libraries
             *
             * Match /
             */
            'index' => array
            (
                'handler' => array ('midcom_admin_libconfig_handler_list', 'list'),
            ),
            /**
             * Edit library config
             *
             * Match /edit/<component>/
             */
            'edit' => array
            (
                'handler' => array ('midcom_admin_libconfig_handler_edit', 'edit'),
                'fixed_args' => array ('edit'),
                'variable_args' => 1,
            ),
            /**
             * Show current settings
             *
             * Match /view/<component>/
             */
            'view' => array
            (
                'handler' => array ('midcom_admin_libconfig_handler_view', 'view'),
                'fixed_args' => array ('view'),
                'variable_args' => 1,
            ),

        );
    }

    public function get_libraries()
    {
        $libs = array();

        foreach($_MIDCOM->componentloader->manifests as $name => $manifest)
        {
            if (!array_key_exists('package.xml', $manifest->_raw_data))
            {
                // This component is not yet packaged, skip
                continue;
            }

            if ($manifest->purecode)
            {
                $_MIDCOM->componentloader->load_graceful($name);
                $configpath = MIDCOM_ROOT . $_MIDCOM->componentloader->path_to_snippetpath($name)."/config/config.inc";
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
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $libs = midcom_admin_libconfig_plugin::get_libraries();

        echo '<ul class="midgard_admin_asgard_navigation">';

        foreach ($libs as $name => $manifest)
        {
            $label = $_MIDCOM->i18n->get_string($name, $name);
            echo "            <li class=\"status\"><a href=\"{$prefix}__mfa/asgard_midcom.admin.libconfig/view/{$name}/\">{$label}</a></li>\n";
        }

        echo "</ul>";

    }
}
?>