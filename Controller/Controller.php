<?php
namespace Fontai\Bundle\GeneratorBundle\Controller;

use Fontai\Bundle\LogBundle\Service\Log;
use Fontai\Propel\Navigation;
use Fontai\Bundle\SettingsBundle\Service\Settings;
use Fontai\Spout\Writer\XLSX\Writer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Propel\Runtime\Util\PropelModelPager;


abstract class Controller extends AbstractController
{
  use ControllerTrait;

  /** @var string Singular Name of Model */
  const SINGULAR_NAME = NULL;

  /** @var string Controller namespace */
  const CONTROLLER_NAMESPACE = NULL;

  /** @var string Model namespace */
  const MODEL_NAMESPACE = NULL;

  /** @var string Model Entity class name */
  const ENTITY_CLASS = NULL;

  /** @var string Model Entity class name with namespace */
  const ENTITY = NULL;

  /** @var string Model Query class name with namespace */
  const QUERY = NULL;

  /** @var string Quickedit form type class name with namespace */
  const QUICKEDIT_FORM_TYPE = NULL;

  /** @var string Edit form type class name with namespace */
  const EDIT_FORM_TYPE = NULL;

  /** @var integer Default per page */
  const DEFAULT_PER_PAGE = 20;

  /** @var boolean Show all enabled */
  const SHOW_ALL = FALSE;

  /** @var string Route name prefix */
  const ROUTE_NAME_PREFIX = NULL;

  /** @var boolean Fulltext search with Elastic enabled */
  const FULLTEXT_SEARCH = FALSE;

  /** @var boolean Log with LogBundle enabled */
  const LOG = FALSE;

  /** @var string Default model field to sort by */
  const DEFAULT_SORT_FIELD = NULL;

  /** @var string Default sort type */
  const DEFAULT_SORT_TYPE = 'asc';

  /** @var boolean Edit form navigation enabled */
  const ENABLE_NAVIGATION = TRUE;

  /** @var string Sheet title for export XLS */
  const EXPORT_SHEET_TITLE = NULL;

  protected $settings;
  protected $translator;

  /** @var string List page title */
  protected static $listTitle;

  /** @var array edit page title */
  protected static $editTitle = ['', ''];

  /** @var array Model fields */
  protected static $fields = [];

  /** @var array controllers, where this controller is included */
  protected static $includedIn = [];

  /** @var array Model I18N fields */
  protected static $fieldsI18n = [];

  /** @var string Model I18N PHP name */
  const ENTITY_CLASS_I18N = NULL;

  /** @var array Autocomplete configuration */
  protected static $autocompleteParams = [
    'filter' => [],
    'edit' => []
  ];


  public function __construct(
    Settings $settings,
    TranslatorInterface $translator
  )
  {
    $this->settings = $settings;
    $this->translator = $translator;
  }

  protected function processSort(
    Request $request,
    string $sessionKey = NULL
  )
  {
    if ($sessionKey === NULL)
    {
      $sessionKey = static::SINGULAR_NAME;
    }

    $session = $request->getSession();

    $attributeNameField = sprintf('%s/sort/field', $sessionKey);
    $attributeNameType = sprintf('%s/sort/type', $sessionKey);

    if ($request->query->has('resetFilters'))
    {
      $session->remove($attributeNameField);
      $session->remove($attributeNameType);
    }
    elseif ($request->query->has('sort'))
    {
      $session->set($attributeNameField, $request->query->get('sort'));
      $session->set($attributeNameType,  $request->query->get('type'));
    }

    if (static::DEFAULT_SORT_FIELD && !$session->get($attributeNameField))
    {
      $session->set($attributeNameField, static::DEFAULT_SORT_FIELD);
      $session->set($attributeNameType,  static::DEFAULT_SORT_TYPE);
    }
  }


  protected function addSortCriteria(
    ModelCriteria $query,
    SessionInterface $session,
    string $sessionKey = NULL
  )
  {
    if ($sessionKey === NULL)
    {
      $sessionKey = static::SINGULAR_NAME;
    }

    if ($sortColumn = Container::camelize($session->get(sprintf('%s/sort/field', $sessionKey), '')))
    {
      $table = in_array($sortColumn, static::$fields)
      ? static::ENTITY_CLASS
      : (
        static::ENTITY_CLASS_I18N && in_array($sortColumn, static::$fieldsI18n)
        ? static::ENTITY_CLASS_I18N
        : NULL
      );

      if ($table)
      {
        $query->orderBy(sprintf('%s.%s', $table, $sortColumn), $session->get(sprintf('%s/sort/type', $sessionKey), 'asc'));
      }
    }
  }

  protected function getDefaultFilters(Request $request)
  {
    return [];
  }

  protected function createTranslations($object)
  {
    $languages = call_user_func([sprintf('%s\LanguageQuery', static::MODEL_NAMESPACE), 'create'])
    ->filterByCode($object->getCulture(), Criteria::NOT_EQUAL)
    ->find();

    foreach ($languages as $language)
    {
      $object
      ->getCurrentTranslation()
      ->copy()
      ->setCulture($language->getCode())
      ->save();
    }
  }

  protected function getNavigation(
    Request $request,
    SessionInterface $session,
    $object
  )
  {
    if ($object->isNew())
    {
      return [];
    }

    $this->processSort($request);
    $filters = $session->get(sprintf('%s/filters', static::SINGULAR_NAME));
    
    $className = static::QUERY;
    $query = $className::create();
    
    if (static::ENTITY_CLASS_I18N)
    {
      $query->joinWithI18n($session->get('culture'));
    }

    $this->addSortCriteria($query, $session);

    if (!$this->isIncluded($request))
    {
      $this->addFiltersCriteria($query, $filters);
    }
    
    $this->addIncludedCriteria($query, $request);

    if (method_exists($this, 'addCustomCriteria'))
    {
      $this->addCustomCriteria($query, $request);
    }

    return Navigation::getNavigation($query, $object);
  }

  protected function elasticUpdate($object)
  {
    
  }

  protected function getObjectById(
    int $id,
    Request $request
  )
  {
    $className = static::QUERY;
    $query = $className::create();

    if (static::ENTITY_CLASS_I18N)
    {
      $query->joinWithI18n($request->getSession()->get('culture'));
    }

    if (method_exists($this, 'addCustomCriteria'))
    {
      $this->addCustomCriteria($query, $request);
    }
    
    if (!($object = $query->findOneById($id)))
    {
      throw $this->createNotFoundException(sprintf('%s object not found', static::ENTITY_CLASS));
    }

    if (method_exists($this, 'afterGetOrCreate'))
    {
      $this->afterGetOrCreate($object, $request);
    }

    return $object;
  }

  public function create(
    Request $request,
    SessionInterface $session,
    Log $log
  )
  {
    $className = static::ENTITY;
    $object = new $className();
    
    if (static::ENTITY_CLASS_I18N)
    {
      $object->setCulture($session->get('culture'));
    }

    $this->setIncludedObjectValues($object, $request);

    if (method_exists($this, 'afterGetOrCreate'))
    {
      $this->afterGetOrCreate($object, $request);
    }

    return $this->handleEdit($request, $session, $log, $object);
  }

  public function index(
    Request $request,
    SessionInterface $session
  )
  {
    $culture = $session->get('culture');

    $this->processSort($request);

    $isDynamicFilterRequest = $request->headers->get('X-No-Save');
    $filterForm = $this->getFilterForm($request, $culture, NULL, NULL, !$isDynamicFilterRequest);

    if ($filterForm->isSubmitted() && $isDynamicFilterRequest)
    {
      return $this->render(sprintf('fontai_generator/%s/filters_standalone.html.twig', static::SINGULAR_NAME), [
        'filter_form' => $filterForm->createView()
      ]);
    }

    $filters = $filterForm->getData();

    $perPage = static::SHOW_ALL && isset($session->get('show_all', [])[sprintf('FontaiGenerator\\%s', static::ENTITY_CLASS)])
    ? 100000
    : ($this->getUser()->getPerPage() ?: static::DEFAULT_PER_PAGE);

    $className = static::QUERY;
    $query = $className::create();
    
    if (static::ENTITY_CLASS_I18N)
    {
      $query->joinWithI18n($culture);
    }
    
    $this->addIndexQueryJoins($query, $culture);

    $this->addSortCriteria($query, $session);
    
    if (!$this->isIncluded($request))
    {
      $this->addFiltersCriteria($query, $filters);
    }

    $this->addIncludedCriteria($query, $request);
    
    if (method_exists($this, 'addCustomCriteria'))
    {
      $this->addCustomCriteria($query, $request);
    }

    $pager = $query->paginate(
      $request->query->get('page', $session->get(sprintf('fontai_generator/%s/page', static::SINGULAR_NAME), 1)),
      $perPage
    );

    $includedQueryStringParams = $this->getIncludedQueryStringParams($request);

    $viewData = [
      'pager'         => $pager,
      'filters'       => $filters,
      'filter_form'   => $filterForm->createView(),
      'query'         => $query,
      'query_string'  => $includedQueryStringParams,
      'title'         => static::$listTitle
    ];

    if (static::QUICKEDIT_FORM_TYPE)
    {
      $viewData['quickedit_forms'] = $this->getQuickEditFormsFromPager(
        $pager,
        $culture,
        $includedQueryStringParams
      );
    }

    return $this->render(sprintf('fontai_generator/%s/index.html.twig', static::SINGULAR_NAME), $viewData);
  }

  public function edit(
    Request $request,
    SessionInterface $session,
    Log $log,
    int $id
  )
  {
    $object = $this->getObjectById($id, $request);

    return $this->handleEdit($request, $session, $log, $object);
  }

  public function quickedit(
    Request $request,
    SessionInterface $session,
    Log $log,
    int $id
  )
  {
    $object = $this->getObjectById($id, $request);

    return $this->handleQuickedit($request, $session, $log, $object);
  }

  public function delete(
    Request $request,
    SessionInterface $session,
    Log $log,
    int $id
  )
  {
    $object = $this->getObjectById($id, $request);
    
    // !!! Add delete condition !!!

    if (method_exists($this, 'beforeDelete'))
    {
      $this->beforeDelete($object, $request);
    }

    try
    {
      $object->delete();

      if (static::LOG)
      {
        $log->createEventArchivate($object, $this->getUser());
      }
    }
    catch (\Exception $e)
    {
      $session->getFlashBag()->add('error', 'Záznam nelze smazat');

      return $this->redirect($this->getRedirectPath($request, $object));
    }

    if (method_exists($this, 'afterDeleteSuccess'))
    {
      $this->afterDeleteSuccess($object, $request);
    }

    return $this->redirect($this->getRedirectPath($request));
  }

  public function export(
    Request $request,
    SessionInterface $session,
    Writer $writer
  )
  {
    $culture = $session->get('culture');
    
    $this->processSort($request);
    
    $filterForm = $this->getFilterForm($request, $culture);
    $filters = $filterForm->getData();

    $className = static::QUERY;
    $query = $className::create();
    
    if (static::ENTITY_CLASS_I18N)
    {
      $query->joinWithI18n($culture);
    }

    $this->addSortCriteria($query, $session);
    $this->addFiltersCriteria($query, $filters);
    
    if (method_exists($this, 'addCustomCriteria'))
    {
      $this->addCustomCriteria($query, $request);
    }

    $perPass = 1000; 
    $passes  = ceil($query->count() / $perPass);
    $query->limit($perPass);

    $cachePath = sprintf('%s/xlsx/', $this->getParameter('kernel.cache_dir'));
    
    if (!is_dir($cachePath))
    {
      umask(0000);
      mkdir($cachePath, 0777);
    }
    
    $cachePath .= md5(microtime());

    $writer
    ->setShouldUseInlineStrings(FALSE)
    ->openToFile($cachePath);

    $writer
    ->getCurrentSheet()
    ->setName(static::EXPORT_SHEET_TITLE);

    $writer->addRowWithFontBold($this->getExportColumnTitles());

    $rowStyles = $this->getExportRowStyles();

    for ($pass = 0; $pass < $passes; $pass++)
    {
      $result = $query->offset($perPass * $pass)
      ->find();

      $rows = [];
      
      for ($i = 0; isset($result[$i]); $i++)
      {
        $rows[] = $this->getExportRowData($result[$i]);
        $result[$i]->clearAllReferences(TRUE);
        unset($result[$i]);
      }

      $writer->addRowsWithStyle($rows, $rowStyles);
    }

    $writer->close();

    return $this->file($cachePath, 'export.xlsx');
  }

  public function batch(
    Request $request,
    HttpKernelInterface $httpKernel,
    string $action
  )
  {
    $ids = $request->request->get('batch', [static::SINGULAR_NAME => []]);

    if (
      !isset($ids[static::SINGULAR_NAME])
      || !is_array($ids[static::SINGULAR_NAME])
    )
    {
      throw new BadRequestHttpException();
    }

    $controller = sprintf(
      '%s::%s',
      static::class,
      $action
    );

    foreach ($ids[static::SINGULAR_NAME] as $id)
    {
      $subRequest = new Request();
      $subRequest->attributes->set('_controller', $controller);
      $subRequest->attributes->set('id', $id);

      if ($request->getSession())
      {
        $subRequest->setSession($request->getSession());
      }
    
      $httpKernel->handle(
        $subRequest,
        HttpKernelInterface::SUB_REQUEST
      );
    }

    return $this->redirect($this->getRedirectPath($request));
  }

  public function autocomplete(
    Request $request,
    SessionInterface $session
  )
  {
    $type = $request->query->get('type');

    if (!in_array($type, ['filter', 'edit']))
    {
      return new Response('', 400);
    }

    $culture = $session->get('culture');
    $postData = $request->request->all();
    $relatedQueries = $this->getRelatedQueries($culture);

    foreach ($postData as $variableName => $value)
    {
      if (isset($relatedQueries[$variableName]) && isset(static::$autocompleteParams[$type][$variableName]))
      {
        $outputFieldName = 'Id';
        $fieldName = static::$autocompleteParams[$type][$variableName]['field'];
        $isI18n = static::$autocompleteParams[$type][$variableName]['is_i18n'];

        $query = $relatedQueries[$variableName];
        $queryForFilter = $query;

        if ($isI18n)
        {
          $queryForFilter = $queryForFilter
          ->joinWithI18n($culture)
          ->useI18nQuery($culture);
        }
        
        $queryForFilter->{sprintf('filterBy%s', $fieldName)}((mb_strlen($value) >= 3 ? '%' : NULL) . $value . '%', Criteria::LIKE);

        if ($isI18n)
        {
          $queryForFilter = $queryForFilter->endUse();
        }

        break;
      }
    }

    if (!isset($query))
    {
      if ($request->query->has('output'))
      {
        $outputFieldName = Container::camelize($request->query->get('output', ''));

        if (!in_array($outputFieldName, static::$fields))
        {
          return new Response('', 400);
        }
      }

      $className = static::QUERY;
      $query = $className::create();

      foreach ($postData as $variableName => $value)
      {
        $fieldName = Container::camelize($variableName);

        if (in_array($fieldName, static::$fields))
        {
          $query->{sprintf('filterBy%s', $fieldName)}((mb_strlen($value) >= 3 ? '%' : NULL) . $value . '%', Criteria::LIKE);
        }
      }
    }

    if (!isset($query))
    {
      return new Response('', 400);
    }

    $data = $query
    ->find()
    ->toKeyValue($outputFieldName ?? $fieldName, $fieldName);

    return $this->json($data);
  }

  protected function handleEdit(
    Request $request,
    SessionInterface $session,
    Log $log,
    $object
  )
  {
    $isNew = $object->isNew();
    $culture = $session->get('culture');

    if (static::LOG && !$isNew)
    {
      $objectOld = $object->copy();

      if (static::ENTITY_CLASS_I18N)
      {
        $objectOld->{sprintf('add%s', static::ENTITY_CLASS_I18N)}($object->getCurrentTranslation()->copy());
      }

      $relatedCollectionsOld = [];

      foreach (static::$manyToManyRelations as $fieldName => $relation)
      {
        $relatedCollectionsOld[$fieldName] = clone $object->{sprintf('get%s', $relation[0])}();
      }
    }

    $includedQueryStringParams = $this->getIncludedQueryStringParams($request);

    $form = $this->getEditForm(
      $object,
      $culture,
      $includedQueryStringParams
    );
    
    $this->copyIncludedRequestValues($request);
    $form->handleRequest($request);

    if ($form->isSubmitted())
    {
      if ($request->headers->get('X-No-Save'))
      {
        return $this->render(sprintf('fontai_generator/%s/edit_standalone.html.twig', static::SINGULAR_NAME), [
          static::SINGULAR_NAME => $object,
          'edit_form' => $form->createView(),
          'query_string' => $includedQueryStringParams,
          'back_path' => $this->getBackPath($request)
        ]);
      }

      if ($form->isValid() && method_exists($this, 'beforePost'))
      {
        $this->beforePost($request, $form);
      }

      if (!$form->isValid())
      {
        return $this->parseErrors('Formulář obsahuje tyto chyby:', $form);
      }
      
      if (static::LOG)
      {
        $diff = [];

        if (!$isNew)
        {
          if (
            $object->isModified()
            ||
            (static::ENTITY_CLASS_I18N && $object->getCurrentTranslation()->isModified())
          )
          {
            $diff = $objectOld->diff($object);
          }

          foreach (static::$manyToManyRelations as $fieldName => $relation)
          {
            $relatedCollection = $object->{sprintf('get%s', $relation[0])}();
            $relatedIdsOld = $relatedCollectionsOld[$fieldName]->getColumnValues();
            $relatedIds = $relatedCollection->getColumnValues();

            if (array_diff($relatedIdsOld, $relatedIds) != array_diff($relatedIds, $relatedIdsOld))
            {
              $data = [
                [$relatedIdsOld, []],
                [$relatedIds, []]
              ];

              if (!$relation[1])
              {
                $data[0][1] = array_map(function($object) { return $object->__toString(); }, $relatedCollectionsOld[$fieldName]->getData());
                $data[1][1] = array_map(function($object) { return $object->__toString(); }, $relatedCollection->getData());
              }
              else
              {
                $codes = call_user_func([sprintf('%s\LanguageQuery', static::MODEL_NAMESPACE), 'create'])
                ->select('Code')
                ->find();

                foreach ($codes as $code)
                {
                  $data[0][1][$code] = array_map(function($object) use ($code) { return $object->getTranslation($code)->__toString(); }, $relatedCollectionsOld[$fieldName]->getData());
                  $data[1][1][$code] = array_map(function($object) use ($code) { return $object->getTranslation($code)->__toString(); }, $relatedCollection->getData());
                }
              }

              $diff[$fieldName] = $data;
            }
          }
        }
      }

      $object->save();

      if (method_exists($this, 'afterPostSuccess'))
      {
        $this->afterPostSuccess($object, $request);
      }

      if ($isNew && static::ENTITY_CLASS_I18N)
      {
        $this->createTranslations($object);
      }

      if (static::FULLTEXT_SEARCH)
      {
        $this->elasticUpdate($object);
      }

      if (static::LOG && ($isNew || count($diff)))
      {
        call_user_func([$log, $isNew ? 'createEventCreate' : 'createEvent'], $object, $this->getUser(), $diff);
      }

      return $this->json([
        'success'  => TRUE,
        'redirect' => $this->getRedirectPath($request, $object)
      ]);
    }

    return $this->render(sprintf('fontai_generator/%s/edit.html.twig', static::SINGULAR_NAME), [
      static::SINGULAR_NAME => $object,
      'edit_form'           => $form->createView(),
      'edit_form_mapping'   => $form->all(),
      'query_string'        => $includedQueryStringParams,
      'title'               => static::$editTitle[$isNew ? 0 : 1],
      'navigation'          => static::ENABLE_NAVIGATION ? $this->getNavigation($request, $session, $object) : NULL,
      'back_path'           => $this->getBackPath($request)
    ]);
  }

  protected function getEditForm(
    $object,
    string $culture,
    array $queryStringParams = []
  )
  {
    return $this->createForm(
      static::EDIT_FORM_TYPE,
      $object,
      [
        'culture' => $culture,
        'settings' => $this->settings,
        'user' => $this->getUser(),
        'view_timezone' => $this->get('twig')->getExtension('Twig\Extension\CoreExtension')->getTimezone()->getName(),
        'related_query' => $this->getRelatedQueries($culture),
        'action' => $this->generateUrl(
          sprintf(
            '%s_%s',
            static::ROUTE_NAME_PREFIX,
            $object->isNew() ? 'create' : 'edit'
          ),
          array_merge(
            [
              'id' => $object->getId(),
              'culture' => $culture
            ],
            $queryStringParams
          )
        )
      ]
    );
  }

  protected function getQuickEditForm(
    $object,
    string $culture,
    array $queryStringParams = []
  )
  {
    return static::QUICKEDIT_FORM_TYPE
    ? $this->get('form.factory')->createNamed(
      sprintf('%s_%d', static::SINGULAR_NAME, $object->getId()),
      static::QUICKEDIT_FORM_TYPE,
      $object,
      [
        'culture' => $culture,
        'settings' => $this->settings,
        'user' => $this->getUser(),
        'view_timezone' => $this->get('twig')->getExtension('Twig\Extension\CoreExtension')->getTimezone()->getName(),
        'related_query' => $this->getRelatedQueries($culture),
        'action' => $this->generateUrl(
          sprintf('%s_quickedit', static::ROUTE_NAME_PREFIX),
          array_merge(
            [
              'id' => $object->getId(),
              'culture' => $culture
            ],
            $queryStringParams
          )
        )
      ]
    )
    : NULL;
  }

  protected function getQuickEditFormsFromPager(
    PropelModelPager $pager,
    string $culture,
    array $queryStringParams = []
  )
  {
    $forms = [];

    foreach ($pager as $object)
    {
      $forms[$object->getId()] = $this->getQuickEditForm($object, $culture, $queryStringParams)->createView();
    }

    return $forms;
  }

  protected function handleQuickedit(
    Request $request,
    SessionInterface $session,
    Log $log,
    $object
  )
  {
    if (static::LOG)
    {
      $objectOld = $object->copy();

      if (static::ENTITY_CLASS_I18N)
      {
        $objectOld->{sprintf('add%s', static::ENTITY_CLASS_I18N)}($object->getCurrentTranslation()->copy());
      }

      $relatedCollectionsOld = [];

      foreach (static::$manyToManyRelations as $fieldName => $relation)
      {
        $relatedCollectionsOld[$fieldName] = clone $object->{sprintf('get%s', $relation[0])}();
      }
    }

    $form = $this->getQuickEditForm(
      $object,
      $session->get('culture'),
      $this->getIncludedQueryStringParams($request)
    );
    
    $this->copyIncludedRequestValues($request);
    $form->handleRequest($request);

    if ($form->isSubmitted())
    {
      if ($form->isValid() && method_exists($this, 'beforePost'))
      {
        $this->beforePost($request, $form);
      }

      if (!$form->isValid())
      {
        return $this->parseErrors('Formulář obsahuje tyto chyby:', $form);
      }
      else
      {
        if (static::LOG)
        {
          $diff = [];

          if ($object->isModified() || (static::ENTITY_CLASS_I18N && $object->getCurrentTranslation()->isModified()))
          {
            $diff = $objectOld->diff($object);
          }

          foreach (static::$manyToManyRelations as $fieldName => $relation)
          {
            $relatedCollection = $object->{sprintf('get%s', $relation[0])}();
            $relatedIdsOld = $relatedCollectionsOld[$fieldName]->getColumnValues();
            $relatedIds = $relatedCollection->getColumnValues();

            if (array_diff($relatedIdsOld, $relatedIds) != array_diff($relatedIds, $relatedIdsOld))
            {
              $data = [
                [$relatedIdsOld, []],
                [$relatedIds, []]
              ];

              if (!$relation[1])
              {
                $data[0][1] = array_map(function($object) { return $object->__toString(); }, $relatedCollectionsOld[$fieldName]->getData());
                $data[1][1] = array_map(function($object) { return $object->__toString(); }, $relatedCollection->getData());
              }
              else
              {
                $codes = call_user_func([sprintf('%s\LanguageQuery', static::MODEL_NAMESPACE), 'create'])
                ->select('Code')
                ->find();

                foreach ($codes as $code)
                {
                  $data[0][1][$code] = array_map(function($object) use ($code) { return $object->getTranslation($code)->__toString(); }, $relatedCollectionsOld[$fieldName]->getData());
                  $data[1][1][$code] = array_map(function($object) use ($code) { return $object->getTranslation($code)->__toString(); }, $relatedCollection->getData());
                }
              }

              $diff[$fieldName] = $data;
            }
          }
        }

        $object->save();

        if (method_exists($this, 'afterPostSuccess'))
        {
          $this->afterPostSuccess($object, $request);
        }

        if (static::FULLTEXT_SEARCH)
        {
          $this->elasticUpdate($object);
        }

        if (static::LOG && count($diff))
        {
          $log->createEvent($object, $this->getUser(), $diff);
        }

        return $this->json([
          'success'  => TRUE
        ]);
      }
    }

    return $this->render(sprintf('fontai_generator/%s/quickedit.html.twig', static::SINGULAR_NAME), [
      static::SINGULAR_NAME => $object,
      'quickedit_form'      => $form->createView(),
    ]);
  }

  protected function addIncludedCriteria(
    ModelCriteria $query,
    Request $request
  )
  {
    $getData = $request->query->get(static::SINGULAR_NAME);

    foreach (static::$includedIn as $className)
    {
      $singularName = Container::underscore($className);
      $includedObject = $request->attributes->get($singularName);

      if ($includedObject === NULL)
      {
        $id = $getData[sprintf('%s_id', $singularName)] ?? NULL;

        if ($id)
        {
          $includedObject = call_user_func([sprintf('%s\%sQuery', static::MODEL_NAMESPACE, $className), 'create'])
          ->findOneById($id);
        }
      }
      
      if (is_a($includedObject, sprintf('%s\%s', static::MODEL_NAMESPACE, $className)))
      {
        $method = sprintf('filterBy%s', $className);

        if (!method_exists($query, $method))
        {
          $method = sprintf('%sRelatedBy%sId', $method, $className);
        }

        call_user_func([$query, $method], $includedObject);
      }
    }
  }

  protected function getIncludedQueryStringParams(Request $request)
  {
    $params = [];
    $getData = $request->query->get(static::SINGULAR_NAME);

    foreach (static::$includedIn as $className)
    {
      $singularName = Container::underscore($className);
      $includedObject = $request->attributes->get($singularName);
      $variableName = sprintf('%s_id', $singularName);

      if ($includedObject === NULL)
      {
        $id = $getData[$variableName] ?? NULL;

        if ($id)
        {
          $includedObject = call_user_func([sprintf('%s\%sQuery', static::MODEL_NAMESPACE, $className), 'create'])
          ->findOneById($id);
        }
      }
      
      if (is_a($includedObject, sprintf('%s\%s', static::MODEL_NAMESPACE, $className)))
      {
        $params[static::SINGULAR_NAME][$variableName] = $includedObject->getId();
      }
    }

    return $params;
  }

  protected function setIncludedObjectValues(
    $object,
    Request $request
  )
  {
    foreach (static::$includedIn as $className)
    {
      $singularName = Container::underscore($className);
      $includedObject = $request->attributes->get($singularName);
      
      if (is_a($includedObject, sprintf('%s\%s', static::MODEL_NAMESPACE, $className)))
      {
        $method = sprintf('set%s', $className);

        if (!method_exists($object, $method))
        {
          $method = sprintf('%sRelatedBy%sId', $method, $className);
        }

        call_user_func([$object, $method], $includedObject);
      }
    }
  }

  protected function copyIncludedRequestValues(Request $request)
  {
    if ($request->getMethod() !== 'POST')
    {
      return;
    }

    $getData = $request->query->get(static::SINGULAR_NAME);
    $postData = $request->request->get(static::SINGULAR_NAME);

    foreach (static::$includedIn as $className)
    {
      $variableName = sprintf('%s_id', Container::underscore($className));

      if (isset($getData[$variableName]) && !isset($postData[$variableName]))
      {
        $postData[$variableName] = $getData[$variableName];
        $request->request->set(static::SINGULAR_NAME, $postData);
      }
    }
  }

  protected function getRedirectPath(
    Request $request,
    $object = NULL
  )
  {
    $session = $request->getSession();
    $getData = $request->query->get(static::SINGULAR_NAME);

    foreach (static::$includedIn as $className)
    {
      $variableName = sprintf('%s_id', Container::underscore($className));

      if (isset($getData[$variableName]))
      {
        $controllerClass = sprintf('%s\%sController', static::CONTROLLER_NAMESPACE, $className);
       
        return sprintf(
          '%s#included-%s',
          $this->generateUrl(sprintf('%s_edit', $controllerClass::ROUTE_NAME_PREFIX), [
            'id' => $getData[$variableName],
            'culture' => $session->get('culture')
          ]),
          static::ENTITY_CLASS
        );
      }
    }

      $url = $this->generateUrl(sprintf('%s_index', static::ROUTE_NAME_PREFIX), [
          'culture' => $session->get('culture')
      ]);
      if ($object) {
          $url = $this->generateUrl(sprintf('%s_edit', static::ROUTE_NAME_PREFIX), [
              'id' => $object->getId(),
              'culture' => $session->get('culture')
          ]);
      }
      if ($request->request->has('save_and_add')) {
          $url = $this->generateUrl(sprintf('%s_create', static::ROUTE_NAME_PREFIX), [
              'culture' => $session->get('culture')
          ]);
      } else if ($request->request->has('save_and_list')) {
          $url = $this->generateUrl(sprintf('%s_index', static::ROUTE_NAME_PREFIX), [
              'culture' => $session->get('culture')
          ]);
      }
      return $url;
  }

  protected function getBackPath(Request $request)
  {
    $session = $request->getSession();
    $getData = $request->query->get(static::SINGULAR_NAME);

    foreach (static::$includedIn as $className)
    {
      $variableName = sprintf('%s_id', Container::underscore($className));

      if (isset($getData[$variableName]))
      {
        $controllerClass = sprintf('%s\%sController', static::CONTROLLER_NAMESPACE, $className);
       
        return sprintf(
          '%s#included-%s',
          $this->generateUrl(sprintf('%s_edit', $controllerClass::ROUTE_NAME_PREFIX), [
            'id' => $getData[$variableName],
            'culture' => $session->get('culture')
          ]),
          static::ENTITY_CLASS
        );
      }
    }

    return $this->generateUrl(sprintf('%s_index', static::ROUTE_NAME_PREFIX), [
      'culture' => $session->get('culture')
    ]);
  }

  protected function isIncluded(Request $request)
  {
    $getData = $request->query->get(static::SINGULAR_NAME);

    foreach (static::$includedIn as $className)
    {
      $singularName = Container::underscore($className);
      
      if ($request->attributes->has($singularName) || isset($getData[sprintf('%s_id', $singularName)]))
      {
        return TRUE;
      }
    }

    return FALSE;
  }

  public static function getIncludedControllers()
  {
    return static::$includedIn;
  }
}