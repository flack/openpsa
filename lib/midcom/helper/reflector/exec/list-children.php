<?php
$parent = midcom::get()->dbfactory->get_object_by_guid($_GET['guid']);
$response = new midcom_response_json(midcom_helper_reflector_tree::get_tree($parent));
$response->send();
