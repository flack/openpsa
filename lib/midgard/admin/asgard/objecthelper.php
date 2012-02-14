<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Object helper methods
 *
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_objecthelper extends midgard_admin_asgard_navigation
{
    public static function get_help($data)
    {
        $help_element = null;
        if (   !isset($data['object'])
            || !$data['object']->id)
        {
            return;
        }

        if (midcom::get('dbfactory')->is_a($data['object'], 'midgard_style'))
        {
            $help_element = midgard_admin_asgard_objecthelper::get_help_style($data);
        }

        if (midcom::get('dbfactory')->is_a($data['object'], 'midgard_element'))
        {
            $help_element = midgard_admin_asgard_objecthelper::get_help_element($data);
        }

        if ($help_element)
        {
            midcom_show_style('midgard_admin_asgard_objecthelper_' . $help_element);
        }
    }

    public static function get_help_style($data)
    {
        if ($data['handler_id'] == '____mfa-asgard-object_create')
        {
            // Check what type we're creating
            if ($data['new_type_arg'] == 'midgard_element')
            {
                // We should provide suggestions on element names
                return midgard_admin_asgard_objecthelper::get_help_style_elementnames($data['object']);
            }

            // We have no suitable help for creating substyles
            return;
        }

        // Provide a handy list of element names we can create
        return midgard_admin_asgard_objecthelper::get_help_style_elementnames($data['object']);
    }

    public static function get_help_element($data)
    {
        if (   empty($data['object']->name)
            || empty($data['object']->style))
        {
            // We cannot help with empty elements
            return;
        }

        if ($data['handler_id'] == '____mfa-asgard-object_create')
        {
            // We don't have help for anything you create under an element
            return;
        }

        if ($data['object']->name == 'ROOT')
        {
            $data = $_MIDCOM->get_custom_context_data('request_data');
            $element_path = MIDCOM_ROOT . $_MIDCOM->componentloader->path_to_snippetpath('midgard.admin.asgard') . '/documentation/ROOT.php';
            $data['help_style_element'] = array
            (
                'component' => 'midcom',
                'default'   => file_get_contents($element_path),
            );
            $_MIDCOM->set_custom_context_data('request_data', $data);
            return 'style_element';
        }

        // Find the element we're looking for
        $style_path = $_MIDCOM->style->get_style_path_from_id($data['object']->style);
        $style_elements = $_MIDCOM->style->get_style_elements_and_nodes($style_path);
        $element_path = null;
        $element_component = null;
        foreach ($style_elements['elements'] as $component => $elements)
        {
            if (isset($elements[$data['object']->name]))
            {
                $element_path = $elements[$data['object']->name];
                $element_component = $component;
                break;
            }
        }

        if (!$element_path)
        {
            return;
        }

        $data = $_MIDCOM->get_custom_context_data('request_data');
        $data['help_style_element'] = array
        (
            'component' => $element_component,
            'default'   => file_get_contents($element_path),
        );
        $_MIDCOM->set_custom_context_data('request_data', $data);
        return 'style_element';
    }

    /**
     * Helper for suggesting element names to create under a style
     */
    public static function get_help_style_elementnames(midcom_db_style $style)
    {
        $style_path = $_MIDCOM->style->get_style_path_from_id($style->id);
        $data = $_MIDCOM->get_custom_context_data('request_data');
        $data['help_style_elementnames'] = $_MIDCOM->style->get_style_elements_and_nodes($style_path);
        $_MIDCOM->set_custom_context_data('request_data', $data);
        return 'style_elementnames';
    }
}
?>
