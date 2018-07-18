<?php
$component = $data['component_data'];
$link = $data['router']->generate('components_component', ['component' => $component['name']]);
?>
<li><h3><a href="&(link);/"><i class="fa fa-&(component['icon']);"></i> &(component['name']);</a></h3>
    <div class="details">
        <span class="description">&(component['title']);</span>
        <?php echo $component['toolbar']->render(); ?>
    </div>
</li>
