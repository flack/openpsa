<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
} else {
    require_once dirname(__DIR__, 5) . '/vendor/autoload.php';
}
require_once __DIR__ . '/testcase.php';
require_once __DIR__ . '/directories.php';

require_once __DIR__ . '/helpers/sessioning.php';
require_once __DIR__ . '/helpers/relocate.php';
