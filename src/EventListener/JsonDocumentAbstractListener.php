<?php

namespace Kikwik\JsonFormBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Persistence\Mapping\ClassMetadata;

abstract class JsonDocumentAbstractListener
{
    private $entityManager;

    public function __construct(Registry $registry)
    {
        $this->entityManager = $registry->getManager();
    }

    protected function getClassMetadata($entity): ClassMetadata
    {
        return $this->entityManager->getClassMetadata(get_class($entity));
    }

    protected function recomputeSingleEntityChangeSet($entity): void
    {
        $uow  = $this->entityManager->getUnitOfWork();
        $uow->recomputeSingleEntityChangeSet($this->getClassMetadata($entity), $entity);
    }

    /**
     * @param $entity
     *
     * @return array
     */
    protected function filterJsonObjects($entity): array
    {
        $jsonObjects = [];

        $fieldMappings = $this->getClassMetadata($entity)->fieldMappings;
        if(is_array($fieldMappings)) {
            foreach($fieldMappings as $filed => $options) {
                if($options['type'] === 'json_document') {
                    $jsonObjects[] = $filed;
                }
            }
        }

        return $jsonObjects;
    }
}