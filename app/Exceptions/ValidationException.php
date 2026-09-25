<?php

namespace App\Exceptions;

class ValidationException extends ApiException
{
    /**
     * @param array $details vi du: [['field' => 'ten_de_tai', 'issue' => 'Khong duoc de trong']]
     */
    public function __construct(array $details, string $message = 'Du lieu dau vao khong hop le')
    {
        parent::__construct(422, 'VALIDATION_FAILED', $message, $details);
    }
}
