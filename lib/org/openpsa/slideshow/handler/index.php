<?php
/**
 * @package org.openpsa.slideshow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Index handler
 *
 * @package org.openpsa.slideshow
 */
class org_openpsa_slideshow_handler_index extends midcom_baseclasses_components_handler
{
    /**
     * Handler method for listing users
     *
     * @param string $handler_id Name of the used handler
     * @param mixed $args Array containing the variable arguments passed to the handler
     * @param mixed &$data Data passed to the show method
     */
    public function _handler_index($handler_id, array $args, array &$data)
    {
        $qb = org_openpsa_slideshow_image_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_order('position');
        $data['images'] = $qb->execute();

        $head = midcom::get('head');
        $head->enable_jquery();
        $head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/galleria/galleria-1.2.6.min.js');

        $this->_view_toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => "edit/",
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('slideshow')),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/configuration.png',
            )
        );
    }

    /**
     * Show list of the users
     *
     * @param string $handler_id Name of the used handler
     * @param mixed &$data Data passed to the show method
     */
    public function _show_index($handler_id, array &$data)
    {
        if (sizeof($data['images']) > 0 )
        {
            midcom_show_style('index');
        }
        else
        {
            midcom_show_style('index-empty');
        }
    }
}
?>