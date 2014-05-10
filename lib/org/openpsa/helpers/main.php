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
        foreach ($attachments as $attachment)
        {
            $stat = $attachment->stat();
            $filesize = midcom_helper_misc::filesize_to_string($stat[7]);
            $url = midcom::get('permalinks')->create_attachment_link($attachment->guid, $attachment->name);
            $mimetype = org_openpsa_documents_document_dba::get_file_type($attachment->mimetype);
            $mimetype_icon = midcom_helper_misc::get_mime_icon($attachment->mimetype);

            $output .= '<span class="org_openpsa_helpers_fileinfo">';
            $output .= '<a href="' . $url . '" class="icon"><img src="' . $mimetype_icon . '" alt="' . $mimetype . '" /></a>';
            $output .= '<a href="' . $url . '" class="filename">' . $attachment->name . '</a>';
            $output .= '<span class="mimetype">' . $mimetype . '</span>';
            $output .= '<span class="filesize">' . $filesize . '</span>';
            $output .= "</span>\n";
        }

        return $output;
     }

     public static function get_dm2_attachments($object, $field)
     {
         $attachments = array();
         $identifiers = explode(',', $object->get_parameter('midcom.helper.datamanager2.type.blobs', 'guids_' . $field));
         if (empty($identifiers))
         {
             return $attachments;
         }
         foreach ($identifiers as $identifier)
         {
             $parts = explode(':', $identifier);
             if (sizeof($parts) != 2)
             {
                 continue;
             }
             $guid = $parts[1];
             try
             {
                 $attachments[] = midcom_db_attachment::get_cached($guid);
             }
             catch (midcom_error $e)
             {
                 $e->log();
                 continue;
             }
         }

         return $attachments;
     }

    /**
     * Function for adding JavaScript buttons for saving/cancelling Datamanager 2 form via the toolbar
     *
     * @param object $handler The current handler object reference
     * @param string $action The DM's principal action (save or delete)
     */
    public static function dm2_savecancel(&$handler, $action = 'save')
    {
        $toolbar =& $handler->_view_toolbar;
        if (   !is_object($toolbar)
            || !method_exists($toolbar, 'add_item'))
        {
            return;
        }

        $icon = $action;
        if ($action == 'delete')
        {
            $icon = 'trash';
        }

        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'javascript:document.getElementsByName("midcom_helper_datamanager2_' . $action . '[0]")[0].click();',
                MIDCOM_TOOLBAR_LABEL => $handler->_l10n_midcom->get($action),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/' . $icon . '.png',
                MIDCOM_TOOLBAR_OPTIONS  => array
                (
                    'rel' => 'directlink',
                ),
            )
        );
        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'javascript:document.getElementsByName("midcom_helper_datamanager2_cancel[0]")[0].click();',
                MIDCOM_TOOLBAR_LABEL => $handler->_l10n_midcom->get("cancel"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/cancel.png',
                MIDCOM_TOOLBAR_OPTIONS  => array
                (
                    'rel' => 'directlink',
                ),
            )
        );
    }

    /**
     * Helper function that formats numbers in the current locale's format
     *
     * @todo Negative numbers
     * @param mixed $number The input number
     * @return string The formatted output
     */
    static function format_number($number)
    {
        static $localeconv = null;

        if (is_null($localeconv))
        {
            $language = midcom::get('i18n')->get_current_language();
            $language_db = midcom::get('i18n')->get_language_db();
            setlocale(LC_ALL, $language_db[$language]['locale']);

            $localeconv = localeconv();
        }

        return number_format((float) $number, 2, $localeconv['decimal_point'], $localeconv['thousands_sep']);
    }
}
?>