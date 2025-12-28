<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use PmsNz\ObjectTranslationBundle\Mapping\Translatable;
use PmsNz\ObjectTranslationBundle\Mapping\TranslatableProperty;

#[ORM\Entity]
#[Translatable('entity2')]
class Entity2
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    #[TranslatableProperty]
    public string $property21;

    #[ORM\Column]
    public string $property22;

    #[ORM\Column]
    #[TranslatableProperty]
    public string $property23;
}
