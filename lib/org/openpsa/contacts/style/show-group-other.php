<?php
$grid = $data['members_grid'];
$grid->set_option('loadonce', true);
$grid->set_option('caption', $data['l10n']->get('members'));

$grid->set_column('firstname', $data['l10n']->get('firstname'), '', 'string');
$grid->set_column('lastname', $data['l10n']->get('lastname'), '', 'string');
$grid->set_column('email', $data['l10n']->get('email'), '', 'string');
?>
<div class="sidebar">
  <div class="area org_openpsa_helper_box">
    <h3><?php echo $data['l10n']->get('groups'); ?></h3>
    <?php
        $data['group_tree']->render();
    ?>
  </div>
</div>

<div class="main">
    <?php
    // Display the group information
    foreach ($data['view'] as $fieldname => $fielddata)
    {
        if (!$fielddata)
        {
            continue;
        }
        switch ($fieldname)
        {
            case 'members':
                break;
            case 'notes':
                echo "<h2>" . $data['l10n']->get('notes') . "</h2>\n";
                echo "<pre>" . $fielddata . "</pre>";
                break;

            case 'official':
            case 'name':
                echo "<div><strong>" . $data['l10n']->get($fieldname) . ": </strong>";
                echo $fielddata . "</div>";
                break;
        }
    }
    ?>
	<br />
	<div class="org_openpsa_contacts_members full-width crop-height">
        <?php $grid->render($data['members']); ?>
	</div>
</div>
