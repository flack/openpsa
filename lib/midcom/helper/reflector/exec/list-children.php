<?php
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

$guid = Request::createFromGlobals()->query->getAlnum('guid');

$parent = midcom::get()->dbfactory->get_object_by_guid($guid);
$response = new JsonResponse(midcom_helper_reflector_tree::get_tree($parent));
$response->send();
