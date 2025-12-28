<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class Entity1
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public ?int $id = null;

    #[ORM\Column]
    public string $property11;
}
