<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Model;

abstract class AbstractTranslation
{
    public string $objectType;
    public string $objectId;
    public string $locale;
    public string $field;
    public string $value;
}
