<?php
/**
 * Collection of small helper functions for OpenPSA
 *
 * @package org.openpsa.helpers
 * @author Eero af Heurlin, http://www.iki.fi/rambo
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

use midcom\datamanager\storage\blobs;

/**
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers
{
    public static function render_fileinfo(midcom_core_dbaobject $object, string $field) : string
    {
        $output = '';
        $attachments = blobs::get_attachments($object, $field);
        foreach ($attachments as $attachment) {
            $stat = $attachment->stat();
            $filesize = midcom_helper_misc::filesize_to_string($stat[7]);
            $url = midcom::get()->permalinks->create_attachment_link($attachment->guid, $attachment->name);
            $mimetype = org_openpsa_documents_document_dba::get_file_type($attachment->mimetype);
            $parts = explode('.', $attachment->name);
            $ext = '';
            if (count($parts) > 1) {
                $ext = end($parts);
            }

            $output .= '<span class="org_openpsa_helpers_fileinfo">';
            $output .= '<a href="' . $url . '" class="icon" title="' . $attachment->name . '">';
            $output .= '<i class="fa fa-file-o"></i><span class="extension">' . $ext . '</span></a>';
            $output .= '<a href="' . $url . '" class="filename">' . $attachment->name . '</a>';
            $output .= '<span class="mimetype">' . $mimetype . '</span>';
            $output .= '<span class="filesize">' . $filesize . '</span>';
            $output .= "</span>\n";
        }

        return $output;
    }

    /**
     * @deprecated Use midcom\datamanager\storage\blobs::get_attachments() instead
     */
    public static function get_dm2_attachments(midcom_core_dbaobject $object, string $field) : array
    {
        return blobs::get_attachments($object, $field);
    }
}
