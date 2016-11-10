<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/**
 * Datamanager 2 captcha widget
 *
 * Uses the Gregwar/Captcha package to provide Captcha authentification to a form.
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
 * <b>Implementation Limitation:</b>
 *
 * Due to the nature of the way how the captcha passphrase is passed to the captcha
 * image server, it is currently not possible to have more than one captcha per
 * unique REQUEST_URI within the user's PHP Session space.
 *
 * <b>Integration Guide:</b>
 *
 * Integrating Captcha support does not need any changes to your components, you just
 * have to add a new field into your DM2 schema using the captcha type/widget with null
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
     * The session key in use for this Formmanager instance.
     *
     * @var string
     */
    private $_session_key = null;

    /**
     * The input textbox used which needs to be frozen when operating on the captcha.
     *
     * @var HTML_QuickForm_Element
     */
    private $_element = null;

    /**
     * The session namespace to use.
     *
     * @var string
     */
    private $_session_domain = 'midcom_helper_datamanager2_widget_captcha';

    /**
     * The initialization event handler creates the captcha passphrase (if necessary).
     */
    public function _on_initialize()
    {
        $request_fieldname = "{$this->name}_session_key";
        if (array_key_exists($request_fieldname, $_REQUEST)) {
            $this->_session_key = $_REQUEST[$request_fieldname];
        } else {
            $this->_session_key = md5($_SERVER['REQUEST_URI']);
        }
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    public function add_elements_to_form($attributes)
    {
        $elements = array();

        $alt = $this->_l10n->get('captcha image alt text');
        $url = midcom_connection::get_url('self') . 'midcom-exec-midcom.helper.datamanager2/captcha.php/' .
            $this->_session_key;

        $static_html = "<img src='{$url}' alt='{$alt}' text='{$alt}' class='captcha' />";
        $elements[] = $this->_form->createElement('static', "{$this->name}_image", '', $static_html);

        $static_html = $this->_l10n->get('captcha message');
        $elements[] = $this->_form->createElement('static', "{$this->name}_message", '', $static_html);

        $attributes['class'] = 'captcha';
        $this->_element = $this->_form->createElement('text', $this->name, '', $attributes);
        $elements[] = $this->_element;
        $this->_form->applyFilter($this->name, 'trim');

        $this->_form->addGroup($elements, "{$this->name}_group", $this->_translate($this->_field['title']),
            '<br />', false);
        $this->_form->addElement('hidden', "{$this->name}_session_key", $this->_session_key);

        $this->_form->addFormRule(array(&$this, 'validate'));
    }

    /**
     * QF Validation callback which verifies the passcode against the Captcha.
     */
    function validate($fields)
    {
        $session = new midcom_services_session($this->_session_domain);
        if (!$session->exists($this->_session_key)) {
            return array("{$this->name}_group" => $this->_l10n->get('captcha validation failed'));
        }
        $passphrase = $session->get($this->_session_key);
        if ($fields[$this->name] != $passphrase) {
            $this->_element->setValue('');

            return array("{$this->name}_group" => $this->_l10n->get('captcha validation failed'));
        }

        return true;
    }

    /**
     * When syncing data we clear the captcha data in the session.
     */
    public function sync_type_with_widget($results)
    {
        $session = new midcom_services_session($this->_session_domain);
        $session->remove($this->_session_key);
    }

    /**
     * Freezes the Input Element
     */
    public function freeze()
    {
        $this->_element->freeze();
    }

    /**
     * Unfreezes the Input Element
     */
    public function unfreeze()
    {
        $this->_element->unfreeze();
    }

    /**
     * Checks if the input element is frozen.
     */
    public function is_frozen()
    {
        return $this->_element->isFrozen();
    }
}
