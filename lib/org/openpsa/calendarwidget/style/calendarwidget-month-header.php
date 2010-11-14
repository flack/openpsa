<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
?>
<table class="org_openpsa_calendarwidget_month">
    <thead>
        <tr class="navigation">
            <th class="previous">
<?php
if (   $data['calendar_widget']->first_year
    && $data['calendar_widget']->_previous_year < $data['calendar_widget']->first_year)
{
    echo "                &nbsp;\n";
}
else
{
?>
                <a href="&(prefix);calendar/&(data['previous_year']:h);/&(data['previous_month']:h);/">&lt;&lt;</a>
<?php
}
?>
            </th>
            <th class="month" colspan="6">
                <?php echo strftime('%B', $data['month_start']); ?>
            </th>
            <th class="next">
<?php
if (   $data['calendar_widget']->last_year
    && $data['calendar_widget']->_next_year > $data['calendar_widget']->last_year)
{
    echo "                &nbsp;\n";
}
else
{
?>
                <a href="&(prefix);calendar/&(data['next_year']:h);/&(data['next_month']:h);/">&gt;&gt;</a>
<?php
}
?>
            </th>
        </tr>
        <tr class="daynames">
            <th class="week">
                <?php echo $_MIDCOM->i18n->get_string('week', 'org.openpsa.calendarwidget'); ?>
            </th>
<?php
for ($i = 1; $i <= 7; ++$i)
{
    if (   $i === 6
        || $i === 7)
    {
        $class = 'weekend';
    }
    else
    {
        $class = 'weekday';
    }
?>
            <th class="<?php echo strtolower(date('l', mktime(0, 0, 0, 12, 2 + $i, 2007))); ?> &(class);">
                <?php echo strftime('%a', mktime(0, 0, 0, 12, 2 + $i, 2007)); ?>
            </th>

<?php
}
?>
        </tr>
    </thead>
    <tbody>
