<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
* @license http://www.gnu.org/licenses/gpl.html GNU General Public License
*/

namespace midcom\console\test;

use PHPUnit\Framework\TestCase;
use midcom\console\command\cron;
use Symfony\Component\Console\Tester\CommandTester;

class cronTest extends TestCase
{
    public function test_execute()
    {
        $cmd = new cron;

        $tester = new CommandTester($cmd);
        $tester->execute([]);
        $this->assertEquals(0, $tester->getStatusCode());
    }
}
