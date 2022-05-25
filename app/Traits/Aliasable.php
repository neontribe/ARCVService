<?php

namespace App\Traits;

trait Aliasable
{
    // attaches the alias attribute
    public static function getAlias(Int $programme = 0): string
    {
        // return the chosen key, or the basename of the class the trait is attached to
        return self::PROGRAMME_ALIASES[$programme] ?? class_basename(self::class);
    }
}