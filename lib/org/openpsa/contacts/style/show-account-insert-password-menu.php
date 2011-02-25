<?php
function getPasswordMenu($data){
	return
        '<div style="margin-left:110px;">' .
        '<div class="element element_text">
	        <div class="input">
	        	<input type="radio" name="org_openpsa_contacts_person_account_password_switch" value="0" onclick="setPasswordRowDisplay(\'none\')" checked="checked"/> '.$data["l10n"]->get("generate_password").'
	        </div>
        	<div class="input">
            	<input type="radio" name="org_openpsa_contacts_person_account_password_switch" value="1" onclick="setPasswordRowDisplay(\'table-row\')"/> '.$data["l10n"]->get("own_password").'
        	</div>
        </div>


        <div class="element element_text" id="password_row" style="display:none">
	        <label for="org_openpsa_contacts_person_account_password">
	            <span class="field_text">'.$data["l10n_midcom"]->get("password").'</span>
	        </label>
        	<div class="input">
            	<input type="text" name="org_openpsa_contacts_person_account_password" id="org_openpsa_contacts_person_account_password" class="shorttext" style="display:inline" maxlength="'.$data["max_length"].'" />
        	</div>
        </div>'.
        '</div>';
}



//insert html to dummy content
$qf =& $data['controller']->formmanager->form;
$renderer =& $data['controller']->formmanager->renderer;

$select = $qf->getElement('password_dummy');
$new_html = getPasswordMenu($data);
$renderer->setElementTemplate($new_html, 'password_dummy');
?>