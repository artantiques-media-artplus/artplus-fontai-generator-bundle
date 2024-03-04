<?php
namespace Fontai\Bundle\GeneratorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;


class NumberRangeType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add('from', NumberType::class, [
      'label' => FALSE,
      'required' => $options['required'],
      'attr' => [
        'placeholder' => 'Od'
      ]
    ]);

    $builder->add('to', NumberType::class, [
      'label' => FALSE,
      'required' => $options['required'],
      'attr' => [
        'placeholder' => 'Do'
      ]
    ]);
  }

  public function getBlockPrefix()
  {
    return 'numberrange';
  }
}