<?php
/**
 * @package midgard.admin.asgard
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Plugin interface
 * @package midgard.admin.asgard
 */
class midgard_admin_asgard_plugin extends midcom_baseclasses_components_plugin
{
    public function _on_initialize()
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin');
        // Disable content caching
        midcom::get()->cache->content->no_cache();

        // Preferred language
        if (($language = self::get_preference('interface_language')))
        {
            $this->_i18n->set_language($language);
        }

        // Enable jQuery
        midcom::get()->head->enable_jquery();

        // Ensure we get the correct styles
        midcom::get()->style->prepend_component_styledir('midgard.admin.asgard');
        midcom::get()->skip_page_style = true;

        $this->_request_data['asgard_toolbar'] = new midgard_admin_asgard_toolbar();
        self::get_default_mode($this->_request_data);
    }

    /**
     * Static method other plugins may use
     *
     * @param string $title     Page title
     * @param array &$data      Local request data
     */
    public static function prepare_plugin($title, array &$data)
    {
        midcom::get()->auth->require_user_do('midgard.admin.asgard:access', null, 'midgard_admin_asgard_plugin');
        // Disable content caching
        midcom::get()->cache->content->no_cache();
        $data['view_title'] = $title;
        $data['asgard_toolbar'] = new midgard_admin_asgard_toolbar();
        self::get_default_mode($data);

        // Preferred language
        if (($language = self::get_preference('interface_language')))
        {
            midcom::get()->i18n->set_language($language);
        }

        midcom::get()->skip_page_style = true;
        midcom::get()->style->prepend_component_styledir('midgard.admin.asgard');
        midcom::get()->style->prepend_component_styledir(str_replace('asgard_', '', $data['plugin_name']));
    }

    public static function get_type_label($type)
    {
        return midcom_helper_reflector_tree::get($type)->get_class_label();
    }

    /**
     * Bind view to an object
     */
    public static function bind_to_object($object, $handler_id, array &$data)
    {
        // Tell our object to MidCOM
        midcom::get()->metadata->set_request_metadata($object->metadata->revised, $object->guid);
        $data['object_reflector'] = midcom_helper_reflector::get($object);
        $data['tree_reflector'] = midcom_helper_reflector_tree::get($object);

        $data['object'] = $object;

        // Populate toolbars
        if (midcom::get()->dbclassloader->is_midcom_db_object($object))
        {
            $context = midcom_core_context::get();

            // Bind the object to the metadata service
            midcom::get()->metadata->bind_metadata_to_object(MIDCOM_METADATA_VIEW, $object, $context->id);

            // These toolbars only work with DBA objects as they do ACL checks
            $view_toolbar = midcom::get()->toolbars->get_view_toolbar($context->id);
            $view_toolbar->bind_to($object);
            $data['asgard_toolbar']->bind_to_object($object, $handler_id, $data);
            self::_set_object_breadcrumb($object, $handler_id, $data);
        }

        self::set_pagetitle($object, $handler_id, $data);
    }

    public static function set_pagetitle($object, $handler_id, array &$data)
    {
        // Figure out correct title and language handling
        switch ($handler_id)
        {
            case '____mfa-asgard-object_edit':
                $title_string = midcom::get()->i18n->get_string('edit %s %s', 'midgard.admin.asgard');
                break;
            case '____mfa-asgard-object_metadata':
                $title_string = midcom::get()->i18n->get_string('metadata of %s %s', 'midgard.admin.asgard');
                break;
            case '____mfa-asgard-object_attachments':
            case '____mfa-asgard-object_attachments_edit':
                $title_string = midcom::get()->i18n->get_string('attachments of %s %s', 'midgard.admin.asgard');
                break;
            case '____mfa-asgard-object_parameters':
                $title_string = midcom::get()->i18n->get_string('parameters of %s %s', 'midgard.admin.asgard');
                break;
            case '____mfa-asgard-object_permissions':
                // Figure out label for the object's class
                switch (get_class($object))
                {
                    case 'midcom_db_topic':
                        $type = midcom::get()->i18n->get_string('folder', 'midgard.admin.asgard');
                        break;
                    default:
                        $type = $data['object_reflector']->get_class_label();
                }
                $title_string = sprintf(midcom::get()->i18n->get_string('permissions for %s %s', 'midgard.admin.asgard'), $type, midgard_admin_asgard_handler_object_permissions::resolve_object_title($object));
                break;
            case '____mfa-asgard-object_create':
                $title_string = sprintf(midcom::get()->i18n->get_string('create %s under %s', 'midgard.admin.asgard'), midgard_admin_asgard_plugin::get_type_label($data['current_type']), '%s %s');
                break;
            case '____mfa-asgard-object_delete':
                $title_string = midcom::get()->i18n->get_string('delete %s %s', 'midgard.admin.asgard');
                break;
            case '____mfa-asgard-object_rcs_history':
            case '____mfa-asgard-object_rcs_diff':
            case '____mfa-asgard-object_rcs_preview':
                $title_string = midcom::get()->i18n->get_string('revision history of %s %s', 'midgard.admin.asgard');
                break;
            default:
                $title_string = midcom::get()->i18n->get_string('%s %s', 'midgard.admin.asgard');
                break;
        }

        $label = $data['object_reflector']->get_object_label($object);
        $type_label = midgard_admin_asgard_plugin::get_type_label(get_class($object));
        $data['view_title'] = sprintf($title_string, $type_label, $label);
    }

    /**
     * Set the default object mode
     */
    public static function get_default_mode(array &$data)
    {
        //only set mode once per request
        if (!empty($data['default_mode']))
        {
            return $data['default_mode'];
        }
        $data['default_mode'] = 'view';

        if (   !midgard_admin_asgard_plugin::get_preference('edit_mode')
            && midcom_baseclasses_components_configuration::get('midgard.admin.asgard', 'config')->get('edit_mode') == 1)
        {
            $data['default_mode'] = 'edit';
        }
        elseif (midgard_admin_asgard_plugin::get_preference('edit_mode') == 1)
        {
            $data['default_mode'] = 'edit';
        }

        return $data['default_mode'];
    }

    /**
     * Construct urls for the breadcrumbs
     *
     * @param string $action The action
     * @param string $guid The GUID
     */
    private static function _generate_url($action, $guid)
    {
        return '__mfa/asgard/object/' . $action . '/' . $guid . '/';
    }

    /**
     * Populate the object breadcrumb
     *
     * @param mixed $object        MgdSchema object for which the toolbar will be created
     * @param String $handler_id   Initialized handler id
     * @param array $data          Local request data
     */
    private static function _set_object_breadcrumb($object, $handler_id, array $data)
    {
        $tmp = array();

        $breadcrumb = array();
        $label = $data['object_reflector']->get_object_label($object);
        $breadcrumb[] = array
        (
            MIDCOM_NAV_URL => self::_generate_url('view', $object->guid),
            MIDCOM_NAV_NAME => $label,
        );

        $parent = $object->get_parent();

        if (   is_a($object, 'midcom_db_parameter')
            && is_object($parent)
            && $parent->guid)
        {
            // Add "parameters" list to breadcrumb if we're in a param
            $breadcrumb[] = array
            (
                MIDCOM_NAV_URL => self::_generate_url('parameters', $parent->guid),
                MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('parameters', 'midcom'),
            );
        }

        $i = 0;
        while (   is_object($parent)
               && $parent->guid
               && $i < 10)
        {
            $i++;
            $parent_reflector = midcom_helper_reflector::get($parent);
            $parent_label = $parent_reflector->get_object_label($parent);
            $breadcrumb[] = array
            (
                MIDCOM_NAV_URL => self::_generate_url('view', $parent->guid),
                MIDCOM_NAV_NAME => $parent_label,
            );
            $parent = $parent->get_parent();
        }
        $breadcrumb = array_reverse($breadcrumb);

        switch ($handler_id)
        {
            case '____mfa-asgard-object_edit':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('edit', $object->guid),
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('edit', 'midcom'),
                );
                break;
            case '____mfa-asgard-object_copy':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('copy', $object->guid),
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('copy', 'midcom'),
                );
                break;
            case '____mfa-asgard-object_copy_tree':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('copy/tree', $object->guid),
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('copy', 'midcom'),
                );
                break;
            case '____mfa-asgard-components_configuration_edit_folder':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => "__mfa/asgard/components/configuration/edit/{$object->component}/{$object->guid}/",
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('component configuration', 'midcom'),
                );
                break;
            case '____mfa-asgard-object_metadata':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('metadata', $object->guid),
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('metadata', 'midcom'),
                );
                break;
            case '____mfa-asgard-object_attachments':
            case '____mfa-asgard-object_attachments_edit':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('attachments', $object->guid),
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('attachments', 'midgard.admin.asgard'),
                );

                if ($handler_id == '____mfa-asgard-object_attachments_edit')
                {
                    $breadcrumb[] = array
                    (
                        MIDCOM_NAV_URL => "__mfa/asgard/object/attachments/{$object->guid}/edit/",
                        MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('edit', 'midcom'),
                    );
                }
                break;
            case '____mfa-asgard-object_parameters':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('parameters', $object->guid),
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('parameters', 'midcom'),
                );
                break;
            case '____mfa-asgard-object_permissions':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('permissions', $object->guid),
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('privileges', 'midcom'),
                );
                break;
            case '____mfa-asgard-object_create':
                if ($data['current_type'] == 'midgard_parameter')
                {
                    // Add "parameters" list to breadcrumb if we're creating a param
                    $breadcrumb[] = array
                    (
                        MIDCOM_NAV_URL => self::_generate_url('parameters', $object->guid),
                        MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('parameters', 'midcom'),
                    );
                }
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('create' . $data['current_type'], $object->guid),
                    MIDCOM_NAV_NAME => sprintf(midcom::get()->i18n->get_string('create %s', 'midcom'), midgard_admin_asgard_plugin::get_type_label($data['current_type'])),
                );
                break;
            case '____mfa-asgard-object_delete':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => self::_generate_url('delete', $object->guid),
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('delete', 'midcom'),
                );
                break;
            case '____mfa-asgard_midcom.helper.replicator-object':
                $breadcrumb[] = array
                (
                    MIDCOM_NAV_URL => "__mfa/asgard_midcom.helper.replicator/object/{$object->guid}/",
                    MIDCOM_NAV_NAME => midcom::get()->i18n->get_string('replication information', 'midcom.helper.replicator'),
                );
                break;
            case '____mfa-asgard-object_rcs_diff':
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "__mfa/asgard/object/rcs/preview/{$object->guid}/{$data['compare_revision']}/{$data['latest_revision']}",
                    MIDCOM_NAV_NAME => sprintf($data['l10n']->get('differences between %s and %s'), $data['compare_revision'], $data['latest_revision']),
                );

            case '____mfa-asgard-object_rcs_preview':
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "__mfa/asgard/object/rcs/preview/{$object->guid}/{$data['latest_revision']}/",
                    MIDCOM_NAV_NAME => sprintf($data['l10n']->get('version %s'), $data['latest_revision']),
                );

            case '____mfa-asgard-object_rcs_history':
                $tmp[] = array
                (
                    MIDCOM_NAV_URL => "__mfa/asgard/object/rcs/{$object->guid}/",
                    MIDCOM_NAV_NAME => $data['l10n']->get('show history'),
                );

                $tmp = array_reverse($tmp);

                $breadcrumb = array_merge($breadcrumb, $tmp);

                break;
        }

        midcom_core_context::get()->set_custom_key('midcom.helper.nav.breadcrumb', $breadcrumb);
    }

    /**
     * Get a preference for the current user
     *
     * @param string $preference    Name of the preference
     */
    public static function get_preference($preference)
    {
        static $preferences = array();

        if (!midcom::get()->auth->user)
        {
            return;
        }

        if (!isset($preferences[$preference]))
        {
            $person = midcom_db_person::get_cached(midcom::get()->auth->user->guid);

            $preferences[$preference] = $person->get_parameter('midgard.admin.asgard:preferences', $preference);
        }

        return $preferences[$preference];
    }

    /**
     * Get the MgdSchema root classes
     *
     * @return array containing class name and translated name
     */
    public static function get_root_classes()
    {
        static $root_classes = array();

        // Return cached results
        if (!empty($root_classes))
        {
            return $root_classes;
        }

        // Initialize the returnable array
        $root_classes = array();

        // Get the classes
        $classes = midcom_helper_reflector_tree::get_root_classes();

        // Get the translated name
        foreach ($classes as $class)
        {
            $ref = new midcom_helper_reflector($class);
            $root_classes[$class] = $ref->get_class_label();
        }

        asort($root_classes);

        return $root_classes;
    }
}
