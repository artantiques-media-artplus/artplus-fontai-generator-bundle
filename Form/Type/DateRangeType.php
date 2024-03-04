<?php
namespace Fontai\Bundle\GeneratorBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;


class DateRangeType extends AbstractType
{
  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    $builder->add('from', DateType::class, [
      'label' => FALSE,
      'required' => $options['required'],
      'view_timezone' => $options['view_timezone'],
      'attr' => [
        'placeholder' => 'Od',
        'autocomplete' => 'off'
      ]
    ]);

    $builder->add('to', DateType::class, [
      'label' => FALSE,
      'required' => $options['required'],
      'view_timezone' => $options['view_timezone'],
      'attr' => [
        'placeholder' => 'Do',
        'autocomplete' => 'off'
      ]
    ]);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options)
    {
      $data = $event->getData();

      foreach ($data as &$value)
      {
        if ($value && !preg_match('~^[0-9]{4}\-[0-9]{2}\-[0-9]{2}$~', $value))
        {
          $value = (new \DateTime($value))->format('Y-m-d');
        }
      }

      $event->setData($data);
    });

    $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) use ($options)
    {
      $data = $event->getData();

      foreach ($data as $key => &$value)
      {
        if (!$value)
        {
          continue;
        }
        
        if ($key == 'to')
        {
          $value->setTime(23, 59, 59);
        }
        else
        {
          $value->setTime(0, 0, 0);
        }
      }
    });
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    $resolver->setDefaults([
      'view_timezone' => NULL
    ]);
  }

  public function getBlockPrefix()
  {
    return 'daterange';
  }
}