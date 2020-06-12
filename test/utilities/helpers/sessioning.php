<?php
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

class mock_sessioning extends midcom_services__sessioning
{
    protected function prepare_storage()
    {
        return new MockArraySessionStorage();
    }
}