<?php

namespace App\Extensions;

use Monolog\Formatter\LineFormatter;

class LogFormatter
{
    public function __invoke($monolog): void
    {
        $format = "[%datetime%] %level_name%: %message%\n%context%\n%extra%\n\n";
        $formatter = new LineFormatter($format, 'H:i', true);

        foreach ($monolog->getHandlers() as $handler) {
            $handler->setFormatter($formatter);
        }
    }
}
