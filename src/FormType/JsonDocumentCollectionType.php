<?php

namespace Kikwik\JsonFormBundle\FormType;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class JsonDocumentCollectionType extends AbstractType
{
    public function __construct(private array $modelMap)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options){
            $values = $event->getData();
            $form = $event->getForm();

            if(is_array($options['data_models']))
            {
                foreach($options['data_models'] as $index => $modelClass)
                {
                    $this->addModelForm($form, $index, $modelClass, $options);
                }
            }
            else
            {
                if(!is_array($values))
                {
                    $values = [$values];
                }
                foreach($values as $index => $value)
                {
                    if(is_object($value))
                    {
                        $modelClass = get_class($value);
                        $this->addModelForm($form, $index, $modelClass, $options);
                    }
                }
            }
        });
    }

    private function addModelForm(FormInterface $form, int $index, string $modelClass, array $options)
    {
        if(isset($this->modelMap[$modelClass]))
        {
            $label = $options['model_labels'][$modelClass] ?? $modelClass;
            $form->add($index, $this->modelMap[$modelClass], ['label'=>$label]);
        }
        else
        {
            throw new \RuntimeException(sprintf('kikwik_json_form.model_map not defined for class %s',$modelClass));
        }
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'model_labels' => [],
            'data_models' => null,
        ]);
    }


}