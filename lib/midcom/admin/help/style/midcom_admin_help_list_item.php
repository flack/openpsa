<?php
$component = $data['component_data'];
$url = $data['router']->generate('component', ['component' => $component['name']]);
?>
<li><a href="&(url);"><i class="fa fa-&(component['icon']);"></i> &(component['name']);</a>
    <span class="description">&(component['title']);</span>
</li>
