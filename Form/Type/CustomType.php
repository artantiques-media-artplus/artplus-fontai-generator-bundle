<?php
namespace Fontai\Bundle\GeneratorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;


class CustomType extends AbstractType
{
  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver
    ->setDefaults([
      'required'       => FALSE,
      'error_bubbling' => TRUE,
      'compound'       => FALSE,
      'multiple'       => FALSE,
      'variables'      => []
    ])
    ->setRequired([
      'template'
    ])
    ->setAllowedTypes('variables', 'array');
  }

  public function buildView(FormView $view, FormInterface $form, array $options)
  {
    $view->vars = array_replace($options['variables'], $view->vars);
    $view->vars = array_replace($view->vars, [
      'template'  => $options['template'],
      'root_data' => $form->getRoot()->getData()
    ]);
  }

  public function getBlockPrefix()
  {
    return 'custom';
  }
}