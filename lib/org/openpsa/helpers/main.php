<?php
/**
 * Collection of small helper functions for OpenPSA
 *
 * @package org.openpsa.helpers
 * @author Eero af Heurlin, http://www.iki.fi/rambo
 * @copyright Nemein Oy, http://www.nemein.com
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * @package org.openpsa.helpers
 */
class org_openpsa_helpers
{
    public static function render_fileinfo($object, $field)
    {
        $output = '';
        $attachments = self::get_dm2_attachments($object, $field);
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
      * @param midcom_core_dbaobject $object The object we're working on
      * @param string $field The schema field name
      * @return midcom_db_attachment[] List of attachments, indexed by identifier
      */
     public static function get_dm2_attachments($object, $field)
     {
         $attachments = [];
         $identifiers = explode(',', $object->get_parameter('midcom.helper.datamanager2.type.blobs', 'guids_' . $field));
         if (empty($identifiers)) {
             return $attachments;
         }
         foreach ($identifiers as $identifier) {
             $parts = explode(':', $identifier);
             if (count($parts) != 2) {
                 continue;
             }
             $guid = $parts[1];
             try {
                 $attachments[$parts[0]] = midcom_db_attachment::get_cached($guid);
             } catch (midcom_error $e) {
                 $e->log();
             }
         }

         return $attachments;
     }
}
