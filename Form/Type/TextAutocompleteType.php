<?php
namespace Fontai\Bundle\GeneratorBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\TextType as SymfonyTextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;


class TextAutocompleteType extends SymfonyTextType
{
  public function configureOptions(OptionsResolver $resolver)
  {
    parent::configureOptions($resolver);
  
    $resolver
    ->setDefaults([
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
    return 'text_autocomplete';
  }
}