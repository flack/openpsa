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
        $this->_component = 'org.openpsa.user';
        midcom::get('head')->add_jsfile(MIDCOM_STATIC_URL . '/org.openpsa.user/password.js');
    }

    /**
     * Adds the input elements to the form.
     */
    public function add_elements_to_form($attributes)
    {
        $elements = array();
    	$attributes = array_merge($attributes, array('class' => 'shorttext', 'id' => $this->name . '_input'));

        $menu ='<label><input type="radio" name="org_openpsa_user_person_account_password_switch" value="0" checked="checked"/> ' . $this->_l10n->get("generate_password") . '
            </label>
            <label>
                <input type="radio" name="org_openpsa_user_person_account_password_switch" value="1"/> ' . $this->_l10n->get("own_password") . '
            </label>';

        $jsinit = $this->_prepare_jsinit();
        $elements[] = HTML_QuickForm::createElement('static', $this->name . '_menu', '', $menu);
        $title = $this->_translate($this->_field['title']);
        $elements[] = HTML_QuickForm::createElement('password', $this->name . '_input', $title, $attributes);
        $elements[] = HTML_QuickForm::createElement('static', $this->name . '_jsinit', '', $jsinit);
        $this->_form->addGroup($elements, $this->name, $this->_field['title']);
    }

    private function _prepare_jsinit()
    {
        $strings = array
        (
            'shortPass' => $this->_l10n->get("password too short"),
            'badPass' => $this->_l10n->get("password weak"),
            'goodPass' => $this->_l10n->get("password good"),
            'strongPass' => $this->_l10n->get("password strong"),
            'samePassword' => $this->_l10n->get("username and password identical"),
        );
        $strings = json_encode($strings);

        $password_rules = '';
    	foreach($this->_config->get('password_score_rules') as $rule)
        {
            $password_rules .= " if (password.match(" . $rule['match'] . ")){ score += " . $rule['score'] . ";}";
        }


        $jsinit = <<<EOT
        <script type="text/javascript">
        var org_openpsa_user_password_strings = {$strings};

        org_openpsa_user_password_rules = function(password)
        {
            var score = 0;
            {$password_rules}
            return score;
        }

        $("#{$this->name}_input").password_widget({
              min_length: {$this->_config->get('min_password_length')},
              min_score: {$this->_config->get('min_password_score')},
              password_id: "{$this->name}_input",
              userid_required: false
        });
        </script>
EOT;
        return $jsinit;
    }

    public function get_default()
    {
        return array
        (
            $this->name => '',
        );
    }

    public function sync_type_with_widget($results)
    {
        if ($results[$this->name] !== null)
        {
            $this->_type->value = $results[$this->name][$this->name . '_input'];
        }
    }
}
?>