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
class midcom_services_i18n_l10nTest extends TestCase
{
    public function test_string_exists()
    {
        $l10n = new midcom_services_i18n_l10n('midcom');
        $this->assertTrue($l10n->string_exists('topic'));
        $this->assertFalse($l10n->string_exists('xxx'));
        $this->assertFalse($l10n->string_exists('topic', 'it'));
        $l10n->set_language('it');
        $this->assertFalse($l10n->string_exists('topic'));
    }

    public function test_string_available()
    {
        $l10n = new midcom_services_i18n_l10n('midcom');
        $this->assertTrue($l10n->string_available('topic'));
        $this->assertFalse($l10n->string_available('xxx'));
        $l10n->set_language('it');
        $this->assertTrue($l10n->string_available('topic'));
    }

    public function test_get()
    {
        $l10n = new midcom_services_i18n_l10n('midcom');
        $l10n->set_language('de');
        $l10n->set_fallback_language('en');
        $this->assertEquals('Datum', $l10n->get('date'));
        $this->assertEquals('Date', $l10n->get('date', 'ru'));
        $l10n->set_language('en');
        $this->assertEquals('Date', $l10n->get('date'));
    }
}
