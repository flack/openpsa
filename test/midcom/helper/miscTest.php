<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\helper;

use PHPUnit\Framework\TestCase;
use midcom_helper_misc;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class miscTest extends TestCase
{
    public function test_urlize()
    {
        $clean1 = midcom_helper_misc::urlize('foobar & barfoo');
        $clean2 = midcom_helper_misc::urlize($clean1);
        $this->assertEquals('foobar-barfoo', $clean1);
        $this->assertEquals($clean1, $clean2);
    }
}
