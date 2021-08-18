<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\services\i18n;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Request;
use PHPUnit\Framework\TestCase;
use midcom_services_i18n;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class i18nTest extends TestCase
{
    public function test_get_fallback_language()
    {
        $i18n = new midcom_services_i18n(new RequestStack, 'en');
        $this->assertEquals('en', $i18n->get_fallback_language());
    }

    /**
     * @dataProvider provider_read_http_negotiation
     */
    public function test_read_http_negotiation($input, $expected)
    {
        $rs = new RequestStack;
        $rs->push(new Request([], [], [], [], [], ['HTTP_ACCEPT_LANGUAGE' => $input]));
        $i18n = new midcom_services_i18n($rs, 'en');
        $this->assertEquals($expected, $i18n->get_current_language());
    }

    public function provider_read_http_negotiation()
    {
        return [
            [
                'de-de,de;q=0.8,en-us;q=0.5,en;q=0.3',
                'de'
            ],
            [
                'it-IT',
                'it'
            ]
        ];
    }
}
