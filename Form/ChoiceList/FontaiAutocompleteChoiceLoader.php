<?php
namespace Fontai\Bundle\GeneratorBundle\Form\ChoiceList;


class FontaiAutocompleteChoiceLoader extends FontaiChoiceLoader
{
  public function loadChoiceList($value = NULL)
  {
    return $this->factory->createListFromChoices($this->selectedChoices, $value);
  }
}