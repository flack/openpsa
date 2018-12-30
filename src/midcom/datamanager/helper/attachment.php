<?php
/**
 * @copyright CONTENT CONTROL GmbH, http://www.contentcontrol-berlin.de
 */

namespace midcom\datamanager\helper;

use midcom_db_attachment;
use midcom_error;
use midcom_helper_reflector_nameresolver;

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