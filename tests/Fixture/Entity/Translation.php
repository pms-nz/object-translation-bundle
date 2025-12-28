<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity;

use Doctrine\ORM\Mapping as ORM;
use PmsNz\ObjectTranslationBundle\Model\AbstractTranslation;

#[ORM\Entity]
class Translation extends AbstractTranslation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }
}
