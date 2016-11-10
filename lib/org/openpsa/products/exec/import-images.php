<?php
midcom::get()->auth->require_admin_user();

if (   !isset($_POST['address'])
    || !strstr($_POST['address'], '__PRODUCT_CODE__')) {
    ?>
    <h1>Import product images</h1>

    <p>You can import product images that have the product code in their filename/URL here.
    Type the address format below with string <code>__PRODUCT_CODE__</code> showing where the code should go to.</p>

    <form method="post">
        <label>
            URL/path
            <input type="text" name="address" value="/tmp/__PRODUCT_CODE__.jpg" />
        </label>
        <input type="submit" value="Import images" />
    </form>
    <?php

} else {
    midcom::get()->disable_limits();

    // Import product images
    $qb = org_openpsa_products_product_dba::new_query_builder();
    $qb->add_constraint('code', '<>', '');
    $products = $qb->execute();

    $schemadb = midcom_baseclasses_components_configuration::get('org.openpsa.products', 'config')->get('schemadb_product');
    $schema = midcom_helper_datamanager2_schema::load_database($schema);
    $datamanager = new midcom_helper_datamanager2_datamanager($schema);
    foreach ($products as $product) {
        // Get old image
        $image = file_get_contents(str_replace('__PRODUCT_CODE__', $product->code, $_POST['address']));
        if (empty($image)) {
            continue;
        }

        // Save image to a temp file
        $tmp_name = tempnam(midcom::get()->config->get('midcom_tempdir'), 'org_openpsa_products_product_oldimage_');

        if (!file_put_contents($tmp_name, $image)) {
            //Could not write, clean up and continue
            echo("Error when writing file {$tmp_name}");
            continue;
        }

        $datamanager->autoset_storage($product);
        $datamanager->types['image']->set_image("{$product->code}.jpg", $tmp_name, $product->title);
        $datamanager->save();
    }
}
?>