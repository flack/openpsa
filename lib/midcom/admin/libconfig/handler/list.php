<?php
/**
 * @package midcom.admin.libconfig
 * @author The Midgard Project, http://www.midgard-project.org 
 * @version $Id: list.php 22990 2009-07-23 15:46:03Z flack $
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
    var $_libs = array();

    /**
     * Simple constructor
     * 
     * @access public
     */
    public function __construct()
    {
        parent::__construct();
    }

    public function _on_initialize()
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

        midgard_admin_asgard_plugin::prepare_plugin($this->_l10n->get('midcom.admin.libconfig'),$this->_request_data);

    }

    
    private function _update_breadcrumb()
    {
        // Populate breadcrumb
        $tmp = Array();
        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "__mfa/asgard_midcom.admin.libconfig/",
            MIDCOM_NAV_NAME => $this->_request_data['view_title'],
        );
        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }

    private function _prepare_toolbar(&$data)
    {
        midgard_admin_asgard_plugin::get_common_toolbar($data);
    }
    
    /**
     * Handler method for listing style elements for the currently used component topic
     *
     * @access private
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     * @return boolean Indicating successful request
     */
    public function _handler_list($handler_id, $args, &$data)
    {   

        $this->_libs = midcom_admin_libconfig_plugin::get_libraries();
        $this->_update_breadcrumb();
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
    public function _show_list($handler_id, &$data)
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