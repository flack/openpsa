<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\httplib;

use PHPUnit\Framework\TestCase;
use org_openpsa_httplib;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class mainTest extends TestCase
{
    public function test_post()
    {
        $httplib = new org_openpsa_httplib;
        $this->assertFalse($httplib->post('/', ['test' => '1']));
        $this->assertNotEmpty($httplib->error);
    }
}
