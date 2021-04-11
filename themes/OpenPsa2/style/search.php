<?php
use midcom\datamanager\helper\autocomplete;
?>
<form action="" id="org_openpsa_search_form" method="get">
  <div>
    <input type="text" value="" name="query" id="org_openpsa_search_query"/>
  </div>
</form>
<?php
$providers = org_openpsa_widgets_ui::get_search_providers();
foreach ($providers as $config) {
    if ($config['autocomplete'] === true) {
        autocomplete::add_head_elements();
    }
} ?>
<script type="text/javascript">
org_openpsa_layout.initialize_search(<?php echo json_encode($providers) ?>,
    "<?php echo midgard_admin_asgard_plugin::get_preference('openpsa2_search_provider', false) ?>"
);
</script>