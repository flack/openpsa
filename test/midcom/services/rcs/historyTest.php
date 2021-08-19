<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\rcs;

use PHPUnit\Framework\TestCase;
use midcom_services_rcs_history;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class historyTest extends TestCase
{
    public function test_get_next()
    {
        $data = [
            '1.10' => [],
            '1.9' => [],
            '1.8' => [],
            '1.7' => [],
            '1.6' => [],
            '1.5' => [],
            '1.4' => [],
            '1.3' => [],
            '1.2' => [],
            '1.1' => []
        ];
        $history = new midcom_services_rcs_history($data);
        $result = $history->get_next('1.1');
        $this->assertEquals(['version' => '2'], $result);
        $result = $history->get_next('1.9');
        $this->assertEquals(['version' => '10'], $result);
        $result = $history->get_next('1.10');
        $this->assertNull($result);
    }

    public function test_get_previous()
    {
        $data = [
            '1.10' => [],
            '1.9' => [],
            '1.8' => [],
            '1.7' => [],
            '1.6' => [],
            '1.5' => [],
            '1.4' => [],
            '1.3' => [],
            '1.2' => [],
            '1.1' => []
        ];
        $history = new midcom_services_rcs_history($data);
        $result = $history->get_previous('1.1');
        $this->assertNull($result);
        $result = $history->get_previous('1.10');
        $this->assertEquals(['version' => '9'], $result);
    }
}
