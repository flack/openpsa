<?php
$email_fields = $data['config']->get('email_fields');
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$contacts = array();
$total_contacts = 0;
?>

<h2><?php echo $data['l10n']->get("import contacts"); ?></h2>

<?php
if ($_MIDCOM->componentloader->is_installed('com.magnettechnologies.contactgrabber'))
{
    $_MIDCOM->load_library('com.magnettechnologies.contactgrabber');
    $crabber = new com_magnettechnologies_contactgrabber();
    $crabber_contacts = $crabber->grab_contacts();

    if (is_array($crabber_contacts))
    {
        foreach($crabber_contacts['name'] as $key => $name)
        {
            $contacts[$key]['name'] = $name;
            $contacts[$key]['email'] = $crabber_contacts['email'][$key];
        }
    }
}

$total_contacts = $email_fields + count($contacts);
?>

<h2><?php echo $data['l10n']->get("add contacts"); ?></h2>

<form  method="post">
<input type="hidden" name="net_nehmer_accounts_invitation_total_contacts" value="<?php echo $total_contacts; ?>"/>

<label>Message</label>
<textarea class="net_nehmer_accounts_invitation_email_message" name="net_nehmer_accounts_invitation_email_message"></textarea>

<table class="net_nehmer_accounts_invitation_emails">
  <tr>
    <th><?php echo $data['l10n']->get("name"); ?> </th>
    <th><?php echo $data['l10n']->get("email"); ?></th>
  </tr>
<?php
    for ($i = 0; $i < $email_fields; $i++)
    {
    ?>
      <tr>
        <td>
        <input type="text" name="net_nehmer_accounts_invitation_invitee_name_<?php echo $i; ?>"/>
        </td>
    <td>
        <input type="text" name="net_nehmer_accounts_invitation_invitee_email_<?php echo $i; ?>"/>
    </td>
     </tr>
    <?php
    }

    foreach ($contacts as $key => $contact)
    {
    ?>
      <tr>
        <td>
        <input type="checkbox" name="net_nehmer_accounts_invitation_invitee_selected_<?php echo $key+$email_fields; ?>"/>
    <span class="net_nemein_accounts_invitation_import_name"><?php echo $contact['name']; ?></span>
    </td>
    <td>
        <input type="hidden" name="net_nehmer_accounts_invitation_invitee_name_<?php echo $key+$email_fields; ?>" value="<?php echo $contact['name']; ?>"/>
        <input type="hidden" name="net_nehmer_accounts_invitation_invitee_email_<?php echo $key+$email_fields; ?>" value="<?php echo $contact['email']; ?>"/>
    <span class="net_nemein_accounts_invitation_import_email"><?php echo $contact['email']; ?></span>
    </td>
      </tr>
    <?php
    }
?>
<tr>
  <td>
  <input type="submit" name="net_nehmer_accounts_invitation_submit" value="<?php echo $data['l10n']->get("submit"); ?>"/>
  </td>
  <td>
  </td>
</table>
</form>

