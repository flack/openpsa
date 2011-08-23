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
    /**
     * returns array as code to generate it
     */
    static function array2code($arr, $level = 0, $code = '')
    {
        $pad1 = '';
        $d = $level * 4;
        while ($d--)
        {
            $pad1 .= ' ';
        }
        $pad2 = '';
        $d = ($level+1) * 4;
        while ($d--)
        {
            $pad2 .= ' ';
        }
        $code .= "Array\n{$pad1}(\n";
        foreach ($arr as $k => $v)
        {
            $code .= $pad2;
            switch (true)
            {
                case is_numeric($k):
                    $code .= "{$k} => ";
                    break;
                default:
                    $code .= "'{$k}' => ";
                    break;
            }
            switch (true)
            {
                case is_array($v):
                    $code = self::array2code($v, $level+2, $code);
                    break;
                case is_numeric($v):
                    $code .= "{$v},\n";
                    break;
                default:
                    $code .= "'" . str_replace("'", "\'", $v) . "',\n";
                    break;
            }
        }
        $code .= "{$pad1})";
        if ($level > 0)
        {
            $code .= ",\n";
        }
        return $code;
    }

    /**
     * Fixes newline etc encoding issues in serialized data
     *
     * @param string $data The data to fix.
     * @return string $data with serializations fixed.
     */
    static function fix_serialization($data = null)
    {
        //Skip on empty data
        if (empty($data))
        {
            return $data;
        }

        $preg='/s:([0-9]+):"(.*?)";/ms';
        preg_match_all($preg, $data, $matches);
        $cache = array();

        foreach ($matches[0] as $k => $origFullStr)
        {
            $origLen = $matches[1][$k];
            $origStr = $matches[2][$k];
            $newLen = strlen($origStr);
            if ($newLen != $origLen)
            {
                $newFullStr = "s:$newLen:\"$origStr\";";
                //For performance we cache information on which strings have already been replaced
                if (!array_key_exists($origFullStr, $cache))
                {
                    $data = str_replace($origFullStr, $newFullStr, $data);
                    $cache[$origFullStr] = true;
                }
            }
        }

        return $data;
    }

    public static function render_fileinfo($object, $field)
    {
        $output = '';
        $identifiers = explode(',', $object->get_parameter('midcom.helper.datamanager2.type.blobs', 'guids_' . $field));
        if (empty($identifiers))
        {
            return $output;
        }
        $host_prefix = $_MIDCOM->get_host_prefix();
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
                $attachment = new midcom_db_attachment($guid);
                $url = $host_prefix . '/midcom-serveattachmentguid-' . $attachment->guid . '/' . $attachment->name;
                $stat = $attachment->stat();
                $filesize = midcom_helper_misc::filesize_to_string($stat[7]);
                $mimetype = org_openpsa_documents_document_dba::get_file_type($attachment->mimetype);
                $mimetype_icon = midcom_helper_misc::get_mime_icon($attachment->mimetype);

                $output .= '<span class="org_openpsa_helpers_fileinfo">';
                $output .= '<a href="' . $url . '" class="icon"><img src="' . $mimetype_icon . '" alt="' . $mimetype . '" /></a>';
                $output .= '<a href="' . $url . '" class="filename">' . $attachment->name . '</a>';
                $output .= '<span class="mimetype">' . $mimetype . '</span>';
                $output .= '<span class="filesize">' . $filesize . '</span>';
                $output .= "</span>\n";
            }
            catch (midcom_error $e)
            {
                $e->log();
                continue;
            }
            $output .= '';
        }

        return $output;
     }

    public static function get_attachment_urls($object, $field)
    {
        $urls = array();
        $identifiers = explode(',', $object->get_parameter('midcom.helper.datamanager2.type.blobs', 'guids_' . $field));
        if (empty($identifiers))
        {
            return false;
        }
        $host_prefix = $_MIDCOM->get_host_prefix();
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
                $attachment = new midcom_db_attachment($guid);
                $urls[$guid] = $host_prefix . '/midcom-serveattachmentguid-' . $attachment->guid . '/' . $attachment->name;
            }
            catch (midcom_error $e)
            {
                $e->log();
                continue;
            }
        }

        return $urls;
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
            $language = $_MIDCOM->i18n->get_current_language();
            $language_db = $_MIDCOM->i18n->get_language_db();
            setlocale(LC_ALL, $language_db[$language]['locale']);

            $localeconv = localeconv();
        }

        $output = number_format((float) $number, 2, $localeconv['decimal_point'], $localeconv['thousands_sep']);
        return $output;
    }
}
?>