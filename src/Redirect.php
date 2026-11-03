<?php

declare(strict_types=1);

namespace LegalQueryWeb;

/**
 * Thrown to answer with a redirect (301 for canonical URLs, 302 for form submits).
 */
final class Redirect extends \RuntimeException
{
    public function __construct(public readonly string $location, public readonly int $status = 301)
    {
        parent::__construct("Redirect $status to $location");
    }
}
