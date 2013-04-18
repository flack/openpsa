<?php
/**
 * @package org.openpsa.slideshow
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Image DBA class
 *
 * @package org.openpsa.slideshow
 */
class org_openpsa_slideshow_image_dba extends midcom_core_dbaobject
{
    public $__midcom_class_name__ = __CLASS__;
    public $__mgdschema_class_name__ = 'org_openpsa_slideshow_image';

    public function generate_image($type, $filter_chain)
    {
        try
        {
            $original = new midcom_db_attachment($this->attachment);
        }
        catch (midcom_error $e)
        {
            $e->log();
            return false;
        }
        $found_derived = false;
        try
        {
            $derived = new midcom_db_attachment($this->$type);
            $found_derived = true;
        }
        catch (midcom_error $e)
        {
            $derived = new midcom_db_attachment;
            $derived->parentguid = $original->parentguid;
            $derived->title = $original->title;
            $derived->mimetype = $original->mimetype;
            $derived->name = $type . '_' . $original->name;
        }

        $imagefilter = new midcom_helper_imagefilter($original);

        if (!$imagefilter->process_chain($filter_chain))
        {
            throw new midcom_error('Image processing failed');
        }
        if (!$found_derived)
        {
            if (!$derived->create())
            {
                throw new midcom_error('Failed to create derived image: ' . midcom_connection::get_error_string());
            }
            $this->$type = $derived->id;
            $this->update();
        }
        return $imagefilter->write($derived);
    }

    public static function get_imagedata(array $images)
    {
        $data = array();
        if (empty($images))
        {
            return $data;
        }
        $image_guids = array();
        foreach ($images as $image)
        {
            $image_guids[] = $image->guid;
        }
        if (empty($image_guids))
        {
            return $data;
        }
        $mc = midcom_db_attachment::new_collector('metadata.deleted', false);
        $mc->add_constraint('parentguid', 'IN', $image_guids);
        $rows = $mc->get_rows(array('id', 'name', 'guid'), 'id');
        foreach ($images as $image)
        {
            if (   !isset($rows[$image->attachment])
                || !isset($rows[$image->image])
                || !isset($rows[$image->thumbnail]))
            {
                continue;
            }
            $orig_data = $rows[$image->attachment];
            $image_data = $rows[$image->image];
            $thumb_data = $rows[$image->thumbnail];
            $data[] = array
            (
                'big' => midcom_db_attachment::get_url($orig_data['guid'], $orig_data['name']),
                'image' => midcom_db_attachment::get_url($image_data['guid'], $image_data['name']),
                'thumb' => midcom_db_attachment::get_url($thumb_data['guid'], $thumb_data['name']),
                'title' => (string) $image->title,
                'description' => (string) $image->description
            );
        }
        return $data;
    }

}
?>