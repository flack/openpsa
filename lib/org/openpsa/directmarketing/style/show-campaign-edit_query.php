<?php
$current_rules =& $data['campaign']->rules;
if (isset($data['new_rule_from'])
    && is_array($data['new_rule_from']))
{
    $generated_from =& $data['new_rule_from'];
}
else if (array_key_exists('generated_from', $current_rules))
{
    $generated_from =& $current_rules['generated_from'];
}
else
{
    $generated_from = array
    (
        'type' => 'AND',
        'rows' => array(),
    );
}

if (!function_exists('list_object_properties'))
{
    // PONDER: Should we support schema somehow (only for non-parameter keys), this would practically require manual parsing...
    function list_object_properties(&$object, &$l10n)
    {
        // These are internal to midgard and/or not valid QB constraints
        $skip_properties = array();
        // These will be deprecated soon
        $skip_properties[] = 'orgOpenpsaAccesstype';
        $skip_properties[] = 'orgOpenpsaWgtype';

        if ($_MIDCOM->dbfactory->is_a($object, 'org_openpsa_person'))
        {
            // The info field is a special case
            $skip_properties[] = 'info';
            // These legacy fields are rarely used
            $skip_properties[] = 'topic';
            $skip_properties[] = 'subtopic';
            $skip_properties[] = 'office';
            // This makes very little sense as a constraint
            $skip_properties[] = 'img';
            // Duh
            $skip_properties[] = 'password';
        }
        if ($_MIDCOM->dbfactory->is_a($object, 'midgard_member'))
        {
            // The info field is a special case
            $skip_properties[] = 'info';
        }
        // Skip metadata for now
        $skip_properties[] = 'metadata';
        $ret = array();
        while (list ($property, $value) = each($object))
        {
            if (   preg_match('/^_/', $property)
                || in_array($property, $skip_properties))
            {
                // Skip private or otherwise invalid properties
                continue;
            }
            if (is_object($value))
            {
                while (list ($property2, $value2) = each($value))
                {
                    $prop_merged = "{$property}.{$property2}";
                    $ret[$prop_merged] = $l10n->get("property:{$prop_merged}");
                }
            }
            else
            {
                $ret[$property] = $l10n->get("property:{$property}");
            }
        }
        asort($ret);
        return $ret;
    }
}

$tmp_person = new org_openpsa_person();
$tmp_group = new org_openpsa_organization();
$tmp_member = new midgard_member();
$properties_map = array
(
    'person' => list_object_properties($tmp_person, $data['l10n']),
    'group' => list_object_properties($tmp_group, $data['l10n']),
    'membership' => list_object_properties($tmp_member, $data['l10n']),
);
?>
<!-- Automatically built on PHP level -->
<script type="text/javascript">
    var org_openpsa_directmarketing_edit_query_property_map = {
<?php
$cnt = count($properties_map);
$i = 0;
foreach ($properties_map as $class => $properties)
{
    $i++;
    echo "        '{$class}': {\n";
    echo "             localized: '" . $data['l10n']->get("class:{$class}") . "',\n";
    echo "             parameters: false,\n";
    echo "             properties: {\n";
    $cnt2 = count($properties);
    $i2 = 0;
    foreach ($properties as $property => $localized)
    {
        $i2++;
        if ($i2 < $cnt2)
        {
            echo "                 {$property}: '{$localized}',\n";
        }
        else
        {
            echo "                 {$property}: '{$localized}'\n";
        }
        }
        echo "             }\n";

        if ($i < $cnt)
        {
            echo "        },\n";
        }
        else
        {
            echo "        },\n";
            echo "        'generic_parameters': {\n";
            echo "            localized: '" . $data['l10n']->get("class:generic parameters") . "',\n";
            echo "            parameters: true,\n";
            echo "            properties: false\n";
            echo "        }\n";
        }
    }
?>
    };
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
    //current_rules
    var rules_array = <?php echo json_encode($current_rules);?>;

    //error-message for unknown class
    var error_message_class = <?php echo json_encode($data['l10n']->get('unknown class please use advanced editor'));?>;
</script>

<h2><?php echo $data['l10n']->get('rules wizard'); ?></h2>

<div class="wide">
    <form name="org_openpsa_directmarketing_rules_editor" id="org_openpsa_directmarketing_rules_editor" enctype="multipart/form-data" method="post" onsubmit="return get_rules_array(zero_group_id);" class="datamanager2 org_openpsa_directmarketing_edit_query">


<textarea class="longtext" cols="50" rows="25" name="midcom_helper_datamanager2_dummy_field_rules" id="midcom_helper_datamanager2_dummy_field_rules" style="display: none;">
</textarea>

    <fieldset class="anyalll">
    <div id="org_openpsa_directmarketing_rules_editor_container" name="org_openpsa_directmarketing_rules_editor_container">
    </div>
        <div class="form_toolbar" id="org_openpsa_directmarketing_rules_editor_form_toolbar">
            <input name="midcom_helper_datamanager2_save" accesskey="s" class="save" value="<?php echo $data['l10n_midcom']->get('save'); ?>" type="submit" />
            <input name="midcom_helper_datamanager2_cancel" class="cancel" value="<?php echo $data['l10n_midcom']->get('cancel'); ?>" type="submit" />
            <input name="show_rule_preview" onclick="send_preview();" class="preview" value="<?php echo $data['l10n']->get('preview'); ?>" type="button" />
        </div>
        <script type="text/javascript">

        //function to display rules given of php
        function get_old_rules()
        {
            var group_id = first_group("org_openpsa_directmarketing_rules_editor_container" ,<?php
            // pass type of first rule_group to javascript, if there is one
            if (count($current_rules) > 0 )
            {
                if(isset($current_rules['type']))
                {
                    echo '"'.$current_rules['type'].'"';
                }
                else
                {
                    echo '"'.$current_rules['groups'].'"';
                }
            }
            else
            {
                echo "false";
            }
            ?>);
            <?php
                // add an empty rule if no rules are currently given
                if(count($current_rules) > 0 && empty($current_rules['classes']))
                {
                    echo "groups[group_id].add_rule(false);";
                }
            ?>
            get_child_rules(group_id , rules_array['classes']);
        }
        jQuery(document).ready(function()
        {
            get_old_rules();
        });
        </script>
    </form>

    <div id="preview_persons" style="padding-top:20px;">
    </div>
</div>