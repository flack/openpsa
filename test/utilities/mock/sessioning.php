<?php
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class mock_sessioning extends midcom_services__sessioning
{
    protected function prepare_storage($cookie_path, $cookie_secure)
    {
        return new MockArraySessionStorage();
    }
}