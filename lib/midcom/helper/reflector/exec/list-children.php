<?php
use Symfony\Component\HttpFoundation\JsonResponse;

$parent = midcom::get()->dbfactory->get_object_by_guid($_GET['guid']);
$response = new JsonResponse(midcom_helper_reflector_tree::get_tree($parent));
$response->send();
