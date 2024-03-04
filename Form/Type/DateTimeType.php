<?php
namespace Fontai\Bundle\GeneratorBundle\Form\Type;

use Symfony\Component\Form\Extension\Core\Type\DateTimeType as SymfonyDateTimeType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;


class DateTimeType extends SymfonyDateTimeType
{
  const HTML5_FORMAT = "yyyy-MM-dd'T'HH:mm";

  public function buildForm(FormBuilderInterface $builder, array $options)
  {
    parent::buildForm($builder, $options);

    $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event) use ($options)
    {
      $data = $event->getData();

      if ($data && !preg_match('~^[0-9]{4}\-[0-9]{2}\-[0-9]{2}T[0-9]{2}:[0-9]{2}:[0-9]{2}$~', $data))
      {
        $data = (new \DateTime($data))->format('Y-m-d\TH:i:s');
      }

      $event->setData($data);
    });
  }

  public function buildView(FormView $view, FormInterface $form, array $options)
  {
    parent::buildView($view, $form, $options);
    
    $view->vars['type'] = 'datetime-local';
    $view->vars['attr']['data-value'] = $form->getViewData();
  }

  public function configureOptions(OptionsResolver $resolver)
  {
    parent::configureOptions($resolver);

    $resolver->setDefaults([
      'widget' => 'single_text'
    ]);
  }
}