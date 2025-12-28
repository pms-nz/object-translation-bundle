<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Mapping;

#[\Attribute(\Attribute::TARGET_CLASS)]
final class Translatable
{
    public function __construct(
        public string $name,
    ) {
    }
}
