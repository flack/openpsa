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
 * @property integer $topic
 * @property string $title
 * @property string $description
 * @property integer $position
 * @property integer $attachment
 * @property integer $thumbnail
 * @property integer $image
 * @package org.openpsa.slideshow
 */
class org_openpsa_slideshow_image_dba extends midcom_core_dbaobject
{
    const FOLDER_THUMBNAIL = 'folder_thumbnail';

    public string $__midcom_class_name__ = __CLASS__;
    public string $__mgdschema_class_name__ = 'org_openpsa_slideshow_image';

    public function _on_created()
    {
        $this->_check_folder_thumbnail();
    }

    public function _on_updated()
    {
        $this->_check_folder_thumbnail();
    }

    public function _on_deleted()
    {
        $this->_check_folder_thumbnail();
    }

    private function _check_folder_thumbnail()
    {
        if ($this->position > 0) {
            return;
        }

        try {
            $folder = midcom_db_topic::get_cached($this->topic);
        } catch (midcom_error $e) {
            $e->log();
        }
        $folder->delete_attachment(self::FOLDER_THUMBNAIL);
    }

    public function load_attachment(string $type) : ?midcom_db_attachment
    {
        try {
            return new midcom_db_attachment($this->$type);
        } catch (midcom_error $e) {
            $e->log();
            return null;
        }
    }

    public function generate_image(string $type, string $filter_chain) : bool
    {
        $original = $this->load_attachment('attachment');
        if (!$original) {
            return false;
        }
        $is_new = false;
        $derived = $this->load_attachment($type);
        if (!$derived) {
            $is_new = true;
            $derived = new midcom_db_attachment;
            $derived->parentguid = $original->parentguid;
            $derived->title = $original->title;
            $derived->mimetype = $original->mimetype;
            $derived->name = $type . '_' . $original->name;
        }

        $imagefilter = new midcom_helper_imagefilter($original);
        $imagefilter->process_chain($filter_chain);
        if ($is_new) {
            if (!$derived->create()) {
                throw new midcom_error('Failed to create derived image: ' . midcom_connection::get_error_string());
            }
            $this->$type = $derived->id;
            $this->update();
        }
        return $imagefilter->write($derived);
    }

    public static function get_folder_thumbnail(midcom_db_topic $folder) : ?midcom_db_attachment
    {
        $thumbnail = $folder->get_attachment(self::FOLDER_THUMBNAIL);
        if (empty($thumbnail)) {
            $qb = self::new_query_builder();
            $qb->add_constraint('topic', '=', $folder->id);
            $qb->add_order('position');
            $qb->set_limit(1);
            $results = $qb->execute();
            if (empty($results)) {
                return null;
            }
            midcom::get()->auth->request_sudo('org.openpsa.slideshow');
            $thumbnail = $results[0]->create_folder_thumbnail();
            midcom::get()->auth->drop_sudo();
        }
        return $thumbnail;
    }

    public function create_folder_thumbnail() : ?midcom_db_attachment
    {
        $original = $this->load_attachment('attachment');
        if (!$original) {
            return null;
        }
        $folder = midcom_db_topic::get_cached($this->topic);
        $thumbnail = new midcom_db_attachment;
        $thumbnail->parentguid = $folder->guid;
        $thumbnail->title = $original->title;
        $thumbnail->mimetype = $original->mimetype;
        $thumbnail->name = self::FOLDER_THUMBNAIL;

        $imagefilter = new midcom_helper_imagefilter($original);
        $config = midcom_baseclasses_components_configuration::get('org.openpsa.slideshow', 'config');

        $filter_chain = $config->get('folder_thumbnail_filter');
        $imagefilter->process_chain($filter_chain);
        if (!$thumbnail->create()) {
            throw new midcom_error('Failed to create folder thumbnail: ' . midcom_connection::get_error_string());
        }
        if (!$imagefilter->write($thumbnail)) {
            throw new midcom_error('Failed to write folder thumbnail: ' . midcom_connection::get_error_string());
        }
        return $thumbnail;
    }

    public static function get_imagedata(array $images) : array
    {
        $data = [];
        if (empty($images)) {
            return $data;
        }
        $ids = array_merge(array_column($images, 'attachment'), array_column($images, 'image'), array_column($images, 'thumbnail'));

        $mc = midcom_db_attachment::new_collector();
        $mc->add_constraint('id', 'IN', $ids);
        $rows = $mc->get_rows(['id', 'name', 'guid'], 'id');

        foreach ($images as $image) {
            if (!isset($rows[$image->attachment], $rows[$image->image], $rows[$image->thumbnail])) {
                continue;
            }

            $orig_data = $rows[$image->attachment];
            $image_data = $rows[$image->image];
            $thumb_data = $rows[$image->thumbnail];
            $data[] = [
                'big' => midcom_db_attachment::get_url($orig_data['guid'], $orig_data['name']),
                'image' => midcom_db_attachment::get_url($image_data['guid'], $image_data['name']),
                'thumb' => midcom_db_attachment::get_url($thumb_data['guid'], $thumb_data['name']),
                'title' => $image->title,
                'description' => $image->description
            ];
        }

        return $data;
    }
}
