<?php

declare(strict_types=1);

namespace PmsNz\ObjectTranslationBundle\Tests\Fixture;

use Doctrine\ORM\EntityManagerInterface;
use PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity\Entity1;
use PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity\Entity2;
use PmsNz\ObjectTranslationBundle\Tests\Fixture\Entity\Translation;

class Database
{
    public static function create(EntityManagerInterface $entityManager)
    {
        $entity11 = new Entity1();
        $entity11->property11 = 'value11';
        $entityManager->persist($entity11);

        $entity21 = new Entity2();
        $entity21->property21 = 'value21-1';
        $entity21->property22 = 'value22-1';
        $entity21->property23 = 'value23-1';
        $entityManager->persist($entity21);

        $entity22 = new Entity2();
        $entity22->property21 = 'value21-2';
        $entity22->property22 = 'value22-2';
        $entity22->property23 = 'value23-2';
        $entityManager->persist($entity22);

        $entityManager->flush();

        $translation1 = new Translation();
        $translation1->objectType = 'entity2';
        $translation1->objectId = (string) $entity21->id;
        $translation1->locale = 'fr';
        $translation1->field = 'property21';
        $translation1->value = 'value21-1 in fr';
        $entityManager->persist($translation1);

        $translation2 = new Translation();
        $translation2->objectType = 'entity2';
        $translation2->objectId = (string) $entity21->id;
        $translation2->locale = 'es';
        $translation2->field = 'property23';
        $translation2->value = 'value23-1 in es';
        $entityManager->persist($translation2);

        $entityManager->flush();
    }
}
