<form action="" id="org_openpsa_search_form" method="get">
    <input type="text" value="" name="query" id="org_openpsa_search_query"/>
</form>
<script type="text/javascript">
org_openpsa_layout.initialize_search
(
    <?php echo json_encode(org_openpsa_core_ui::get_search_providers());?>,
    '<?php echo midgard_admin_asgard_plugin::get_preference('openpsa2_search_provider'); ?>'
);
</script>
