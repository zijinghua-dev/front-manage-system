<?php

namespace Zijinghua\Zvoyager\Services;

use Zijinghua\Zbasement\Http\Services\BaseService;
use Zijinghua\Zvoyager\Constracts\Services\AuthInterface;
use Zijinghua\Zvoyager\Traits\Credential;

class AuthService extends BaseService implements AuthInterface
{
    use Credential;

    public function __construct()
    {

    }
    public function login($credential)
    {
        $requestData = $this->getCredentials($credential);


    }
}
