<?php
/**
 * @author lukasz chalat
 * @package midcom.helper.imagepopup
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\File\UploadedFile;

/**
 * This handler get uploaded file and save it in database and write to file.
 *
 * @package midcom.helper.imagepopup
 */
class midcom_helper_imagepopup_handler_upload extends midcom_baseclasses_components_handler
{
    public function _handler_upload(Request $request, string $guid = null)
    {
        // Get the file
        $temp = $request->files->get('file');

        // Verify file extension
        if (   !$temp instanceof UploadedFile
            || !in_array(strtolower($temp->getClientOriginalExtension()), ["gif", "jpg", "png"])) {
                throw new midcom_error('Invalid extension.');
        }

        // Get the data
        $parentguid = $guid ?: $this->_topic->guid;

        // Set modified filename
        $filename = $this->get_modify_filename($temp->getClientOriginalName());

        // Insert the image into database
        $attachment = $this->insert_database($filename, $temp->getClientMimeType(), $parentguid);

        // Get target to write the file
        $target = $this->get_data_from_database($filename, $parentguid);

        // Write the file
        $this->write_the_file($temp->getRealPath(), $target);

        // Make a response for editor.uploadImages() function
        $location = midcom_db_attachment::get_url($attachment);

        // Return image location as JSON
        return new midcom_response_json(['location' => $location]);
    }

    private function get_modify_filename(string $filename) : string
    {
        $filename = midcom_db_attachment::safe_filename($filename);
        $pieces = explode('.', $filename);
        $core = array_shift($pieces);
        $split = preg_split("/-(\d{4})-(\d{2})-(\d{2})-(\d{2})-(\d{2})-(\d{2})/", $core);
        $core = array_shift($split);
        $extension = array_pop($pieces);
        return $core . "-" . date('Y-m-d-H-i-s', time()) . "." . $extension;
    }

    private function get_data_from_database(string $filename, string $parentguid) : midcom_db_attachment
    {
        $query = midcom_db_attachment::new_query_builder();
        $query->add_constraint('name', '=', $filename);
        $query->add_constraint('parentguid', '=', $parentguid);
        $entry = $query->execute();

        if (empty($entry)) {
            throw new midcom_error_notfound("There is no match in database " . midcom_connection::get_error_string());
        }
        if (count($entry) == 1) {
            return $entry[0];
        }
        throw new midcom_error('There is more than just one object' . midcom_connection::get_error_string());
    }

    /**
     * @param string $tmp The temporary location of file
     * @param midcom_db_attachment $target The final destination for file
     */
    private function write_the_file(string $tmp, midcom_db_attachment $target)
    {
        $source = fopen($tmp, 'r');
        if (!$source) {
            throw new midcom_error("Could not open file " . $tmp . " for reading.");
        }
        $stat = $target->copy_from_handle($source);
        fclose($source);
        if (!$stat) {
            throw new midcom_error('Failed to copy from handle: ' . midcom_connection::get_error_string());
        }
    }

    private function insert_database(string $filename, string $mimetype, string $parentguid) : midcom_db_attachment
    {
        $attachment = new midcom_db_attachment();
        $attachment->name = $filename;
        $attachment->title = $filename;
        $attachment->mimetype = $mimetype;
        $attachment->parentguid = $parentguid;
        if (!$attachment->create()) {
            throw new midcom_error('Failed to create derived image: ' . midcom_connection::get_error_string());
        }

        return $attachment;
    }
}
