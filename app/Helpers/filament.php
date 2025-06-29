<?php

function tooltip_callback(int $limit): Closure
{
    return function ($record, $state) use ($limit) {
        if (is_array($state)) {
            $state = implode(', ', $state);
        }

        $state = trim((string) $state);

        if (Str::length($state) >= $limit) {
            return $state;
        }

        return null;
    };
}
