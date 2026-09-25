<?php

namespace App\Exceptions;

class ForbiddenException extends ApiException
{
    public function __construct(string $message = 'Ban khong co quyen thuc hien thao tac nay')
    {
        parent::__construct(403, 'FORBIDDEN', $message);
    }
}
