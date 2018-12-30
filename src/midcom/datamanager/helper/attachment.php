<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\helper;

use midcom_db_attachment;
use midcom_error;
use midcom_helper_reflector_nameresolver;
use Symfony\Component\HttpFoundation\File\MimeType\FileBinaryMimeTypeGuesser;

/**
 * Attachment helper
 */
trait attachment
{
    /**
     * Make sure we have unique filename
     */
    private function generate_unique_name($filename, $parentguid)
    {
        $filename = midcom_db_attachment::safe_filename($filename, true);
        $attachment = new midcom_db_attachment;
        $attachment->name = $filename;
        $attachment->parentguid = $parentguid;

        $resolver = new midcom_helper_reflector_nameresolver($attachment);
        if (!$resolver->name_is_unique()) {
            debug_add("Name '{$attachment->name}' is not unique, trying to generate", MIDCOM_LOG_INFO);
            $ext = '';
            if (preg_match('/^(.*)(\..*?)$/', $filename, $ext_matches)) {
                $ext = $ext_matches[2];
            }
            $filename = $resolver->generate_unique_name('name', $ext);
        }
        return $filename;
    }

    /**
     *
     * @param midcom_db_attachment $input
     * @param array $existing
     * @param string $identifier
     * @return midcom_db_attachment
     */
    protected function get_attachment(midcom_db_attachment $input, array $existing, $identifier)
    {
        // upload case
        if ($input->id == 0) {
            $guesser = new FileBinaryMimeTypeGuesser;
            $input->mimetype = $guesser->guess($input->location);
        }

        $filename = midcom_db_attachment::safe_filename($identifier . '_' . $input->name, true);
        if (!empty($existing[$identifier])) {
            $attachment = $existing[$identifier];
            if ($attachment->name != $filename) {
                $attachment->name = $this->generate_unique_name($filename, $attachment->parentguid);
            }
            $attachment->title = $input->name;
            $attachment->mimetype = $input->mimetype;
            return $attachment;
        }

        $attachment = new midcom_db_attachment;
        $attachment->parentguid = $input->parentguid;
        $this->create_attachment($attachment, $filename, $input->name, $input->mimetype);

        return $attachment;
    }

    /**
     *
     * @param midcom_db_attachment $attachment
     * @param string $filename
     * @param string $title
     * @param string $mimetype
     * @throws midcom_error
     */
    protected function create_attachment(midcom_db_attachment $attachment, $filename, $title, $mimetype)
    {
        $attachment->name = $this->generate_unique_name($filename, $attachment->parentguid);
        $attachment->title = $title;
        $attachment->mimetype = $mimetype;

        if (!$attachment->create()) {
            throw new midcom_error('Failed to create attachment: ' . \midcom_connection::get_error_string());
        }
    }
}