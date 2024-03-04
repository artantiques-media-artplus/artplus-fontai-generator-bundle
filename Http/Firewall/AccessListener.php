<?php
namespace Fontai\Bundle\GeneratorBundle\Http\Firewall;

use Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface;
use Symfony\Component\Security\Http\AccessMapInterface;
use Symfony\Component\Security\Core\Authentication\AuthenticationManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Security\Core\Exception\AuthenticationCredentialsNotFoundException;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;


class AccessListener
{
  private $tokenStorage;
  private $accessDecisionManager;
  private $map;
  private $authManager;

  public function __construct(
    TokenStorageInterface $tokenStorage,
    AccessDecisionManagerInterface $accessDecisionManager,
    AccessMapInterface $map,
    AuthenticationManagerInterface $authManager
  )
  {
    $this->tokenStorage = $tokenStorage;
    $this->accessDecisionManager = $accessDecisionManager;
    $this->map = $map;
    $this->authManager = $authManager;
  }

  public function __invoke(RequestEvent $event)
  {
    if (NULL === $token = $this->tokenStorage->getToken())
    {
      throw new AuthenticationCredentialsNotFoundException('A Token was not found in the TokenStorage.');
    }

    $request = $event->getRequest();

    list($attributes) = $this->map->getPatterns($request);

    if (NULL === $attributes)
    {
      return;
    }

    $token = $this->authManager->authenticate($token);
    $this->tokenStorage->setToken($token);

    if (!$this->accessDecisionManager->decide($token, $attributes, $request))
    {
      $exception = new AccessDeniedException();
      $exception->setAttributes($attributes);
      $exception->setSubject($request);

      throw $exception;
    }
  }
}