<?php
$prefix = $_MIDCOM->get_context_data(MIDCOM_CONTEXT_ANCHORPREFIX);
$view = $data['view_event'];

if (date('Y-m-d', $data['event']->start) === date('Y-m-d', $data['day']))
{
    $start_time = strftime('%H:%M - ', $data['event']->start);
}
else
{
    $start_time = strftime('%x %H:%M - ', $data['event']->start);
}

if (date('Y-m-d', $data['event']->start) === date('Y-m-d', $data['event']->end))
{
    $end_time = strftime('%H:%M', $data['event']->end);
}
else
{
    $end_time = strftime('%x %H:%M', $data['event']->end);
}
?>
    <li class="vevent">
        <h3>
            <a class="summary url" href="&(prefix);&(view['name']:h);/">&(view['title']:h);</a>
        </h3>
        <abbr class="dtstart" title="<?php echo strftime('%Y-%m-%dT%H:%M:%S%z', $data['event']->start); ?>">&(start_time:h);</abbr>
        <abbr class="dtend" title="<?php echo strftime('%Y-%m-%dT%H:%M:%S%z', $data['event']->end); ?>">&(end_time:h);</abbr>
        <div class="location">&(view['location']:h);</div>
        <span class="guid" style="display: none;"><?php echo $data['event']->guid; ?></span>
    </li>
