<div id="org_openpsa_calendar_calendarwidget"></div>
<div class="wide">
    <h2><?php echo strftime("%A %x", $data['selected_time']); ?></h2>
    <?php $data['calendar']->show(); ?>
</div>