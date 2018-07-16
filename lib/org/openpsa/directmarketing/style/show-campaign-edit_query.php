<?php
$current_rules = $data['campaign']->rules;
$property_map = org_openpsa_directmarketing_campaign_ruleresolver::build_property_map($data['l10n']);
$preview_url = $data['router']->generate('campaign_query', ['guid' => $data['campaign']->guid]);

$grid = $data['grid'];
$grid->add_pager(30)
    ->set_option('height', 600)
    ->set_option('viewrecords', true)
    ->set_option('beforeRequest', 'set_postdata', false)
    ->set_option('url', $preview_url)
    ->set_option('sortname', 'index_lastname');

$grid->set_option('caption', $data['l10n']->get('contacts found'));

$grid->set_column('lastname', $data['l10n']->get('lastname'), 'classes: "title ui-ellipsis"', 'string')
    ->set_column('firstname', $data['l10n']->get('firstname'), 'width: 100, classes: "ui-ellipsis"', 'string')
    ->set_column('email', $data['l10n']->get('email'), 'width: 100, classes: "ui-ellipsis"', 'string');
?>
<!-- Automatically built on PHP level -->
<script type="text/javascript">
    var org_openpsa_directmarketing_edit_query_property_map = <?php echo json_encode($property_map); ?>;
    var org_openpsa_directmarketing_edit_query_match_map = {
        'LIKE': '<?php echo $data['l10n']->get('contains'); ?>',
        'NOT LIKE': '<?php echo $data['l10n']->get('does not contain'); ?>',
        '=': '<?php echo $data['l10n']->get('equals'); ?>',
        '<>': '<?php echo $data['l10n']->get('not equals'); ?>',
        '<': '<?php echo $data['l10n']->get('less than'); ?>',
        '>': '<?php echo $data['l10n']->get('greater than'); ?>'
    };
    var org_openpsa_directmarketing_edit_query_l10n_map = {
        'in_domain': '<?php echo $data['l10n']->get('in domain'); ?>',
        'with_name': '<?php echo $data['l10n']->get('with name'); ?>',
        'add_rule': '<?php echo $data['l10n']->get('add rule'); ?>',
        'add_group': '<?php echo $data['l10n']->get('add group'); ?>',
        'remove_group': '<?php echo $data['l10n']->get('remove group'); ?>',
        'remove_rule': '<?php echo $data['l10n']->get('remove rule'); ?>',
        'static_url': '<?php echo MIDCOM_STATIC_URL; ?>'
    }
    var org_openpsa_directmarketing_group_select_map = {
    'AND': '<?php echo $data['l10n']->get('and'); ?>',
    'OR': '<?php echo $data['l10n']->get('or'); ?>'
    }

    var org_openpsa_directmarketing_class_map = {
    'person': 'org_openpsa_contacts_person_dba',
    'group': 'org_openpsa_contacts_group_dba',
    'membership': 'midgard_member',
    'org_openpsa_contacts_person_dba' : 'person',
    'org_openpsa_contacts_group_dba' : 'group',
    'midgard_member': 'membership',
    'generic_parameters': 'midgard_parameter',
    'midgard_parameter': 'generic_parameters'
    }
</script>

<h2><?php echo $data['l10n']->get('rules wizard'); ?></h2>

<div class="wide">
    <form name="org_openpsa_directmarketing_rules_editor" id="org_openpsa_directmarketing_rules_editor" enctype="multipart/form-data" method="post" action="" onsubmit="return get_rules_array(zero_group_id);" class="datamanager2 org_openpsa_directmarketing_edit_query">

<textarea class="longtext" cols="50" rows="25" name="midcom_helper_datamanager2_dummy_field_rules" id="midcom_helper_datamanager2_dummy_field_rules"><?php
        echo $data['campaign']->rulesSerialized;
?></textarea>

    <div id="dirmar_rules_editor_container">
    </div>
        <div class="form_toolbar" id="org_openpsa_directmarketing_rules_editor_form_toolbar">
            <input name="midcom_helper_datamanager2_save[0]" accesskey="s" class="submit save" value="<?php echo $data['l10n_midcom']->get('save'); ?>" type="submit" />
            <input name="midcom_helper_datamanager2_cancel[0]" class="submit cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" type="submit" />
            <input id="show_rule_preview" name="show_rule_preview" class="submit preview" value="<?php echo $data['l10n']->get('preview'); ?>" type="button" />
        </div>
        <script type="text/javascript">
        jQuery(document).ready(function()
        {
            init("dirmar_rules_editor_container", <?php echo $data['campaign']->rulesSerialized; ?>);
        });
        </script>
    </form>

    <div id="preview">
        <div class="org_openpsa_directmarketing full-width fill-height">
            <?php $grid->render(); ?>
        </div>
    </div>
</div>