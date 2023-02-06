<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

namespace test\org\openpsa\relatedto\handler;

use openpsa_testcase;
use midcom;
use org_openpsa_invoices_invoice_dba;
use org_openpsa_relatedto_dba;
use org_openpsa_sales_salesproject_dba;
use org_openpsa_relatedto_plugin;

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class relatedtoTest extends openpsa_testcase
{
    protected static org_openpsa_invoices_invoice_dba $_object_from;
    protected static org_openpsa_sales_salesproject_dba $_object_to;
    protected static org_openpsa_relatedto_dba $_relation;

    public static function setUpBeforeClass() : void
    {
        self::$_object_from = self::create_class_object(org_openpsa_invoices_invoice_dba::class);
        self::$_object_to = self::create_class_object(org_openpsa_sales_salesproject_dba::class);

        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        self::$_relation = org_openpsa_relatedto_plugin::create(self::$_object_from, 'org.openpsa.invoices', self::$_object_to, 'org.openpsa.sales');
        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_render_sort()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');
        $data = $this->run_handler('org.openpsa.invoices', ['__mfa', 'org.openpsa.relatedto', 'render', self::$_object_from->guid, 'both', 'default']);
        $this->assertEquals('render_sort', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }

    public function testHandler_render()
    {
        midcom::get()->auth->request_sudo('org.openpsa.relatedto');

        $data = $this->run_handler('org.openpsa.invoices', ['__mfa', 'org.openpsa.relatedto', 'render', self::$_object_from->guid, 'both']);
        $this->assertEquals('render', $data['handler_id']);

        midcom::get()->auth->drop_sudo();
    }
}
