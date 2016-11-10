<?php
$options = array(
    '' => '',
    org_openpsa_products_product_dba::TYPE_SERVICE => $data['l10n']->get('service'),
    org_openpsa_products_product_dba::TYPE_GOODS => $data['l10n']->get('material goods'),
    org_openpsa_products_product_dba::TYPE_SOLUTION => $data['l10n']->get('solution'),
);

if (!function_exists('org_openpsa_products_search_value_helper')) {
    function org_openpsa_products_search_value_helper($request_key)
    {
        if (isset($_REQUEST['org_openpsa_products_search'][$request_key]['value'])) {
            echo ' value=' . midcom_helper_xsspreventer::escape_attribute($_REQUEST['org_openpsa_products_search'][$request_key]['value']);
        }
    }
}
?>
<form method="get" class="datamanager">

    <label>
        <span class="field_text">match</span>
        <select name="org_openpsa_products_search_type">
            <option value="AND"<?php if (isset($_REQUEST['org_openpsa_products_search_type']) && $_REQUEST['org_openpsa_products_search_type'] == 'AND') {
    echo ' selected';
} ?>>All of the following</option>
            <option value="OR"<?php if (isset($_REQUEST['org_openpsa_products_search_type']) && $_REQUEST['org_openpsa_products_search_type'] == 'OR') {
    echo ' selected';
} ?>>Any of the following</option>
        </select>
    </label>

    <input type="hidden" name="org_openpsa_products_search[1][property]" value="title" />
    <input type="hidden" name="org_openpsa_products_search[1][constraint]" value="LIKE" />
    <label>
        <span class="field_text"><?php printf($data['l10n']->get('%s includes'), $data['l10n_midcom']->get('title')); ?></span>
        <input class="shorttext" type="text" name="org_openpsa_products_search[1][value]"<?php org_openpsa_products_search_value_helper(1); ?> />
    </label>

    <input type="hidden" name="org_openpsa_products_search[2][property]" value="price" />
    <input type="hidden" name="org_openpsa_products_search[2][constraint]" value=">=" />
    <label>
        <span class="field_text"><?php printf($data['l10n']->get('%s is at least'), $data['l10n']->get('price')); ?></span>
        <input class="shorttext" type="text" name="org_openpsa_products_search[2][value]"<?php org_openpsa_products_search_value_helper(2); ?> />
    </label>

    <input type="hidden" name="org_openpsa_products_search[3][property]" value="orgOpenpsaObtype" />
    <input type="hidden" name="org_openpsa_products_search[3][constraint]" value=">=" />
    <label>
        <span class="field_text"><?php printf($data['l10n']->get('%s is'), $data['l10n']->get('type')); ?></span>
        <select name="org_openpsa_products_search[3][value]">
        <?php
        foreach ($options as $key => $value) {
            $selected = '';
            if (   isset($_REQUEST['org_openpsa_products_search'][3]['value'])
                && $_REQUEST['org_openpsa_products_search'][3]['value'] == $key) {
                $selected = ' selected';
            }
            $key_esc = midcom_helper_xsspreventer::escape_attribute($key);
            $value_esc = midcom_helper_xsspreventer::escape_element('option', $value);
            echo "        <option value={$key_esc}{$selected}>{$value_esc}</option>\n";
        }
        ?>
        </select>
    </label>

    <div class="form_toolbar">
        <input type="submit" accesskey="s" class="search" value="<?php echo $data['l10n']->get('search'); ?>" />
    </div>
</form>