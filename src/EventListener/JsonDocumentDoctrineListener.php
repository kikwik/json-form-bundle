<?php

namespace Kikwik\JsonFormBundle\EventListener;

use Doctrine\ORM\Event\PreUpdateEventArgs;
use Symfony\Component\PropertyAccess\PropertyAccess;

class JsonDocumentDoctrineListener extends JsonDocumentAbstractListener
{

    /**
     * @param PreUpdateEventArgs $event
     */
    public function preUpdate(PreUpdateEventArgs $event): void
    {
        $object = $event->getObject();

        $jsonObjects = $this->filterJsonObjects($object);

        if($this->forceUpdateJsonDocument($jsonObjects, $object)) {
            $this->recomputeSingleEntityChangeSet($object);
        }
    }

    /**
     * @param array $fields
     * @param       $object
     *
     * @return bool
     */
    private function forceUpdateJsonDocument(array $fields, $object): bool
    {
        $hasUpdates = false;

        if(!empty($fields)) {
            $accessor = PropertyAccess::createPropertyAccessor();

            foreach($fields as $fieldName)
            {
                $newValue = null;
                $actualValue = $accessor->getValue($object, $fieldName);
                if(is_array($actualValue))
                {
                    $newValue = [];
                    foreach ($actualValue as $k => $v) {
                        $newValue[$k] = clone $v;
                    }
                }

                if(is_object($actualValue))
                {
                    $newValue =  clone $actualValue;
                }

                if(null !== $newValue)
                {
                    $accessor->setValue($object, $fieldName, $newValue);
                    $hasUpdates = true;
                }
            }
        }

        return $hasUpdates;
    }
}