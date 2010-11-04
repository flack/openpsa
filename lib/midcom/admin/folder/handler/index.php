<?php
/**
 * @author tarjei huse
 * @package midcom.admin.folder
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 *
 */

/**
 * Created on 2006-Sep-Sun
 *
 * The midcom_baseclasses_components_handler class defines a bunch of helper vars
 *
 * @see midcom_baseclasses_components_handler
 * @package midcom.admin.folder
 */
class midcom_admin_folder_handler_index  extends midcom_baseclasses_components_handler
{

    /**
     * Simple default constructor.
     */
    function __construct()
    {
        parent::__construct();
    }
    /**
     * _on_initialize is called by midcom on creation of the handler.
     */
    function _on_initialize()
    {
    }

    /**
     * The handler for the index article.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    function _handler_index ($handler_id, $args)
    {
        $this->_request_data['name']  = "midcom.admin.folder";
        // the handler must return true
        /***
         * Set the breadcrumb text
         */
        $this->_update_breadcrumb_line($handler_id);
        /**
         * change the pagetitle. (must be supported in the style)
         */
        $title = $this->_l10n_midcom->get('index');
        $_MIDCOM->set_pagetitle(":: {$title}");
        /**
         * Example of getting a config var.
         */
        $this->_request_data['sort_order'] = $this->_config->get('sort_order');
        return true;
    }

    /**
     * This function does the output.
     *
     */
    function _show_index() {
        // hint: look in the style/index.php file to see what happens here.
        midcom_show_style('index');
    }

    /**
     * Helper, updates the context so that we get a complete breadcrumb line towards the current
     * location.
     *
     */
    function _update_breadcrumb_line()
    {
        $tmp = Array();

        $tmp[] = Array
        (
            MIDCOM_NAV_URL => "/",
            MIDCOM_NAV_NAME => $this->_l10n->get('index'),
        );

        $_MIDCOM->set_custom_context_data('midcom.helper.nav.breadcrumb', $tmp);
    }
}
?>