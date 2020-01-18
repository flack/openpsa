<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

namespace midcom\console\test;

use openpsa_testcase;
use midcom\console\application;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;

class applicationTest extends openpsa_testcase
{
    public function test_doRun()
    {
        if (!defined('OPENPSA_PROJECT_BASEDIR')) {
            define('OPENPSA_PROJECT_BASEDIR', OPENPSA_TEST_ROOT . '__output');
        }
        $input = new ArrayInput([]);
        $output = new NullOutput;
        $app = new application;
        $this->assertEquals(0, $app->doRun($input, $output));
    }
}
