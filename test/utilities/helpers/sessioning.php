<?php
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Symfony\Component\HttpFoundation\Request;

class mock_sessioning extends midcom_services__sessioning
{
    protected function prepare_storage(?Request $request, bool $cookie_secure)
    {
        return new MockArraySessionStorage();
    }
}