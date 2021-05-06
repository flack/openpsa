<?php
/**
 * @package org.openpsa.mail
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

use midcom\datamanager\schemadb;
use midcom\datamanager\datamanager;
use Symfony\Component\HttpFoundation\Request;

/**
 * Helper class for constructing simple formmailers for embedding directly into a page.
 *
 * Available form fields can be controlled by a datamanager schema
 *
 * Example:
 * <code>
 * $formmailer = new org_openpsa_mail_formmailer;
 * $formmailer->to = 'website_owner@domain';
 * $formmailer->process();
 * </code>
 *
 * @package org.openpsa.mail
 */
class org_openpsa_mail_formmailer
{
    use midcom_baseclasses_components_base;

    /**
     * @var schemadb
     */
    private $_schemadb;

    /**
     * Email sender
     *
     * @var string
     */
    public $from;

    /**
     * Email recipient
     *
     * @var string
     */
    public $to;

    /**
     * Email body template
     *
     * @var string
     */
    public $body;

    /**
     * Email subject template
     *
     * @var string
     */
    public $subject;

    public function __construct(schemadb $schemadb = null)
    {
        $this->_component = 'org.openpsa.mail';
        if (null === $schemadb) {
            $this->_schemadb = schemadb::from_path($this->_config->get('schemadb_formmailer'));
        } else {
            $this->_schemadb = $schemadb;
        }
    }

    public function process(Request $request = null)
    {
        $request = $request ?: Request::createFromGlobals();

        $dm = new datamanager($this->_schemadb);
        $controller = $dm->get_controller();

        switch ($controller->handle($request)) {
            case 'save':
                $this->_send_form($controller->get_datamanager()->render('plaintext'));
                break;
            case 'cancel':
                //Clear form
                midcom::get()->relocate($request->server->get('REQUEST_URI'));
            default:
                $controller->display_form();
        }
    }

    private function _send_form(array $values)
    {
        $mail = new org_openpsa_mail;

        if (!empty($values['subject'])) {
            $mail->subject = $values['subject'];
        } else {
            $mail->subject = $this->get('subject');
        }

        $mail->from = $this->get('from');
        $mail->to = $this->get('to');
        $mail->body = $this->get('body');

        $parameters = [];
        foreach ($values as $field => $value) {
            $parameters[strtoupper($field)] = $value;
        }
        $mail->parameters = $parameters;

        if ($mail->send()) {
            echo $this->_l10n->get('form successfully sent');
        } else {
            echo $this->_l10n->get('error sending form');
        }
    }

    private function get(string $field)
    {
        if (!empty($this->$field)) {
            return $this->$field;
        }
        return $this->_config->get('formmailer_' . $field);
    }
}
