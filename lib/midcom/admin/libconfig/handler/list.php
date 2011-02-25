<?php
/**
 * @package midcom.admin.libconfig
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Listing libraries handler class
 *
 * @package midcom.admin.libconfig
 */
class midcom_admin_libconfig_handler_list extends midcom_baseclasses_components_handler
{
    private $_libs = array();

    public function _on_initialize()
    {
        $this->add_stylesheet(MIDCOM_STATIC_URL . '/midcom.admin.libconfig/style.css');

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.libconfig'), $this->_request_data);
    }

    /**
     * Handler method for listing style elements for the currently used component topic
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     * @return boolean Indicating successful request
     */
    public function _handler_list($handler_id, array $args, array &$data)
    {
        $this->_libs = midcom_admin_libconfig_plugin::get_libraries();
        $this->add_breadcrumb("__mfa/asgard_midcom.admin.libconfig/", $data['view_title']);
        $_MIDCOM->set_pagetitle($data['view_title']);
    }

    /**
     * Show list of the style elements for the currently edited topic component
     *
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    public function _show_list($handler_id, array &$data)
    {
        midgard_admin_asgard_plugin::asgard_header();
        $data['config'] =& $this->_config;

        midcom_show_style('midcom-admin-libs-list-header');

        $data['even'] = false;
        foreach ($this->_libs as $name => $lib)
        {
            $data['name'] = $name;
            midcom_show_style('midcom-admin-libs-list-item');
            if (!$data['even'])
            {
                $data['even'] = true;
            }
            else
            {
                $data['even'] = false;
            }
        }

        midcom_show_style('midcom-admin-libs-list-footer');
        midgard_admin_asgard_plugin::asgard_footer();
    }
}
?>