<?php
/**
 * @package org.openpsa.mail
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 */

/**
 * Helper class for constructing simple formmailers for embedding directly into a page.
 *
 * Available form fields can be controlled by a DM2 schema
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
class org_openpsa_mail_formmailer extends midcom_baseclasses_components_purecode
{
    /**
     * The schemadb we're working with
     *
     * @var array
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

    public function __construct($schemadb = null)
    {
        parent::__construct();
        if (null === $schemadb) {
            $this->_schemadb = midcom_helper_datamanager2_schema::load_database($this->_config->get('schemadb_formmailer'));
        } else {
            $this->_schemadb = $schemadb;
        }
    }

    public function process()
    {
        $controller = midcom_helper_datamanager2_controller::create('nullstorage');
        $controller->schemadb = $this->_schemadb;
        $controller->schemaname = 'default';
        if (!$controller->initialize()) {
            throw new midcom_error('Failed to initialize a DM2 nullstorage controller.');
        }

        switch ($controller->process_form()) {
            case 'save':
                $this->_send_form($controller->datamanager->get_content_email());
                break;
            case 'cancel':
                //Clear form
                midcom::get()->relocate($_SERVER['REQUEST_URI']);
            default:
                $controller->display_form();
        }
    }

    private function _send_form($values)
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

        $parameters = array();
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

    private function get($field)
    {
        if (!empty($this->$field)) {
            return $this->$field;
        }
        return $this->_config->get('formmailer_' . $field);
    }
}
