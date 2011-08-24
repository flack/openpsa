<?php
/**
 * @package openpsa.test
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

if (!defined('OPENPSA_TEST_ROOT'))
{
    define('OPENPSA_TEST_ROOT', dirname(dirname(dirname(dirname(dirname(dirname(__FILE__)))))) . DIRECTORY_SEPARATOR);
    require_once(OPENPSA_TEST_ROOT . 'rootfile.php');
}

/**
 * OpenPSA testcase
 *
 * @package openpsa.test
 */
class org_openpsa_documents_handler_document_adminTest extends openpsa_testcase
{
    protected static $_person;
    protected static $_document;

    public static function setUpBeforeClass()
    {
        self::$_person = self::create_user(true);

        $topic = self::get_component_node('org.openpsa.documents');
        self::$_document = self::create_class_object('org_openpsa_documents_document_dba', array('topic' => $topic->id));
    }

    public function testHandler_delete()
    {
        midcom::get('auth')->request_sudo('org.openpsa.documents');

        $data = $this->run_handler('org.openpsa.documents', array('document', 'delete', self::$_document->guid));
        $this->assertEquals('document-delete', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }

    public function testHandler_edit()
    {
        midcom::get('auth')->request_sudo('org.openpsa.documents');

        $data = $this->run_handler('org.openpsa.documents', array('document', 'edit', self::$_document->guid));
        $this->assertEquals('document-edit', $data['handler_id']);

        midcom::get('auth')->drop_sudo();
    }
}
?>