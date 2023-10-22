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

The entity must have an `updatedAt` field that the subscriber will set to the current timme in case of one of the json_document fields has changed.
This will force doctrine to persist the main entity.

```php
// the model
namespace App\Model;

class Dimensioni
{
    private ?string $altezza = null;
    private ?string $larghezza = null;
    
    // getter and setter...
}
```

```php
// the model form
namespace App\Form\Model;

use App\Model\Dimensioni;

class DimensioniType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('altezza')
            ->add('larghezza')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'=>Dimensioni::class
        ]);
    }
}
```

```php
// the entity
namespace App\Entity;

use App\Model\Dimensioni;

#[ORM\Entity(repositoryClass: ProdottoRepository::class)]
class Prodotto
{    
    #[ORM\Column(type: 'json_document', nullable: true)]
    private ?Dimensioni $dimensioni = null;
    
    #[ORM\Column(type: 'json_document', nullable: true)]
    private array $schedaTecnica = [];
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $updatedAt;
    
    // getter and setter...
}
```

```php
// the entity form
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


JsonDocumentCollectionType
--------------------------

Define the mapping from a model and a form in `config/packages/kikwik_json_form.yaml`:

```yaml
kikwik_json_form:
    model_map:
        App\Model\Costruzione:      App\Form\Model\CostruzioneType
        App\Model\Illuminazione:    App\Form\Model\IlluminazioneType
```

Then you can use the `JsonDocumentCollectionType` to store heterogeneous types of models in one field:

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
            ->add('schedaTecnica',JsonDocumentCollectionType::class, [
                'model_labels' => [
                    'App\Model\Costruzione' => 'Costruzione',
                    'App\Model\Illuminazione' => 'Illuminazione',
                ]
            ])
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

To force the initial data use the `data_models` option inside a `PRE_SET_DATA` listener:

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
            ->add('schedaTecnica',JsonDocumentCollectionType::class, [
                'model_labels' => [
                    'App\Model\Costruzione' => 'Costruzione',
                    'App\Model\Illuminazione' => 'Illuminazione',
                ]
            ])
            ->addEventSubscriber($this->jsonDocumentFormSubscriber)
        ;
        
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }
     
    public function onPreSetData(PreSetDataEvent $event)
    {
        /** @var Prodotto $prodotto */
        $prodotto = $event->getData();
        $form = $event->getForm();

        if(!$prodotto || $prodotto->getId() == null)
        {
            $form->add('schedaTecnica',JsonDocumentCollectionType::class, [
                'model_labels' => [
                    'App\Model\Costruzione' => 'Costruzione',
                    'App\Model\Illuminazione' => 'Illuminazione',
                ],
                'data_models' => [
                    'App\Model\Costruzione',
                    'App\Model\Illuminazione'
                ]
            ]);
        }
    } 
     
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Prodotto::class,
        ]);
    }
}
```