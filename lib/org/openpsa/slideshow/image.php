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

    static function new_query_builder()
    {
        return midcom::get('dbfactory')->new_query_builder(__CLASS__);
    }

    static function new_collector($domain, $value)
    {
        return midcom::get('dbfactory')->new_collector(__CLASS__, $domain, $value);
    }

    static function &get_cached($src)
    {
        return midcom::get('dbfactory')->get_cached(__CLASS__, $src);
    }

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

}
?>