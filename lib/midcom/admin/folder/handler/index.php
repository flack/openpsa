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
class midcom_admin_folder_handler_index extends midcom_baseclasses_components_handler
{
    /**
     * The handler for the index article.
     *
     * @param mixed $handler_id the array key from the request array
     * @param array $args the arguments given to the handler
     * @param Array &$data The local request data.
     * @return boolean Indicating success.
     */
    public function _handler_index ($handler_id, array $args, array &$data)
    {
        $data['name']  = "midcom.admin.folder";

        $this->add_breadcrumb("/", $this->_l10n->get('index'));

        $title = $this->_l10n_midcom->get('index');
        midcom::get('head')->set_pagetitle(":: {$title}");

        $data['sort_order'] = $this->_config->get('sort_order');
    }

    /**
     * This function does the output.
     */
    public function _show_index()
    {
        midcom_show_style('index');
    }
}
?>