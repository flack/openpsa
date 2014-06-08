<h2><?php echo $data['customer_label']; ?></h2>

<form method="post" action="">
    <table class="list">
        <thead>
            <tr>
                <th><?php echo $data['l10n']->get('invoice'); ?></th>
                <th><?php echo midcom::get()->i18n->get_string('task', 'org.openpsa.projects'); ?></th>
                <th><?php echo midcom::get()->i18n->get_string('hours', 'org.openpsa.projects'); ?></th>
                <th><?php echo $data['l10n']->get('units'); ?></th>
                <th><?php echo $data['l10n']->get('price per unit'); ?></th>
                <th><?php echo $data['l10n']->get('sum'); ?></th>
            </tr>
        </thead>
        <tfoot>
            <tr class="primary">
                <td>&nbsp;</td>
                <td><?php echo $data['l10n']->get('totals'); ?></td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td>&nbsp;</td>
                <td class="numeric"><span class="totals"></span></td>
            </tr>
        </tfoot>
        <tbody>