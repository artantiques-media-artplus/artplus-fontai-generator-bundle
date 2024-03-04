<?php
namespace Fontai\Bundle\GeneratorBundle\CustomCKFinderAuth;

use CKSource\Bundle\CKFinderBundle\Authentication\Authentication as AuthenticationBase;

class CustomCKFinderAuth extends AuthenticationBase
{
  public function authenticate()
  {
    return (bool) $this->getUser();
  }

  protected function getUser()
  {
    if (!$this->container->has('security.token_storage'))
    {
      return FALSE;
    }

    if (NULL === $token = $this->container->get('security.token_storage')->getToken())
    {
      return FALSE;
    }

    $user = $token->getUser();
    
    if (!is_object($user))
    {
      return FALSE;
    }

    return $user;
  }
}