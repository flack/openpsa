<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\midcom\helper;

use PHPUnit\Framework\TestCase;
use midcom_helper_toolbar;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class toolbarTest extends TestCase
{
    public function test_add_item()
    {
        $toolbar = new midcom_helper_toolbar;
        $toolbar->add_item([MIDCOM_TOOLBAR_LABEL => 'Item 1']);
        $this->assertCount(1, $toolbar->items);
        $toolbar->add_item([MIDCOM_TOOLBAR_LABEL => 'Item 2'], 0);
        $this->assertCount(2, $toolbar->items);
        $this->assertEquals('Item 2', $toolbar->items[0][MIDCOM_TOOLBAR_LABEL]);
        $toolbar->add_item([MIDCOM_TOOLBAR_LABEL => 'Item 3'], 1);
        $this->assertCount(3, $toolbar->items);
        $this->assertEquals('Item 3', $toolbar->items[1][MIDCOM_TOOLBAR_LABEL]);
    }

    public function test_remove_item()
    {
        $toolbar = new midcom_helper_toolbar;
        $toolbar->add_item([MIDCOM_TOOLBAR_LABEL => 'Item 1']);
        $toolbar->add_item([MIDCOM_TOOLBAR_LABEL => 'Item 2']);
        $toolbar->add_item([MIDCOM_TOOLBAR_LABEL => 'Item 3']);
        $toolbar->remove_item(1);
        $this->assertCount(2, $toolbar->items);
        $this->assertEquals('Item 3', $toolbar->items[1][MIDCOM_TOOLBAR_LABEL]);
    }
}
