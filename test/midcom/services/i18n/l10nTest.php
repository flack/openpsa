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
class midcom_services_i18n_l10nTest extends openpsa_testcase
{
    public function test_string_exists()
    {
        $l10n = new midcom_services_i18n_l10n('midcom', 'default');
        $this->assertTrue($l10n->string_exists('topic'));
        $this->assertFalse($l10n->string_exists('xxx'));
        $this->assertFalse($l10n->string_exists('topic', 'it'));
        $l10n->set_language('it');
        $this->assertFalse($l10n->string_exists('topic'));
    }

    public function test_string_available()
    {
        $l10n = new midcom_services_i18n_l10n('midcom', 'default');
        $this->assertTrue($l10n->string_available('topic'));
        $this->assertFalse($l10n->string_available('xxx'));
        $l10n->set_language('it');
        $this->assertTrue($l10n->string_available('topic'));
    }

    public function test_get()
    {
        $l10n = new midcom_services_i18n_l10n('midcom', 'default');
        $l10n->update('teststring', 'it', 'translate-it');
        $l10n->update('teststring', 'en', 'translate-en');
        $l10n->update('teststring2', 'en', 'translate2-en');
        $l10n->set_language('it');
        $l10n->set_fallback_language('en');
        $this->assertEquals($l10n->get('teststring'), 'translate-it');
        $this->assertEquals($l10n->get('teststring2'), 'translate2-en');
        $l10n->set_language('de');
        $this->assertEquals($l10n->get('teststring'), 'translate-en');
        $this->assertEquals($l10n->get('teststring', 'it'), 'translate-it');
        $this->assertEquals($l10n->get('teststring', 'ru'), 'translate-en');
    }
}
?>