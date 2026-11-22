<?php
echo midcom::get()->toolbars->render_view_toolbar();
echo midcom::get()->toolbars->_render_toolbar('navigation');
?>
<script>
    openpsa2_add_toolbar_toggle();
    org_openpsa_layout.clip_toolbar();
</script>