<?php
/**
 * @package midcom.admin.babel
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package midcom.admin.babel
 */
class midcom_admin_babel_plugin extends midcom_baseclasses_components_handler
{
    function get_plugin_handlers()
    {

        $_MIDCOM->load_library('midgard.admin.asgard');
        $_MIDCOM->load_library('midcom.admin.babel');

        $_MIDCOM->auth->require_user_do('midcom.admin.babel:access', null, 'midcom_admin_babel_plugin');

        return array
        (
           'select' => array
            (
                'handler' => Array('midcom_admin_babel_handler_process', 'select'),
            ),
            'status' => array
            (
                'handler' => Array('midcom_admin_babel_handler_process', 'status'),
                'fixed_args' => 'status',
                'variable_args' => 1,
            ),
            'edit' => array
            (
                'handler' => Array('midcom_admin_babel_handler_process', 'edit'),
                'fixed_args' => 'edit',
                'variable_args' => 2,
            ),
            'save' => array
            (
                'handler' => Array('midcom_admin_babel_handler_process', 'save'),
                'fixed_args' => 'save',
                'variable_args' => 2,
            ),
        );
    }


    function calculate_language_status($lang)
    {
        if (    in_array('midcom_admin_babel_lang_status', $GLOBALS['midcom_config']['cache_module_memcache_data_groups'])
             && $_MIDCOM->cache->memcache->exists('midcom_admin_babel_lang_status', $lang))
        {
            return $_MIDCOM->cache->memcache->get('midcom_admin_babel_lang_status', $lang);
        }
        $status = array
        (
            'components_core' => array(),
            'components_other' => array(),
            'strings_all' => array
            (
                'total'      => 0,
                'translated' => 0,
            ),
            'strings_core' => array
            (
                'total'      => 0,
                'translated' => 0,
            ),
            'strings_other' => array
            (
                'total'      => 0,
                'translated' => 0,
            )
        );

        $components = array('midcom');

        // Load translation status of each component
        foreach ($_MIDCOM->componentloader->manifests as $manifest)
        {
            $components[] = $manifest->name;
        }

        foreach ($components as $component)
        {
            $component_l10n = $_MIDCOM->i18n->get_l10n($component);

            if ($_MIDCOM->componentloader->is_core_component($component))
            {
                $string_array = 'components_core';
            }
            else
            {
                $string_array = 'components_other';
            }

            $status[$string_array][$component] = array();

            $string_ids = array_unique($component_l10n->get_all_string_ids());

            $status[$string_array][$component]['total'] = count($string_ids);
            $status['strings_all']['total'] += $status[$string_array][$component]['total'];

            if ($string_array == 'components_core')
            {
                $status['strings_core']['total'] += $status[$string_array][$component]['total'];
            }
            else
            {
                $status['strings_other']['total'] += $status[$string_array][$component]['total'];
            }

            $status[$string_array][$component]['translated'] = 0;

            foreach ($string_ids as $id)
            {
                if ($component_l10n->string_exists($id, $lang))
                {
                    $status[$string_array][$component]['translated']++;
                    $status['strings_all']['translated']++;

                    if ($_MIDCOM->componentloader->is_core_component($component))
                    {
                        $status['strings_core']['translated']++;
                    }
                    else
                    {
                        $status['strings_other']['translated']++;
                    }
                }
            }
        }

        if (in_array('midcom_admin_babel_lang_status', $GLOBALS['midcom_config']['cache_module_memcache_data_groups']))
        {
            $_MIDCOM->cache->memcache->put('midcom_admin_babel_lang_status', $lang, $status);
        }
        return $status;
    }

    function navigation()
    {
        $prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
        $languages = $_MIDCOM->i18n->get_language_db();
        $curlang = $_MIDCOM->i18n->get_current_language();

        echo "<ul class=\"midgard_admin_asgard_navigation\">\n";

        foreach ($languages as $language => $language_info)
        {
            $language_name = $_MIDCOM->i18n->get_string($language_info['enname'], 'midcom.admin.babel');

            // Calculate status
            $state = midcom_admin_babel_plugin::calculate_language_status($language);
            $percentage = round(100 / $state['strings_core']['total'] * $state['strings_core']['translated']);
            $percentage_other = round(100 / $state['strings_other']['total'] * $state['strings_other']['translated']);

            if ($percentage >= 96)
            {
                $status = 'ok';
            }
            elseif ($percentage >= 75)
            {
                $status = 'acceptable';
            }
            else
            {
                $status = 'bad';
            }

            echo "            <li class=\"status\"><a href=\"{$prefix}__mfa/asgard_midcom.admin.babel/status/{$language}/\">{$language_name} <span class=\"metadata\">({$percentage}%/{$percentage_other}%)</span></a></li>\n";
        }

        echo "</ul>\n";

    }

}

?>