<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Thrown to end a request with an error status (404, 405, 503 ...).
 */
final class HttpError extends \RuntimeException
{
    public function __construct(public readonly int $status, string $message = '')
    {
        parent::__construct($message !== '' ? $message : "HTTP $status", $status);
    }
}
