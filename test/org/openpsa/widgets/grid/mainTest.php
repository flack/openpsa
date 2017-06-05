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
class org_openpsa_widgets_grid_mainTest extends openpsa_testcase
{
    public function test__construct()
    {
        $grid = new org_openpsa_widgets_grid('test', 'local');
        $this->assertEquals('local', $grid->get_option('datatype'));
    }

    public function test_get_identifier()
    {
        $grid = new org_openpsa_widgets_grid('test', 'local');
        $this->assertEquals('test', $grid->get_identifier());
    }

    public function test_set_provider()
    {
        $grid = new org_openpsa_widgets_grid('test', 'local');
        $this->assertNull($grid->get_provider());
        $provider = new org_openpsa_widgets_grid_provider([]);
        $grid->set_provider($provider);
        $this->assertEquals($provider, $grid->get_provider());
    }

    public function test__toString()
    {
        $grid = new org_openpsa_widgets_grid('test', 'local');
        $string = (string) $grid;
        $this->assertNotEmpty($string);
    }
}
