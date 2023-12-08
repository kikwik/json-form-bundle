<?php

namespace Kikwik\JsonFormBundle\EventListener;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Serializer\SerializerInterface;

class JsonDocumentFormSubscriber extends JsonDocumentAbstractListener implements EventSubscriberInterface
{
    public function __construct(Registry $registry, private readonly SerializerInterface $serializer)
    {
        parent::__construct($registry);
    }

    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'serializePreSubmitValues',
            FormEvents::SUBMIT   => 'checkForChangesOnSubmit',
        ];
    }

    private $_serializedFields = [];

    public function serializePreSubmitValues(FormEvent $event): void
    {
        $object = $event->getData();
        if($object)
        {
            $fields = $this->filterJsonObjects($object);
            if(!empty($fields))
            {
                $accessor = PropertyAccess::createPropertyAccessor();
                foreach($fields as $fieldName)
                {
                    $actualValue = $accessor->getValue($object, $fieldName);
                    $this->_serializedFields[$fieldName] = $this->serializer->serialize($actualValue, 'json');
                }
            }
        }
    }

    public function checkForChangesOnSubmit(FormEvent $event): void
    {
        $object = $event->getData();
        $accessor = PropertyAccess::createPropertyAccessor();
        foreach($this->_serializedFields as $fieldName => $oldSerializedValue)
        {
            $newValue = $accessor->getValue($object, $fieldName);
            if($oldSerializedValue != $this->serializer->serialize($newValue, 'json'))
            {
                $object->setUpdatedAt(new \DateTime());
            }
        }
    }
}