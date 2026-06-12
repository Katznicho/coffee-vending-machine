<?php

namespace App\Services;

use RuntimeException;

class CellulantSandboxTestException extends RuntimeException
{
    public function __construct(
        string $message,
        public array $details = [],
    ) {
        parent::__construct($message);
    }
}
