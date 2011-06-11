<?php
$class = '';

if ($data['outside_month'])
{
    $class .= ' outside';
}

if (count($data['events']) > 0)
{
    $class .= ' events';
}

if ($data['today'])
{
    $class .= ' today';
}
?>
        <td class="<?php echo strtolower(date('l', $data['day'])); ?>&(class:h);">
            <span class="date"><?php echo strftime('%d', $data['day']); ?></span>
