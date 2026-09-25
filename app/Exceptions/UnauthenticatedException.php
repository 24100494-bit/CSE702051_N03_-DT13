<?php

namespace App\Exceptions;

class UnauthenticatedException extends ApiException
{
    public function __construct(string $message = 'Ban chua dang nhap')
    {
        parent::__construct(401, 'UNAUTHENTICATED', $message);
    }
}
