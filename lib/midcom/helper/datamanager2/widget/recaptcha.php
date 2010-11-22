<?php
/**
 * @package midcom.helper.datamanager2
 * @author The Midgard Project, http://www.midgard-project.org
 * @version $Id: recaptcha.php 25328 2010-03-18 19:10:35Z indeyets $
 * @copyright The Midgard Project, http://www.midgard-project.org
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 */

/** @ignore */
require_once('Text/Password.php');
require_once(MIDCOM_ROOT . '/midcom/helper/datamanager2/widget/recaptcha/recaptchalib.php');
/**
 * Datamanager 2 recaptcha widget
 *
 * Uses the reCaptcha service to provide Captcha authentification to a form.
 *
 * The captcha is completely auto-generated, you should not have to worry about any
 * defaults. The captcha rendering is done by external service. reCaptcha uses it's service
 * for digitizing old books.
 *
 * Security considerations:
 * reCaptcha is valid for only one post. If host reloads a page a new reCaptha is created
 *
 * <b>Available configuration options:</b>
 * 
 * <b>Integration Guide:</b>
 *
 * Integrating Captcha support does not need any changes to your components, you just
 * have to add a new field into your DM2 schema using the recaptcha type/widget with NULL
 * storage:
 *
 * <code>
 * 'recaptcha' => Array
 * (
 *     'title' => 'User Verification',
 *     'storage' => null,
 *     'type' => 'recaptcha',
 *     'widget' => 'recaptcha',
 * ),
 * </code>
 * 
 * reCaptcha is configured by setting private and public keys to snippet
 * sitegroup-config/midcom.helper.datamanager2/config
 *
 * <code>
 * 'recaptcha_privkey' => 'get key from recaptcha.org',
 * 'recaptcha_pubkey' => 'get key from recaptcha.org',
 * </code>
 *
 * @package midcom.helper.datamanager2
 */
class midcom_helper_datamanager2_widget_recaptcha extends midcom_helper_datamanager2_widget
{
 
    /**
     * The input textbox used which needs to be frozen when operating on the captcha.
     *
     * @var HTML_QuickForm_Element
     * @access private
     */
    var $_element = null;
    
    /**
     * Public and private keys used by recaptcha
     * TODO: These could come from configuration
     */
    var $_public_key = null;
    var $_private_key = null;
    
    var $_validated = false;

    /**
     * The initialization event handler creates the captcha passphrase (if necessary).
     *
     * @return boolean Indicating Success
     */
    function _on_initialize()
    {
        $this->_public_key = $this->_config->get('recaptcha_pubkey');
        $this->_private_key = $this->_config->get('recaptcha_privkey');

        return true;
    }

    /**
     * Adds a simple single-line text form element at this time.
     */
    function add_elements_to_form()
    {
        $elements = Array();
        
        $attributes = Array
        (
            'class' => 'captcha',
            'id'    => "{$this->_namespace}{$this->name}",
        );

        $static_html = recaptcha_get_html($this->_public_key);
        $static_html = midcom_get_snippet_content_graceful('/sitegroup-config/midcom.helper.datamanager2/recaptcha').$static_html;

        $this->_element = HTML_QuickForm::createElement('static', "{$this->name}_captcha", '', $static_html);
        
        $elements[] = $this->_element;
        $elements[] = HTML_QuickForm::createElement('hidden', $this->name, '', $attributes);
        $this->_form->applyFilter($this->name, 'trim');

        $this->_form->addGroup($elements, "{$this->name}_group", $this->_translate($this->_field['title']),
            '<br />', false);

        $this->_form->addFormRule(Array(&$this, 'validate'));
    }

    /**
     * QF Valiation callback which verifies the passcode against the Captcha.
     */
    function validate($fields)
    {   
        /* For some reason this validation is runned twice. Recaptha gives only one
         * true answer per captcha. So first we check if it has been given 
         */
        if($this->_validated)
        {
            return true;
        }
    
        $resp = recaptcha_check_answer ($this->_private_key,
                                        $_SERVER["REMOTE_ADDR"],
                                        $fields["recaptcha_challenge_field"],
                                        $fields["recaptcha_response_field"]);
                                        
        if (!$resp->is_valid)
        {   
            return Array ("{$this->name}_group" => $this->_l10n->get('captcha validation failed'));
        }
        /*
         * Saving valid value.
         */
        $this->_validated = true;

        return true;
    }

    function get_default()
    {
        return '';
    }

    function sync_type_with_widget($results)
    {
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