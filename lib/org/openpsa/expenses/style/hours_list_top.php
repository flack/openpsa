<h1>&(data['view_title']);</h1>
<?php
if (isset($data['qf'])) {
    $data['qf']->render();
}
?>
