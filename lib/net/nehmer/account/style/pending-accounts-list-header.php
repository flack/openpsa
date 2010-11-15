<h1><?php echo $data['l10n']->get('pending approvals'); ?></h1>
<form id="net_nehmer_account_pending" method="post" action="<?php echo midcom_connection::get_url('uri'); ?>multiple/">
    <table id="net_nehmer_account_pending_table" class="sortable">
        <thead>
            <tr>
                <th class="marker">
                </th>
                <th>
                    <?php echo $data['l10n']->get('name'); ?>
                </th>
                <th>
                    <?php echo $data['l10n']->get('username'); ?>
                </th>
                <th>
                    <?php echo $data['l10n']->get('email'); ?>
                </th>
                <th>
                    <?php echo $data['l10n']->get('apply date'); ?>
                </th>
            </tr>
        </thead>
        <tbody>
