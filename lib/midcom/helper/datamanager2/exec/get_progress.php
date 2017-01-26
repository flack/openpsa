<?php
if ($_GET['id']) {
    echo json_encode(apc_fetch('upload_' . $_GET['id']));
}
