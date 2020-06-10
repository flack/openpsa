<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use PHPUnit\Framework\TestCase;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_mail_mainTest extends TestCase
{
    public function test_send()
    {
        $mail = new org_openpsa_mail;
        $mail->html_body = '<p>Test</p>';
        $this->assertTrue($mail->send());
        $this->assertEquals('Test', $mail->body);
    }
}
