<?php
namespace Fontai\Bundle\GeneratorBundle\Twig;

use App\Model;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;


class FontaiGeneratorExtension extends AbstractExtension
{
  protected $tokenStorage;

  public function __construct(TokenStorageInterface $tokenStorage)
  {
    $this->tokenStorage = $tokenStorage;
  }
  public function getFilters()
  {
    return [
      new TwigFilter(
        'format_bytes',
        [$this, 'formatBytes']
      )
    ];
  }

  public function getFunctions()
  {
    return [
      new TwigFunction(
        'fontai_generator_widget',
        [$this, 'getWidget'],
        [
          'needs_environment' => TRUE,
          'is_safe' => ['html']
        ]
      ),
      new TwigFunction(
        'file_exists',
        'file_exists'
      ),
      new TwigFunction(
        'method_exists',
        'method_exists'
      ),
      new TwigFunction(
        'at_least_one_offset_exists',
        [$this, 'atLeastOneOffsetExists']
      )
    ];
  }

  public function formatBytes($value)
  {
    $units = ['B', 'kB', 'MB', 'GB', 'TB'];
    $value = max($value, 0);
    $pow = floor(($value ? log($value) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $value /= pow(1024, $pow);

    return sprintf(
      '%s %s',
      round($value, 2),
      $units[$pow]
    );
  }

  public function atLeastOneOffsetExists(
    \ArrayAccess $arrayAccess,
    array $keys
  )
  {
    foreach ($keys as $key)
    {
      if ($arrayAccess->offsetExists($key))
      {
        return TRUE;
      }
    }

    return FALSE;
  }

  public function getWidget(\Twig_Environment $environment)
  {
    $token = $this->tokenStorage->getToken();
    $user = $token ? $token->getUser() : NULL;

    return $environment->render('@FontaiGenerator/extension/widget.html.twig', [
      'isEnabled' => $user instanceof Model\Admin
    ]);
  }
  
  public function getName()
  {
    return 'fontai_generator_extension';
  }
}