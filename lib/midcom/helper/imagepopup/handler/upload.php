<?php

/**
 * @author lukasz chalat
 * @package midcom.helper.imagepopup
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * This handler get uploaded file and save it in database and write to file.
 *
 * @package midcom.helper.imagepopup
 */
class midcom_helper_imagepopup_handler_upload extends midcom_baseclasses_components_handler
{
    /**
     *
     * @param mixed $handler_id The ID of the handler.
     * @param array $args The argument list.
     * @param array &$data The local request data.
     */
    public function _handler_upload($handler_id, array $args, array &$data)
    {
        // Get the file
        reset($_FILES);
        $temp = array_shift($_FILES);

        if(is_uploaded_file($temp['tmp_name']))
        {
            // Verify file extension
            if(!in_array(strtolower(pathinfo($temp['name'], PATHINFO_EXTENSION)), array("gif", "jpg", "png")))
            {
                throw new midcom_error('Invalid extension.');
            }
        }

        // Get the data
        $temp_name = $temp['tmp_name'];
        $mimetype = $temp['type'];
        $parentguid = "";

        // Do we have guid ?
        if($handler_id == '____ais-imagepopup-upload_image_noobject')
        {
            $parentguid = $this->_topic->guid;
        }
        else
        {
            $parentguid = $args[0];
        }

        // Set modified filename
        $filename = $this->get_modify_filename($temp['name']);

        // Insert the image into database
        $attachment = $this->insert_database($filename, $mimetype, $parentguid);

        $data['attachment'] = $attachment;

        // Get target to write the file
        $target = $this->get_data_from_database($filename, $parentguid);

        // Write the file
        $this->write_the_file($temp_name, $target);

        // Make a response for editor.uploadImages() function
        $location = midcom_db_attachment::get_url($attachment);

        $data['location'] = $location;

        // Return image location as JSON
        return new midcom_response_json(array('location' => $location));
    }

    /**
     * @param string $filename The file name
     * @return string The modified file name
     */
    private function get_modify_filename($filename)
    {

        $filename = midcom_db_attachment::safe_filename($filename);
        $pieces = explode('.', $filename);
        $core = array_shift($pieces);
        $split = preg_split("/(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})/", $core);
        $core = array_shift($split);
        $extension = array_pop($pieces);
        $modifyFilename = $core . date('Y-m-d-H-i-s', time()) . "." . $extension;

        return $modifyFilename;
    }


    /**
     * @param string $filename The file name
     * @param string $parentguid The parent GUID
     * @return midcom_db_attachment The file destination
     */
    private function get_data_from_database($filename, $parentguid)
    {
        $query = midcom_db_attachment::new_query_builder();
        $query->add_constraint('name', '=', $filename);
        $query->add_constraint('parentguid', '=', $parentguid);
        $entry = $query->execute();

        if(count($entry) == 0)
        {
            throw new midcom_error_notfound("There is no match in database " . midcom_connection::get_error_string());
        }
        else if(count($entry) == 1)
        {
            return $entry[0];
        }
        else if(count($entry) > 1)
        {
            throw new midcom_error('There is more than just one object' . midcom_connection::get_error_string());
        }
    }

    /**
     * @param string $tmp The temporary location of file
     * @param midcom_db_attachment $target The final destination for file
     */
    private function write_the_file($tmp, midcom_db_attachment $target)
    {
        $source = fopen($tmp, 'r');
        if(!$source)
        {
            throw new midcom_error("Could not open file " . $tmp . " for reading.");
        }
        $stat = $target->copy_from_handle($source);
        fclose($source);
        if(!$stat)
        {
            throw new midcom_error('Failed to copy from handle: ' . midcom_connection::get_error_string());
        }
    }

    /**
     * @param string $filename The file name
     * @param string $mimetype The MIME type
     * @param string $parentguid The parent GUID
     * @return midcom_db_attachment
     */
    private function insert_database($filename, $mimetype, $parentguid)
    {
        $attachment = new midcom_db_attachment();
        $attachment->name = $filename;
        $attachment->title = $filename;
        $attachment->mimetype = $mimetype;
        $attachment->parentguid = $parentguid;
        if(!$attachment->create())
        {
            throw new midcom_error('Failed to create derived image: ' . midcom_connection::get_error_string());
        }

        return $attachment;
    }
}
