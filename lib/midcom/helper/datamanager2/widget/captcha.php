<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: captcha.php 25328 2010-03-18 19:10:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** @ignore */
require_once('Text/Password.php');

/**
 * Datamanager 2 captcha widget
 *
 * Uses the PEAR Text_CAPTCHA package to provide Captcha authentification to a form.
 * Uses the PEAR Text_Password package to generate a captcha passphrase.
 *
 * The captcha is completely auto-generated, you should not have to worry about any
 * defaults. The captcha rendering is done using a midcom-exec callback linking into
 * the current PHP session (where the captcha phrase is stored).
 *
 * Security considerations:
 *
 * The captcha passphrases are made unique for each invocation of this type. This is
 * accomplished by creating a random session key during initialize where the passphrase
 * is stored. This session key is made persistent using a hidden request variable. Every
 * passphrase is only valid for a single submission call. Every successful submit will
 * invalidate the passphrase in the user's session thus triggering a new captcha being
 * generated.
 *
 * <b>Available configuration options:</b>
 *
 * - <i>int length</i> sets the length of the Captcha passphrase, defaults to 6.
 *
 * <b>Implementation Limitation:</b>
 *
 * Due to the nature of the way how the captcha passphrase is passed to the captcha
 * image server, it is currently not possible to have more then one captcha per
 * unique REQUEST_URI within the user's PHP Session space.
 *
 * <b>Integration Guide:</b>
 *
 * Integrating Captcha support does not need any changes to your components, you just
 * have to add a new field into your DM2 schema using the captcha type/widget with NULL
 * storage:
 *
 * <code>
 * 'captcha' => Array
 * (
 *     'title' => 'User Verification',
 *     'storage' => null,
 *     'type' => 'captcha',
 *     'widget' => 'captcha',
 * ),
 * </code>
 *
 * @todo Enable multiple captchas per page.
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_captcha extends midcom_helper_datamanager2_widget
{
    /**
     * The length of the passphrase.
     *
     * @var int
     */
    var $length = 6;

    /**
     * The passphrase currently in use.
     *
     * @var string
     * @access private
     */
    var $_passphrase = null;

    /**
     * The session key in use for this Formmanager instance.
     *
     * @var string
     * @access private
     */
    var $_session_key = null;

    /**
     * The input textbox used which needs to be frozen when operating on the captcha.
     *
     * @var HTML_QuickForm_Element
     * @access private
     */
    var $_element = null;

    /**
     * The session namespace to use.
     *
     * @var string
     * @access private
     */
    var $_session_domain = 'midcom_helper_datamanager2_widget_captcha';

    /**
     * The initialization event handler creates the captcha passphrase (if necessary).
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {

        $request_fieldname = "{$this->name}_session_key";
        if (array_key_exists($request_fieldname, $_REQUEST))
        {
            $this->_session_key = $_REQUEST[$request_fieldname];
        }
        else
        {
            $hash = $_SERVER['REQUEST_URI'];
            $this->_session_key = md5($hash);
        }

        $session = new midcom_services_session($this->_session_domain);
        if (! $session->exists($this->_session_key))
        {
            $phrase = Text_Password::create($this->length);
            $this->_passphrase = $phrase;
            $session->set($this->_session_key, $phrase);
        }
        else
        {
            $this->_passphrase = $session->get($this->_session_key);
        }

        return true;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    function add_elements_to_form()
    {
        $elements = Array();

        $alt = $this->_l10n->get('captcha image alt text');
        $url = $GLOBALS['midcom_config']['midcom_site_url'] .
            'midcom-exec-midcom.helper.datamanager2/captcha.php/' .
            $this->_session_key;

        $static_html = "<img src='{$url}' alt='{$alt}' text='{$alt}' class='captcha' />";
        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_image", '', $static_html);

        $static_html = $this->_l10n->get('captcha message');
        $elements[] = HTML_QuickForm::createElement('static', "{$this->name}_message", '', $static_html);

        $attributes = Array
        (
            'class' => 'captcha',
            'id'    => "{$this->_namespace}{$this->name}",
        );
        $this->_element = HTML_QuickForm::createElement('text', $this->name, '', $attributes);
        $elements[] = $this->_element;
        $this->_form->applyFilter($this->name, 'trim');

        $this->_form->addGroup($elements, "{$this->name}_group", $this->_translate($this->_field['title']),
            '<br />', false);
        $this->_form->addElement('hidden', "{$this->name}_session_key", $this->_session_key);

        $this->_form->addFormRule(Array(&$this, 'validate'));
    }

    /**
     * QF Validation callback which verifies the passcode against the Captcha.
     */
    function validate($fields)
    {
        if ($fields[$this->name] != $this->_passphrase)
        {
            return Array ("{$this->name}_group" => $this->_l10n->get('captcha validation failed'));
        }

        return true;
    }

    function get_default()
    {
        return '';
    }

    /**
     * When syncing data we clear the capatcha data in the session.
     */
    function sync_type_with_widget($results)
    {
        $session = new midcom_services_session($this->_session_domain);
        $session->remove($this->_session_key);
    }

    /**
     * Freezes the Input Element
     */
    function freeze()
    {
        $this->_element->freeze();
    }

    /**
     * Unfreezes the Input Element
     */
    function unfreeze()
    {
        $this->_element->unfreeze();
    }

    /**
     * Checks if the input element is frozen.
     */
    function is_frozen()
    {
        return $this->_element->isFrozen();
    }

}

?>