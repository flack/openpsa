<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);

$year = (int) date('Y', $data['week_start']);
$week_no = date('Y-m-d', $data['week_start']);
?>
        <tr>
            <th class="week-number">
<?php
if (   $year >= $data['first_year']
    && $year <= $data['last_year'])
{
?>
                <a href="&(prefix);week/&(week_no);/"><?php echo strftime('%V', $data['week_start']); ?></a>
<?php
}
else
{
?>
                <?php echo strftime('%V', $data['week_start']); ?>

<?php
}
?>
            </th>
