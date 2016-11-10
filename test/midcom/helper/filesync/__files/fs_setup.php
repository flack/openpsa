<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA filesync testcase helpers
 *
 * @package openpsa.test
 */
class openpsa_test_fs_setup
{
    public static function get_exportdir($type)
    {
        $rootdir = OPENPSA_TEST_ROOT . '__output/filesync_export/';
        self::check_dir($rootdir);
        self::check_dir($rootdir . $type);
        return $rootdir . $type . '/';
    }

    private static function check_dir($dir)
    {
        if (   !is_dir($dir)
            && !mkdir($dir)) {
            throw new Exception('Failed to create directory ' . $dir);
        }
    }
}
