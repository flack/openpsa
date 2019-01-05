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
     * Handler for listing users
     *
     * @param array &$data Data passed to the show method
     */
    public function _handler_index(array &$data)
    {
        $qb = org_openpsa_slideshow_image_dba::new_query_builder();
        $qb->add_constraint('topic', '=', $this->_topic->id);
        $qb->add_order('position');
        $data['images'] = $qb->execute();

        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('component', '=', $this->_component);
        $qb->add_constraint('up', '=', $this->_topic->id);
        $qb->add_order('metadata.score', 'ASC');
        $qb->set_limit(1);
        $data['has_subfolders'] = ($qb->count() > 0);

        $head = midcom::get()->head;
        $head->add_stylesheet(MIDCOM_STATIC_URL . '/' . $this->_component . '/slideshow.css');
        if (!empty($data['images'])) {
            $data['entries'] = org_openpsa_slideshow_image_dba::get_imagedata($data['images']);
            $head->enable_jquery();
            $head->add_jsfile(MIDCOM_STATIC_URL . '/' . $this->_component . '/galleria/galleria-1.3.5.min.js');
        }
        $buttons = [
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('edit'),
                MIDCOM_TOOLBAR_LABEL => sprintf($this->_l10n_midcom->get('edit %s'), $this->_l10n->get('slideshow')),
                MIDCOM_TOOLBAR_GLYPHICON => 'pencil',
            ],
            [
                MIDCOM_TOOLBAR_URL => $this->router->generate('recreate_folder_thumbnails'),
                MIDCOM_TOOLBAR_LABEL => $this->_l10n->get('recreate subfolder thumbnails'),
                MIDCOM_TOOLBAR_GLYPHICON => 'refresh',
            ]
        ];
        $this->_view_toolbar->add_items($buttons);
    }

    /**
     * Show list of the users
     *
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_index($handler_id, array &$data)
    {
        if (!empty($data['images'])) {
            midcom_show_style('index');
        } else {
            midcom_show_style('index-empty');
        }
    }

    /**
     * Handler for listing users
     *
     * @param array &$data Data passed to the show method
     */
    public function _handler_subfolders(array &$data)
    {
        $qb = midcom_db_topic::new_query_builder();
        $qb->add_constraint('component', '=', $this->_component);
        $qb->add_constraint('up', '=', $this->_topic->id);
        $qb->add_order('metadata.score', 'ASC');
        $data['subfolders'] = $qb->execute();

        $data['thumbnails'] = $this->_get_folder_thumbnails($data['subfolders']);
    }

    private function _get_folder_thumbnails($folders)
    {
        $thumbnails = [];
        foreach ($folders as $i => $folder) {
            $thumbnails[$i] = org_openpsa_slideshow_image_dba::get_folder_thumbnail($folder);
        }
        return $thumbnails;
    }

    /**
     * Show list of the users
     *
     * @param string $handler_id Name of the used handler
     * @param array &$data Data passed to the show method
     */
    public function _show_subfolders($handler_id, array &$data)
    {
        if (!empty($data['subfolders'])) {
            midcom_show_style('index-subfolders');
        } else {
            midcom_show_style('index-empty');
        }
    }
}
