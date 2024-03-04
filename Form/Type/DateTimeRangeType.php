<?php
namespace Fontai\Bundle\GeneratorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;


class DateTimeRangeType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add('from', DateTimeType::class, [
      'label' => FALSE,
      'required' => $options['required'],
      'view_timezone' => $options['view_timezone'],
      'attr' => [
        'placeholder' => 'Od',
        'autocomplete' => 'off'
      ]
    ]);

    $builder->add('to', DateTimeType::class, [
      'label' => FALSE,
      'required' => $options['required'],
      'view_timezone' => $options['view_timezone'],
      'attr' => [
        'placeholder' => 'Do',
        'autocomplete' => 'off'
      ]
    ]);
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
      'view_timezone' => NULL
    ]);
  }

  public function getBlockPrefix()
  {
    return 'datetimerange';
  }
}