<?php
namespace Fontai\Bundle\GeneratorBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Fontai\Bundle\GeneratorBundle\Form\ChoiceList\FontaiAutocompleteChoiceLoader;


class ModelAutocompleteType extends ModelType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    parent::buildForm($builder, $options);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options)
    {
      $models = (clone $options['query'])->filterById($event->getData())->find();
      $options['choice_loader']->setSelectedChoices(iterator_to_array($models));
    });
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    parent::configureOptions($resolver);
    
    $choiceLoader = function(Options $options)
    {
      // Unless the choices are given explicitly, load them on demand
      if (null === $options['choices'])
      {
        return new FontaiAutocompleteChoiceLoader(
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

    $resolver
    ->setDefaults([
      'choice_loader' => $choiceLoader,
      'autocomplete_length' => 3
    ])
    ->setRequired(['autocomplete_route']);
  }

  public function finishView(FormView $view, FormInterface $form, array $options)
  {
    parent::finishView($view, $form, $options);

    $view->vars['autocomplete_length'] = $options['autocomplete_length'];
    $view->vars['autocomplete_route'] = $options['autocomplete_route'];
  }

  public function getBlockPrefix()
  {
    return 'model_autocomplete';
  }
}