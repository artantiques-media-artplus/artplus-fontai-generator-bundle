<?php
namespace Fontai\Bundle\GeneratorBundle\Form\DataTransformer;

use Propel\Runtime\ActiveRecord\ActiveRecordInterface;
use Propel\Runtime\Collection\ObjectCollection;
use Symfony\Component\Form\DataTransformerInterface;
use Symfony\Component\Form\Exception\TransformationFailedException;


class ValuesToObjectsTransformer implements DataTransformerInterface
{
  protected $isMultiple;

  public function __construct(bool $isMultiple = FALSE)
  {
    $this->isMultiple = $isMultiple;
  }

  public function transform($array)
  {
    return $array;
  }

  public function reverseTransform($objectOrCollection)
  {
    if (NULL === $objectOrCollection)
    {
      return $this->isMultiple ? [] : NULL;
    }

    if ($this->isMultiple)
    {
      if (!$objectOrCollection instanceof ObjectCollection)
      {
        throw new TransformationFailedException('Expected a \Propel\Runtime\Collection\ObjectCollection.');
      }

      return $objectOrCollection->getColumnValues('Id');
    }
    
    if (!$objectOrCollection instanceof ActiveRecordInterface)
    {
      throw new TransformationFailedException('Expected a \Propel\Runtime\ActiveRecord\ActiveRecordInterface.');
    }

    return $objectOrCollection->getId();
  }
}
