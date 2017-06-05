<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class midcom_services_i18nTest extends openpsa_testcase
{
    public function test_get_fallback_language()
    {
        $i18n = new midcom_services_i18n;
        $this->assertEquals('en', $i18n->get_fallback_language());
    }

    /**
     * @dataProvider provider_read_http_negotiation
     */
    public function test_read_http_negotiation($input, $expected)
    {
        $_SERVER['HTTP_ACCEPT_LANGUAGE'] = $input;
        $i18n = new midcom_services_i18n;
        unset($_SERVER['HTTP_ACCEPT_LANGUAGE']);
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
