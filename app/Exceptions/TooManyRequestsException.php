<?php

namespace App\Exceptions;

/** 429 - goi qua nhieu lan; $retryAfter (giay) duoc dat vao header Retry-After */
class TooManyRequestsException extends ApiException
{
    private int $retryAfter;

    public function __construct(string $message, int $retryAfter)
    {
        parent::__construct(429, 'TOO_MANY_ATTEMPTS', $message);
        $this->retryAfter = max(1, $retryAfter);
    }

    public function getRetryAfter(): int
    {
        return $this->retryAfter;
    }
}
