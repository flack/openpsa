<?php
/**
 * @author CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @copyright CONTENT CONTROL http://www.contentcontrol-berlin.de/
 * @license http://www.gnu.org/licenses/gpl.html GNU General Public License
 * @package org.openpsa.user
 */

/**
 * OpenPSA password widget
 *
 * @package org.openpsa.user
 */
class org_openpsa_user_widget_password extends midcom_helper_datamanager2_widget
{
    public function __construct($renderer)
    {
        parent::__construct($renderer);
        midcom::get()->head->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.user/password.js');
    }

    /**
     * Adds the input elements to the form.
     */
    public function add_elements_to_form($attributes)
    {
        $elements = [];
        $attributes = array_merge($attributes, ['class' => 'shorttext', 'id' => $this->name . '_input']);

        $menu ='<label><input type="radio" name="org_openpsa_user_person_account_password_switch" value="0" checked="checked"/> ' . $this->_l10n->get("generate_password") . '
            </label>
            <label>
                <input type="radio" name="org_openpsa_user_person_account_password_switch" value="1"/> ' . $this->_l10n->get("own_password") . '
            </label>';

        self::jsinit($this->name . '_input', $this->_l10n, $this->_config, false);
        $elements[] = $this->_form->createElement('static', $this->name . '_menu', '', $menu);
        $title = $this->_translate($this->_field['title']);
        $elements[] = $this->_form->createElement('password', $this->name . '_input', $title, $attributes);
        $this->_form->addGroup($elements, $this->name, $title);
    }

    public static function jsinit($name, midcom_services_i18n_l10n $l10n, midcom_helper_configuration $config, $userid_required)
    {
        $conf = [
            'strings' => [
                'shortPass' => $l10n->get("password too short"),
                'badPass' => $l10n->get("password weak"),
                'goodPass' => $l10n->get("password good"),
                'strongPass' => $l10n->get("password strong"),
                'samePassword' => $l10n->get("username and password identical"),
            ],
            'password_rules' => $config->get('password_score_rules'),
            'min_length' => $config->get('min_password_length'),
            'min_score' => $config->get('min_password_score'),
            'userid_required' => $userid_required
        ];
        $conf = json_encode($conf);
        midcom::get()->head->add_jquery_state_script('$("#' . $name . '").password_widget(' . $conf . ');');
    }

    public function sync_type_with_widget($results)
    {
        if ($results[$this->name] !== null) {
            $this->_type->value = $results[$this->name][$this->name . '_input'];
        }
    }
}
