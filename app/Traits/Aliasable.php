<?php

namespace App\Traits;

trait Aliasable
{
    // attaches the alias attribute
    public function getAliasAttribute(Int $programme = 0): string
    {
        // return the chosen key, or the basename of the class the trait is attached to
        return $this->programmeAliases[$programme] ?? class_basename(self::class);
    }
}