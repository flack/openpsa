<?php
/**
 * @package midcom.admin.libconfig
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: view.php 22990 2009-07-23 15:46:03Z flack $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Listing libraries handler class
 *
 * @package midcom.admin.libconfig
 */
class midcom_admin_libconfig_handler_view extends midcom_baseclasses_components_handler
{
    /**
     * Simple constructor
     *
     * @access public
     */
    function __construct()
    {
        parent::__construct();
    }

    function _on_initialize()
    {
        $this->_l10n = $_MIDCOM->i18n->get_l10n('midcom.admin.libconfig');
        $this->_request_data['l10n'] = $this->_l10n;

        $_MIDCOM->add_link_head
        (
            array
            (
                'rel' => 'stylesheet',
                'type' => 'text/css',
                'href' => MIDCOM_STATIC_URL . '/midcom.admin.libconfig/style.css',
            )
        );

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.libconfig'), $this->_request_data);
    }

    private function _update_breadcrumb($name)
    {
        // Populate breadcrumb
        $label = $_MIDCOM->i18n->get_string($name,$name);
        $tmp = Array();
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.libconfig/",
            MIDCOM_NAV_NAME => $this->_request_data['view_title'],
        );

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.libconfig/view/{$name}",
            MIDCOM_NAV_NAME => $label,
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    private function _prepare_toolbar(&$data)
    {
        $data['asgard_toolbar']->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "__mfa/asgard_midcom.admin.libconfig/edit/{$data['name']}",
                MIDCOM_TOOLBAR_LABEL => $_MIDCOM->i18n->get_string('edit', 'midcom'),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/edit.png',
            )
        );

        midgard_admin_asgard_plugin::get_common_toolbar($data);
    }

    /**
     * @param mixed $handler_id The ID of the handler.
     * @param Array $args The argument list.
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_view($handler_id, $args, &$data)
    {
        $data['name'] = $args[0];
        if (!array_key_exists($data['name'],$_MIDCOM->componentloader->manifests))
        {
            return false;
        }

        $componentpath = MIDCOM_ROOT . $_MIDCOM->componentloader->path_to_snippetpath($data['name']);

        // Load and parse the global config
        $cfg = midcom_baseclasses_components_interface::read_array_from_file("{$componentpath}/config/config.inc");
        if (! $cfg)
        {
            // hmmm... that should never happen
            $cfg = array();
        }

        $config = new midcom_helper_configuration($cfg);

        // Go for the sitewide default
        $cfg = midcom_baseclasses_components_interface::read_array_from_file("/etc/midgard/midcom/{$data['name']}/config.inc");
        if ($cfg !== false)
        {
            $config->store($cfg, false);
        }

        // Finally, check the sitegroup config
        $cfg = midcom_baseclasses_components_interface::read_array_from_snippet("{$GLOBALS['midcom_config']['midcom_sgconfig_basedir']}/{$data['name']}/config");
        if ($cfg !== false)
        {
            $config->store($cfg, false);
        }

        $data['config'] =& $config;

        $this->_update_breadcrumb($data['name']);
        $this->_prepare_toolbar($data);
        $_MIDCOM->set_pagetitle($data['view_title']);

        return true;
    }

    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    function _show_view($handler_id, &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();

        midcom_show_style('midcom-admin-libs-view-header');
        $data['even'] = false;
        foreach($data['config']->_global as $key => $value)
        {
            $data['key'] = $_MIDCOM->i18n->get_string($key,$data['name']);
            $data['global'] = $this->_detect($data['config']->_global[$key]);

            if (isset($data['config']->_local[$key]))
            {
                $data['local'] = $this->_detect($data['config']->_local[$key]);
            }
            else
            {
                $data['local'] = $this->_detect(null);
            }

            midcom_show_style('midcom-admin-libs-view-item');
            if (!$data['even'])
            {
                $data['even'] = true;
            }
            else
            {
                $data['even'] = false;
            }

        }
        midcom_show_style('midcom-admin-libs-view-footer');
        midgard_admin_asgard_plugin::asgard_footer();

    }

    function _detect($value)
    {

        $type = gettype($value);

        switch ($type)
        {
            case "boolean":

                $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png';
                $result = "<img src='{$src}'/>";

                if ($value === true)
                {
                    $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/ok.png';
                    $result = "<img src='{$src}'/>";
                }

                break;
            case "array":
                $content = '';
                foreach ($value as $key => $val)
                {
                    $content .= "<li>{$key} => ".$this->_detect($val).",</li>";
                }
                $result = "<ul>array<br />(<br />{$content}),</ul>";


                break;
            case "object":
                $result = "<strong>Object</strong>";
                break;
            case "NULL":
                $src = MIDCOM_STATIC_URL . '/stock-icons/16x16/cancel.png';
                $result = "<img src='{$src}'/>";
                $result = "<strong>N/A</strong>";
                break;
            default:
                $result = $value;

        }

        return $result;

    }

}
?>