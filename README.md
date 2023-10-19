KikwikCookieBundle
==================

Helpers and listeners for using forms with dunglas/doctrine-json-odm


Installation
------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require kikwik/json-form-bundle
```


Usage
-----

To handle correctly forms that has `json_document` fields you must autowire the `JsonDocumentFormSubscriber` service
and add to the FormBuilderInterface as event subscriber.

The form entity must have an updatedAt field that the subscriber will set to the current timme in case of one of the json_document fields has changed.
This will force doctrine to persist the main entity.

```php
namespace App\Form;

use Kikwik\JsonFormBundle\EventListener\JsonDocumentFormSubscriber;

class ProdottoFormType extends AbstractType
{
    public function __construct(private JsonDocumentFormSubscriber $jsonDocumentFormSubscriber)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('field1')
            ->add('field2')
            ->add('dimensioniLampada',DimensioniType::class)
            ->add('dimensioniScatola',DimensioniType::class)
            ->addEventSubscriber($this->jsonDocumentFormSubscriber)
        ;
    }
     
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prodotto::class,
        ]);
    }
}

```