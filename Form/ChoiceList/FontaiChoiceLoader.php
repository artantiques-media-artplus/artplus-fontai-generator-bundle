<?php
namespace Fontai\Bundle\GeneratorBundle\Form\ChoiceList;

use Propel\Bundle\PropelBundle\Form\ChoiceList\PropelChoiceLoader;
use Propel\Runtime\ActiveQuery\Criteria;
use Propel\Runtime\ActiveQuery\ModelCriteria;
use Symfony\Component\Form\ChoiceList\Factory\ChoiceListFactoryInterface;


class FontaiChoiceLoader extends PropelChoiceLoader
{
  protected $queryMethod;
  protected $excludeChoices;
  protected $selectedChoices = [];

  public function __construct(
    ChoiceListFactoryInterface $factory,
    $class,
    ModelCriteria $queryObject,
    $useAsIdentifier = NULL,
    string $queryMethod = 'find',
    array $excludeChoices = []
  )
  {
    $this->queryMethod = $queryMethod;
    $this->excludeChoices = $excludeChoices;

    parent::__construct(
      $factory,
      $class,
      $queryObject,
      $useAsIdentifier,
      $queryMethod
    );
  }

  public function setSelectedChoices(array $selectedChoices)
  {
    $this->selectedChoices = $selectedChoices;
  }

  public function loadChoiceList($value = NULL)
  {
    if ($this->choiceList)
    {
      return $this->choiceList;
    }

    if ($this->excludeChoices)
    {
      $this->query->filterById($this->excludeChoices, Criteria::NOT_IN);
    }

    $models = $this->query->{$this->queryMethod}();

    foreach ($this->selectedChoices as $selectedChoice)
    {
      if ($selectedChoice !== NULL && !$models->contains($selectedChoice))
      {
        $models->append($selectedChoice);
      }
    }
    
    $this->choiceList = $this->factory->createListFromChoices(iterator_to_array($models), $value);

    return $this->choiceList;
  }
}