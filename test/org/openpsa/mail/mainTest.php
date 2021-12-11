<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\mail;

use PHPUnit\Framework\TestCase;
use org_openpsa_mail;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class mainTest extends TestCase
{
    public function test_send()
    {
        $mail = new org_openpsa_mail;
        $mail->from = 'nowhere@openpsa2.org';
        $mail->to = 'nowhere@openpsa2.org';
        $mail->html_body = '<p>Test</p>';
        $this->assertTrue($mail->send());
        $this->assertEquals('Test', $mail->body);
    }
}
