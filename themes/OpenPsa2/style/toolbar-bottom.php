<?php
echo midcom::get()->toolbars->render_view_toolbar();
echo midcom::get()->toolbars->_render_toolbar('navigation');
?>
<script>
    org_openpsa_layout.clip_toolbar();
</script>