<?php
namespace Fontai\Bundle\GeneratorBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;


class DisableBrowserCacheSubscriber implements EventSubscriberInterface
{
  public function onKernelResponse(ResponseEvent $event)
  {
    if (strpos($event->getRequest()->attributes->get('_controller'), 'App\Controller\FontaiGenerator\\') !== 0)
    {
      return;
    }

    $headers = $event->getResponse()->headers;

    $headers->addCacheControlDirective('no-cache');
    $headers->addCacheControlDirective('no-store');
  }

  public static function getSubscribedEvents()
  {
    return [
      KernelEvents::RESPONSE => [
        ['onKernelResponse', 0]
      ]
    ];
  }
}