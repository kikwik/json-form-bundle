KikwikJsonFormBundle
====================

Helpers and listeners for using forms with [dunglas/doctrine-json-odm](https://github.com/dunglas/doctrine-json-odm)


Installation
------------

Open a command console, enter your project directory and execute the
following command to download the latest stable version of this bundle:

```console
$ composer require kikwik/json-form-bundle
```



Usage
-----

Suppose to have some models defined:

```php
// first model
namespace App\Model;

class Dimension
{
    private ?int $height = null;
    private ?int $width = null;
    
    // getter and setter...
}
```

```php
// second model
namespace App\Model;

class TechData
{
    private ?string $someData = null;
    private ?string $otherData = null;
    
    // getter and setter...
}
```

Then you have to define a form for each model:


```php
// first model form
namespace App\Form\Model;

use App\Model\Dimension;

class DimensionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('height')
            ->add('width')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'=>Dimension::class
        ]);
    }
}
```

```php
// second model form
namespace App\Form\Model;

use App\Model\TechData;

class TechDataType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('someData')
            ->add('otherData')
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class'=>TechData::class
        ]);
    }
}
```

Now you can to have an entity with some `json_document` fields defined as single model or an array of arbitrary models (the entity must have an `updatedAt` timestamp field)


```php
// the entity
namespace App\Entity;

use App\Model\Dimension;

#[ORM\Entity(repositoryClass: ProductRepository::class)]
class Product
{    
    #[ORM\Column(type: Types::STRING)]
    private ?string $name = null;

    #[ORM\Column(type: 'json_document', nullable: true)]
    private ?Dimension $dimension = null;
    
    #[ORM\Column(type: 'json_document', nullable: true)]
    private array $techData = [];
    
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $updatedAt;
    
    // getter and setter...
}
```

To handle correctly forms that has `json_document` fields you must autowire the `JsonDocumentFormSubscriber` service
and add to the FormBuilderInterface as event subscriber.

The subscriber will set the `updatedAt` field to the current timestamp in case of one of the json_document fields has changed.
This will force doctrine to persist the main entity.

```php
// the entity form
namespace App\Form;

use Kikwik\JsonFormBundle\EventListener\JsonDocumentFormSubscriber;

class ProductFormType extends AbstractType
{
    public function __construct(private JsonDocumentFormSubscriber $jsonDocumentFormSubscriber) # autowire here
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('dimension',DimensionType::class)
            ->addEventSubscriber($this->jsonDocumentFormSubscriber) # add as event subscriber
        ;
    }
     
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
```


JsonDocumentCollectionType
--------------------------

To handle collections of models you must define the mapping from a model to the relative form in `config/packages/kikwik_json_form.yaml`:

```yaml
kikwik_json_form:
    model_map:
        App\Model\Dimension:    App\Form\Model\DimensionType
        App\Model\TechData:     App\Form\Model\TechDataType
```

Then you can use the `JsonDocumentCollectionType` to store heterogeneous types of models in one field:

```php
namespace App\Form;

use Kikwik\JsonFormBundle\EventListener\JsonDocumentFormSubscriber;

class ProductFormType extends AbstractType
{
    public function __construct(private JsonDocumentFormSubscriber $jsonDocumentFormSubscriber)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('techData',JsonDocumentCollectionType::class, [
                'model_labels' => [
                    'App\Model\Dimension' => 'Size of product',
                    'App\Model\TechData' => 'Technical data',
                ],
                'model_options' => [
                    'App\Model\Dimension' => ['unitOfMeasurement'=>'mm'],
                ]
            ])
            ->addEventSubscriber($this->jsonDocumentFormSubscriber)
        ;
    }
     
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
```

To force the initial data use the `data_models` option inside a `PRE_SET_DATA` listener:

```php
namespace App\Form;

use Kikwik\JsonFormBundle\EventListener\JsonDocumentFormSubscriber;

class ProductFormType extends AbstractType
{
    public function __construct(private JsonDocumentFormSubscriber $jsonDocumentFormSubscriber)
    {
    }
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('techData',JsonDocumentCollectionType::class, [
                'model_labels' => [
                    'App\Model\Dimension' => 'Size of product',
                    'App\Model\TechData' => 'Technical data',
                ],
                'model_options' => [
                    'App\Model\Dimension' => ['unitOfMeasurement'=>'mm'],
                ]
            ])
            ->addEventSubscriber($this->jsonDocumentFormSubscriber)
        ;
        
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }
     
    public function onPreSetData(PreSetDataEvent $event)
    {
        /** @var Product $product */
        $product = $event->getData();
        $form = $event->getForm();

        if(!$product || $product->getId() == null)
        {
            $form->add('techData',JsonDocumentCollectionType::class, [
                'model_labels' => [
                    'App\Model\Dimension' => 'Size of product',
                    'App\Model\TechData' => 'Technical data',
                ],
                'model_options' => [
                    'App\Model\Dimension' => ['unitOfMeasurement'=>'mm'],
                ],
                'data_models' => [
                    'App\Model\Dimension',
                    'App\Model\TechData'
                ]
            ]);
        }
    } 
     
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
```