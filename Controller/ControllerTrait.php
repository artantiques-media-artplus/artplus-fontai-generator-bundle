<?php
namespace Fontai\Bundle\GeneratorBundle\Controller;

use Fontai\Bundle\GeneratorBundle\Common\Utils;
use Symfony\Component\HttpFoundation\Request;


trait ControllerTrait
{
  protected function getFilterForm(
    Request $request,
    string $culture,
    string $sessionKey = NULL,
    string $formType = NULL,
    bool $updateSession = TRUE
  )
  {
    $session = $request->getSession();
    $attributeName = sprintf('%s/filters', $sessionKey ?: static::SINGULAR_NAME);
    $queryName = sprintf('%s_filters', static::SINGULAR_NAME);

    if ($request->query->has('resetFilters'))
    {
      $session->remove($attributeName);
      $request->query->remove($queryName);
    }

    $dataPrevious = $session->get($attributeName, []);
    $defaultFilters = $this->getDefaultFilters($request, $sessionKey);

    if ($dataPrevious === [] && $defaultFilters !== [])
    {
      $dataPrevious = $defaultFilters;
    }
    
    $form = $this->get('form.factory')->createNamed(
      $queryName,
      $formType ?: static::FILTER_FORM_TYPE,
      $dataPrevious,
      [
        'method' => 'GET',
        'csrf_protection' => FALSE,
        'culture' => $culture,
        'request' => $request,
        'settings' => $this->settings,
        'user' => $this->getUser(),
        'view_timezone' => $this->get('twig')->getExtension('Twig\Extension\CoreExtension')->getTimezone()->getName(),
        'related_query' => $this->getRelatedQueries($culture)
      ]
    )
    ->handleRequest($request);

    if ($form->isSubmitted())
    {
      $data = $form->getData();
      
      if ($data && is_array($data) && $data !== [])
      {
        foreach (array_keys($dataPrevious) as $key)
        {
          if (is_array($dataPrevious[$key]) && is_array($data) && (!isset($data[$key]) || !Utils::notEmpty($data[$key])))
          {
            unset($dataPrevious[$key]);
          }
        }
        
        if ($updateSession)
        {
          $session->set($attributeName, array_merge($dataPrevious, $data));
        }
      }
    }

    return $form;
  }

  protected function parseErrors($title = 'Položku nelze uložit', $form = NULL)
  {
    $r = [
      'success' => FALSE,
      'title'   => $this->translator->trans($title),
      'errors'  => []
    ];

    if ($form)
    {
      foreach ($form->getErrors(TRUE) as $i => $error)
      {
        $origin = $error->getOrigin();
        $name   = '[' . $origin->getName() . ']';
        $parent = $origin->getParent();
    
        if (!$parent)
        {
          $name = NULL;
        }
        else
        {
          do
          {
            $parentName = $parent->getName();
            
            if ($parent = $parent->getParent())
            {
              $parentName = sprintf('[%s]', $parentName);
            }

            $name = $parentName . $name;
          }
          while ($parent);
        }
        
        $r['errors'][$name] = [
          $this->translator->trans($origin->getConfig()->getOption('label')),
          $this->translator->trans($error->getMessage())
        ];
      }
    }

    return $this->json($r);
  }
}