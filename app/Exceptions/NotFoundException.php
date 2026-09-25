<?php

namespace App\Exceptions;

class NotFoundException extends ApiException
{
    public function __construct(string $message = 'Khong tim thay ban ghi')
    {
        parent::__construct(404, 'NOT_FOUND', $message);
    }
}
