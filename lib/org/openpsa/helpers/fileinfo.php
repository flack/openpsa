<?php
/**
 * @package org.openpsa.helpers
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\storage\blobs;

/**
 * Helper class for file info
 *
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers_fileinfo
{
    /**
     * Try to generate a human-readable file type by doing some educated guessing based on mimetypes
     */
    public static function render_type(string $mimetype) : string
    {
        if (!preg_match('/\//', $mimetype)) {
            return $mimetype;
        }

        //first, try if there is a direct translation
        if ($mimetype != midcom::get()->i18n->get_string($mimetype, 'org.openpsa.helpers')) {
            return midcom::get()->i18n->get_string($mimetype, 'org.openpsa.helpers');
        }

        //if nothing is found, do some heuristics
        [$type, $subtype] = explode('/', $mimetype);
        $st_orig = $subtype;

        switch ($type) {
            case 'image':
                $subtype = strtoupper($subtype);
                break;
            case 'text':
                $type = 'document';
                break;
            case 'application':
                $type = 'document';

                if (preg_match('/^vnd\.oasis\.opendocument/', $subtype)) {
                    $type = str_replace('vnd.oasis.opendocument.', '', $subtype);
                    $subtype = 'OpenDocument';
                } elseif (preg_match('/^vnd\.ms/', $subtype)) {
                    $subtype = ucfirst(str_replace('vnd.ms-', '', $subtype));
                } elseif (preg_match('/^vnd\.openxmlformats/', $subtype)) {
                    $type = str_replace('vnd.openxmlformats-officedocument.', '', $subtype);
                    $type = str_replace('ml.', ' ', $type);
                    $subtype = 'OOXML';
                }

                $subtype = preg_replace('/^vnd\./', '', $subtype);
                $subtype = preg_replace('/^x-/', '', $subtype);

                break;
        }

        /*
         * if nothing matched so far and the subtype is alphanumeric, uppercase it on the theory
         * that it's probably a file extension
         */
        if (   $st_orig == $subtype
            && preg_match('/^[a-z0-9]+$/', $subtype)) {
            $subtype = strtoupper($subtype);
        }

        return sprintf(midcom::get()->i18n->get_string('%s ' . $type, 'org.openpsa.helpers'), $subtype);
    }

    public static function render(midcom_core_dbaobject $object, string $field) : string
    {
        $output = '';
        $attachments = blobs::get_attachments($object, $field);
        foreach ($attachments as $attachment) {
            $stat = $attachment->stat();
            $filesize = midcom_helper_misc::filesize_to_string($stat[7]);
            $url = midcom::get()->permalinks->create_attachment_link($attachment->guid, $attachment->name);
            $type = self::render_type($attachment->mimetype);
            $parts = explode('.', $attachment->name);
            $ext = '';
            if (count($parts) > 1) {
                $ext = end($parts);
            }

            $output .= '<span class="org_openpsa_helpers_fileinfo">';
            $output .= '<a href="' . $url . '" class="icon" title="' . $attachment->name . '">';
            $output .= '<i class="fa fa-file-o"></i><span class="extension">' . $ext . '</span></a>';
            $output .= '<a href="' . $url . '" class="filename">' . $attachment->name . '</a>';
            $output .= '<span class="mimetype">' . $type . '</span>';
            $output .= '<span class="filesize">' . $filesize . '</span>';
            $output .= "</span>\n";
        }

        return $output;
    }
}
