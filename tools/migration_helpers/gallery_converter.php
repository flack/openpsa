<?php
/**
 * Converter script for ragna o.r.gallery topics to o.o.slideshow
 */

class gallery_converter
{
    private $_node;

    public function __construct()
    {
        if (   !class_exists('org_routamc_gallery_photolink')
            || !class_exists('org_routamc_photostream_photo')) {
            throw new midcom_error('MgdSchemas for the converter could not be found');
        }
    }

    public function execute()
    {
        $qb = new midgard_query_builder('midgard_topic');
        $qb->add_constraint('component', '=', 'org.routamc.gallery');
        $results = $qb->execute();
        foreach ($results as $result) {
            $this->_process_gallery_node($result);
        }
        $qb = new midgard_query_builder('midgard_topic');
        $qb->add_constraint('component', '=', 'org.routamc.photostream');
        $results = $qb->execute();
        foreach ($results as $result) {
            $this->_process_photostream_node($result);
        }
    }

    private function _process_gallery_node($node)
    {
        $this->_node = $node;

        $this->_output('Processing gallery node ' . $this->_node->name);
        $links = $this->_get_photolinks();
        $this->_output('Found ' . sizeof($links) . ' photolinks');

        foreach ($links as $i => $link) {
            $photo = new org_routamc_photostream_photo($link->photo);
            $slide = new org_openpsa_slideshow_image();
            $slide->title = $photo->title;
            $slide->description = $photo->description;
            $slide->topic = $link->node;
            $slide->attachment = $photo->archival;
            $slide->image = $photo->main;
            $slide->thumbnail = $photo->thumb;
            $slide->position = $i;
            if (!$slide->create()) {
                throw new midcom_error('Could not save new image: ' . midcom_connection::get_error_string());
            }
            $link->delete();
        }

        $this->_node->component = 'org.openpsa.slideshow';
        if (!$this->_node->update()) {
            throw new midcom_error('Could not update node: ' . midcom_connection::get_error_string());
        }
        $this->_output('Done.');
    }

    private function _process_photostream_node($node)
    {
        $this->_node = $node;

        $this->_output('Processing photostream node ' . $this->_node->name);
        $photos = $this->_get_photos();
        $this->_output('Found ' . sizeof($photos) . ' photos');

        $i = 0;
        foreach ($photos as $photo) {
            $qb = new midgard_query_builder('org_openpsa_slideshow_image');
            $qb->add_constraint('attachment', '=', $photo->archival);
            if ($qb->count() > 0) {
                $photo->delete();
                continue;
            }
            $slide = new org_openpsa_slideshow_image();
            $slide->title = $photo->title;
            $slide->description = $photo->description;
            $slide->topic = $this->_node->id;
            $slide->attachment = $photo->archival;
            $slide->image = $photo->main;
            $slide->thumbnail = $photo->thumb;
            $slide->position = $i;
            if (!$slide->create()) {
                throw new midcom_error('Could not save new image: ' . midcom_connection::get_error_string());
            }

            $i++;
        }

        if ($i > 0) {
            $this->_node->component = 'org.openpsa.slideshow';
            if (!$this->_node->update()) {
                throw new midcom_error('Could not update node: ' . midcom_connection::get_error_string());
            }
        } else {
            $this->_node->delete();
        }
        $this->_output('Done.');
    }

    private function _output($message)
    {
        echo $message . "\n";
        flush();
    }

    private function _get_photos()
    {
        $qb = new midgard_query_builder('org_routamc_photostream_photo');
        $qb->add_constraint('node', '=', $this->_node->id);
        return $qb->execute();
    }

    private function _get_photolinks()
    {
        $qb = new midgard_query_builder('org_routamc_gallery_photolink');
        $qb->add_constraint('node', '=', $this->_node->id);
        $orders = (array) $this->_node->get_parameter('org.routamc.gallery', 'nav_order');

        if (sizeof($orders) == 0) {
            $orders = [
                'metadata.score reverse',
                'photo.taken reverse',
            ];
        }

        foreach ($orders as $order) {
            // Probably an empty string
            if (!preg_match('/^([^\s]+)\s*(.*)/', $order, $regs)) {
                continue;
            }

            if (preg_match('/(rev|desc)/i', $regs[2])) {
                $qb->add_order($regs[1], 'DESC');
            } else {
                $qb->add_order($regs[1]);
            }
        }

        return $qb->execute();
    }
}
