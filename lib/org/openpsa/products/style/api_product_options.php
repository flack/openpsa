<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<div class="main">
    <h1><?php echo $data['l10n']->get('export'); ?></h1>

    <p>
        <?php
        echo $data['l10n']->get('you can export csv files here');
        ?>
    </p>

    <form enctype="multipart/form-data" action="&(prefix);api/product/csv/" method="post" class="datamanager">
        <label for="org_openpsa_products_export_schema">
            <span class="field_text"><?php echo $data['l10n']->get('schema'); ?></span>
            <select class="dropdown" name="org_openpsa_products_export_schema" id="org_openpsa_products_export_schema">
                <?php
                // FIXME: this schema counting is *really* inefficient
                $root_group_guid = $data['config']->get('root_group');
                if (!empty($root_group_guid))
                {
                    try
                    {
                        $root_group = org_openpsa_products_product_group_dba::get_cached($root_group_guid);
                        $qb_groups = org_openpsa_products_product_group_dba::new_query_builder();
                        $qb_groups->add_constraint('up', 'INTREE', $root_group->id);
                        $groups = $qb_groups->execute();
                    }
                    catch (midcom_error $e)
                    {
                        $root_group = null;
                    }

                }

                foreach (array_keys($data['schemadb_product']) as $name)
                {
                    $count_by_schema = 0;
                    if ($root_group)
                    {
                        $qb = org_openpsa_products_product_dba::new_query_builder();
                        $qb->add_constraint('code', '<>', '');
                        $qb->begin_group('OR');
                        $qb->add_constraint('productGroup', '=', $root_group->id);
                        foreach($groups as $group)
                        {
                            $qb->add_constraint('productGroup', '=', $group->id);
                        }
                        $qb->end_group('OR');

                        $products = $qb->execute();

                        foreach ($products as $product)
                        {
                           if ($product->get_parameter('midcom.helper.datamanager2', 'schema_name') == $name)
                           {
                               $count_by_schema++;
                           }
                        }
                    }
                    $count_by_schema = ' ('.$count_by_schema.')';
                    echo "                <option value=\"{$name}\">" . $data['l10n']->get($data['schemadb_product'][$name]->description) . $count_by_schema . "</option>\n";
                }
                ?>
            </select>
        </label>
        <?php
        if ($root_group)
        {
        ?>
        <label for="org_openpsa_products_export_all">
            <span class="field_text"><?php echo $data['l10n']->get('export groups'); ?></span>
            <select class="dropdown" name="org_openpsa_products_export_all" id="org_openpsa_products_export_all">
                <option value="0"><?php echo $data['l10n']->get('only current product group'); ?></option>
                <option value="0"><?php echo $data['l10n']->get('all product groups (that are subgroups of current one)'); ?></option>
            </select>
        </label>
        <?php
        }
        ?>
        <div class="form_toolbar">
            <input type="submit" class="save" value="<?php echo $data['l10n']->get('export'); ?>" />
        </div>
    </form>
</div>
