<?php
namespace Fontai\Bundle\GeneratorBundle\Form\Type;

use Propel\Bundle\PropelBundle\Form\DataTransformer\CollectionToArrayTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;
use Symfony\Component\Form\ChoiceList\Factory\DefaultChoiceListFactory;
use Symfony\Component\Form\ChoiceList\Factory\PropertyAccessDecorator;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\MissingOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;
use Fontai\Bundle\GeneratorBundle\Form\ChoiceList\FontaiChoiceLoader;
use Fontai\Bundle\GeneratorBundle\Form\DataTransformer\ValuesToObjectsTransformer;


class ModelType extends AbstractType
{
  /**
   * @var ChoiceListFactoryInterface
   */
  protected $choiceListFactory;

  /**
   * ModelType constructor.
   *
   * @param PropertyAccessorInterface|null  $propertyAccessor
   * @param ChoiceListFactoryInterface|null $choiceListFactory
   */
  public function __construct(PropertyAccessorInterface $propertyAccessor = null, ChoiceListFactoryInterface $choiceListFactory = null)
  {
    $this->choiceListFactory = $choiceListFactory ?: new PropertyAccessDecorator(
      new DefaultChoiceListFactory(),
      $propertyAccessor
    );
  }

  /**
   * Creates the label for a choice.
   *
   * For backwards compatibility, objects are cast to strings by default.
   *
   * @param object $choice The object.
   *
   * @return string The string representation of the object.
   *
   * @internal This method is public to be usable as callback. It should not
   *           be used in user code.
   */
  public static function createChoiceLabel($choice)
  {
    return (string) $choice;
  }

  /**
   * Creates the field name for a choice.
   *
   * This method is used to generate field names if the underlying object has
   * a single-column integer ID. In that case, the value of the field is
   * the ID of the object. That ID is also used as field name.
   *
   * @param object     $choice The object.
   * @param int|string $key    The choice key.
   * @param string     $value  The choice value. Corresponds to the object's
   *                           ID here.
   *
   * @return string The field name.
   *
   * @internal This method is public to be usable as callback. It should not
   *           be used in user code.
   */
  public static function createChoiceName($choice, $key, $value)
  {
    return str_replace('-', '_', (string) $value);
  }

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    if ($options['multiple'])
    {
      $builder->addViewTransformer(new CollectionToArrayTransformer(), TRUE);
    }

    if ($options['values_as_data'])
    {
      $builder->addModelTransformer(new ValuesToObjectsTransformer($options['multiple']), TRUE);
    }

    $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) use ($options)
    {
      $data = $event->getData();

      if ($options['values_as_data'])
      {
        $data = (clone $options['query'])->filterById($data)->{$options['multiple'] ? 'find' : 'findOne'}();
        $event->setData($data);
      }
      
      if ($data)
      {
        $options['choice_loader']->setSelectedChoices($data instanceof \Traversable ? iterator_to_array($data) : [$data]);
      }
    });
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $choiceLoader = function(Options $options)
    {
      // Unless the choices are given explicitly, load them on demand
      if (null === $options['choices'])
      {
        return new FontaiChoiceLoader(
          $this->choiceListFactory,
          $options['class'],
          $options['query'],
          $options['index_property'],
          $options['query_method'],
          $options['exclude_choices']
        );
      }

      return null;
    };

    $choiceName = function(Options $options)
    {
      $identifier = $options['index_property']
      ? array($options['query']->getTableMap()->getColumn($options['index_property']))
      : $options['query']->getTableMap()->getPrimaryKeys();
      
      $firstIdentifier = current($identifier);
      
      if (count($identifier) === 1 && $firstIdentifier->getPdoType() === \PDO::PARAM_INT)
      {
        return array(__CLASS__, 'createChoiceName');
      }

      return null;
    };

    $choiceValue = function(Options $options)
    {
      $identifier = $options['index_property']
      ? array($options['query']->getTableMap()->getColumn($options['index_property']))
      : $options['query']->getTableMap()->getPrimaryKeys();
      
      $firstIdentifier = current($identifier);
      
      if (count($identifier) === 1 && in_array($firstIdentifier->getPdoType(), [\PDO::PARAM_BOOL, \PDO::PARAM_INT, \PDO::PARAM_STR]))
      {
        return function($object) use ($firstIdentifier)
        {
          if ($object)
          {
            return call_user_func([$object, sprintf('get%s', ucfirst($firstIdentifier->getPhpName()))]);
          }

          return null;
        };
      }

      return null;
    };

    $queryNormalizer = function(Options $options, $query)
    {
      if ($query === null)
      {
        $queryClass = $options['class'] . 'Query';

        if (!class_exists($queryClass))
        {
          if (empty($options['class']))
          {
            throw new MissingOptionsException('The "class" parameter is empty, you should provide the model class');
          }

          throw new InvalidOptionsException(
            sprintf(
              'The query class "%s" is not found, you should provide the FQCN of the model class',
              $queryClass
            )
          );
        }
        $query = new $queryClass();
      }

      return $query;
    };

    $choiceLabelNormalizer = function(Options $options, $choiceLabel)
    {
      if ($choiceLabel === null)
      {
        if ($options['property'] == null)
        {
          $choiceLabel = array(__CLASS__, 'createChoiceLabel');
        }
        else
        {
          $getter = sprintf('get%s', ucfirst($options['query']->getTableMap()->getColumn($options['property'])->getPhpName()));

          $choiceLabel = function($choice) use ($getter)
          {
            return call_user_func([$choice, $getter]);
          };
        }
      }

      return $choiceLabel;
    };

    $resolver
    ->setDefaults([
      'query'                     => null,
      'query_method'              => 'find',
      'exclude_choices'           => [],
      'index_property'            => null,
      'property'                  => null,
      'choices'                   => null,
      'choice_loader'             => $choiceLoader,
      'choice_label'              => null,
      'choice_name'               => $choiceName,
      'choice_value'              => $choiceValue,
      'choice_translation_domain' => false,
      'by_reference'              => false,
      'edit_route'                => null,
      'values_as_data'            => false
    ])
    ->setRequired(['class'])
    ->setNormalizer('query', $queryNormalizer)
    ->setNormalizer('choice_label', $choiceLabelNormalizer)
    ->setAllowedTypes('query', ['null', 'Propel\Runtime\ActiveQuery\ModelCriteria']);
  }

  public function finishView(FormView $view, FormInterface $form, array $options)
  {
    if ($options['edit_route'])
    {
      $view->vars['edit_route'] = $options['edit_route'];
    }
  }

  public function getBlockPrefix()
  {
    return 'model';
  }

  public function getParent()
  {
    return 'Symfony\Component\Form\Extension\Core\Type\ChoiceType';
  }
}