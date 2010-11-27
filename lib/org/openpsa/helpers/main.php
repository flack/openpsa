<?php
/**
 * Collection of small helper functions for OpenPSA
 *
 * @package org.openpsa.helpers
 * @author Eero af Heurlin, http://www.iki.fi/rambo
 * @version $Id: main.php 26074 2010-05-11 22:44:03Z flack $
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

    /**
     * Function for adding JavaScript buttons for saving/cancelling Datamanager 2 form via the toolbar
     *
     * @param object $handler The current handler object reference
     */
    static function dm2_savecancel(&$handler)
    {
        $toolbar =& $handler->_view_toolbar;
        if (   !is_object($toolbar)
            || !method_exists($toolbar, 'add_item'))
        {
            return;
        }
        $toolbar->add_item
        (
            array
            (
                MIDCOM_TOOLBAR_URL => 'javascript:document.getElementsByName("midcom_helper_datamanager2_save")[0].click();',
                MIDCOM_TOOLBAR_LABEL => $handler->_l10n_midcom->get("save"),
                MIDCOM_TOOLBAR_ICON => 'stock-icons/16x16/save.png',
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
                MIDCOM_TOOLBAR_URL => 'javascript:document.getElementsByName("midcom_helper_datamanager2_cancel")[0].click();',
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