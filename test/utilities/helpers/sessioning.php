<?php
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Request;

class mock_sessioning extends midcom_services__sessioning
{
    protected function prepare_storage(Request $request = null)
    {
        return new MockArraySessionStorage();
    }
}