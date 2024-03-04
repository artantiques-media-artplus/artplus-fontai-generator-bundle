<?php
namespace Fontai\Bundle\GeneratorBundle\Security\Authorization\Voter;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;


class Voter implements VoterInterface
{
  protected $requestStack;

  public function __construct(RequestStack $requestStack)
  {
    $this->requestStack = $requestStack;
  }

  public function vote(TokenInterface $token, $subject, array $attributes)
  {
    $request = $this->requestStack->getCurrentRequest();
    $user    = $token->getUser();

    if (!method_exists($user, 'hasCredential') || !$request->attributes->has('_controller'))
    {
      return self::ACCESS_ABSTAIN;
    }

      $controllerString = $request->attributes->get('_controller');
    if (strrpos($controllerString, '::') === false) {
        list($controller, $action) = explode(':', $controllerString);
    } else {
        list($controller, $action) = explode('::', $controllerString);
    }
    $controller = substr($controller, 0, -10);

    $action = strtr($action, [
      'batch'     => $request->attributes->get('action'),
      'edit'      => 'index',
      'quickedit' => 'edit'
    ]);

    if (in_array(
      $controller,
      [
        'Fontai\Bundle\FccBundle\Controller\Fcc',
        'Symfony\Bundle\FrameworkBundle\Controller\Redirect'
      ]
    ))
    {
      return self::ACCESS_GRANTED;
    }

    $actions = ['index', 'create', 'edit', 'delete', 'export'];

    if (strpos($controller, 'App\Controller\FontaiGenerator\\') === FALSE || !in_array($action, $actions))
    {
      return self::ACCESS_ABSTAIN;
    }

    if ($user->hasCredential(str_replace('App\Controller\\', '', $controller) . '-' . $action))
    {
      return self::ACCESS_GRANTED;
    }

    if (method_exists($controller . 'Controller', 'getIncludedControllers'))
    {
      $credentials = [];

      foreach (call_user_func([$controller . 'Controller', 'getIncludedControllers']) as $includedController)
      {
        $includedController = 'FontaiGenerator\\' . $includedController;

        $credentials[] = $includedController . '-' . $action;

        if (in_array($action, ['delete', 'create']))
        {
          $credentials[] = $includedController . '-edit';
        }
      }
      
      if ($user->hasCredential($credentials))
      {
        return self::ACCESS_GRANTED;
      }
    }

    return self::ACCESS_DENIED;
  }
}