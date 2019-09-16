<div id="breadcrumb">
    <div class="breadcrumb-path">
        <?php
        $nap = new midcom_helper_nav();
        echo $nap->get_breadcrumb_line(' &raquo; ', 'breadcrumb_link', 0, 'current-page');
        ?>
    </div>
    <?php
    echo '<h1>' . midcom_core_context::get()->get_key(MIDCOM_CONTEXT_PAGETITLE) . '</h1>';
    ?>
</div>