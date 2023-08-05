<div class="content-with-sidebar">
    <div class="main">
	    <?php
	    $data['view']->display_view(true);
        $prefix = midcom_core_context::get()->get_key(MIDCOM_CONTEXT_ANCHORPREFIX);
        midcom::get()->dynamic_load($prefix . 'members/' . $data['group']->guid . '/');
        ?>
    </div>
    <?php midcom_show_style('group-sidebar'); ?>
</div>
