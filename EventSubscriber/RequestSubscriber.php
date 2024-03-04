<?php

namespace Fontai\Bundle\GeneratorBundle\EventSubscriber;

use App\Model\LanguageQuery;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;


class RequestSubscriber implements EventSubscriberInterface {
    protected $tokenStorage;
    protected $languagesEnabled;

    public function __construct(
        TokenStorageInterface $tokenStorage,
        $config = []
    ) {
        $this->tokenStorage = $tokenStorage;
        $this->languagesEnabled = isset($config['languages']) && $config['languages'];
    }

    public function onKernelRequest(RequestEvent $event) {
        $request = $event->getRequest();
        $session = $request->getSession();
        $isMainRequest = $event->isMainRequest();

        $request->attributes->set('is_master_request', $isMainRequest);

        if (!$isMainRequest) {
            return;
        }
        
        $controller = $request->attributes->get('_controller');

        if (strpos($controller, 'App\Controller\Backend\\') !== 0 && strpos($controller, 'App\Controller\FontaiGenerator\\') !== 0)
        {
            return;
        }

        if ($perPage = $request->query->get('per_page')) {
            if (is_numeric($perPage)) {
                $this->tokenStorage->getToken()->getUser()
                    ->setPerPage($perPage)
                    ->save();

                $showAllCache = [];
            } else {
                $showAllCache = $session->get('show_all', []);
                $showAllCache[substr(str_replace('App\Controller\\', '', $controller), 0, -10)] = TRUE;
            }

            $session->set('show_all', $showAllCache);
        }

        if ($this->languagesEnabled) {
            if ($culture = $request->query->get('culture')) {
                $language = LanguageQuery::create()
                    ->findOneByCode($culture);

                if ($language) {
                    $session->set('culture', $language->getCode());
                }
            }

            if (!$session->get('culture')) {
                $language = LanguageQuery::create()
                    ->findOneByIsDefault(TRUE);

                $session->set('culture', $language->getCode());
            }
        } else $session->remove('culture');
    }

    public static function getSubscribedEvents() {
        return [
            KernelEvents::REQUEST => [
                ['onKernelRequest', 0]
            ]
        ];
    }
}