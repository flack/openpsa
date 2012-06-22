<?php
$prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);

$grid = $data['members_grid'];
$grid->set_option('scroll', 1);
$grid->set_option('rowNum', 30);
$grid->set_option('height', 600);
$grid->set_option('viewrecords', true);
$grid->set_option('url', $prefix . 'group/json/' . $data['group']->guid . '/');

$grid->set_column('lastname', $data['l10n']->get('lastname'), 'classes: "title ui-ellipsis"', 'string')
    ->set_column('firstname', $data['l10n']->get('firstname'), 'width: 100, classes: "ui-ellipsis"', 'string')
    ->set_column('username', $data['l10n']->get('username'), 'width: 100, classes: "ui-ellipsis"')
    ->set_column('email', $data['l10n']->get('email'), 'width: 100, classes: "ui-ellipsis"');

?>
<div class="sidebar">
    <div class="area org_openpsa_helper_box">
        <h3>
            <?php echo $data['l10n']->get('groups'); ?>
        </h3>
        <?php $data['group_tree']->render(); ?>
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
    <div class="org_openpsa_contacts_members full-width fill-height">
        <?php $grid->render(); ?>
    </div>
</div>