<?php

namespace App\Support;

class SearchHelper
{
    /**
     * Escape LIKE wildcard characters (% and _) from user input.
     *
     * This prevents user-supplied characters from acting as SQL LIKE wildcards,
     * which could otherwise cause unintended broad matches.
     */
    public static function escapeLike(string $value): string
    {
        return str_replace(
            ['\\', '%', '_'],
            ['\\\\', '\\%', '\\_'],
            $value
        );
    }
}
