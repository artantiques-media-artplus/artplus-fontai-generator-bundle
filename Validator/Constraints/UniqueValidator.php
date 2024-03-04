<?php
namespace Fontai\Bundle\GeneratorBundle\Validator\Constraints;

use Propel\Runtime\Map\TableMap;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;


class UniqueValidator extends ConstraintValidator
{
  public function validate($value, Constraint $constraint)
  {
    if (NULL === $value)
    {
      return;
    }

    $fields            = (array) $constraint->fields;
    $object            = $constraint->object;
    $class             = get_class($object);
    $queryClass        = $class . 'Query';
    $tableMapClass     = constant($class . '::TABLE_MAP');
    $tableMapI18nClass = defined($s = $class . 'I18n::TABLE_MAP') ? constant($s) : NULL;
    $classFields       = $tableMapClass::getFieldnames(TableMap::TYPE_FIELDNAME);
    $classFieldsI18n   = $tableMapI18nClass ? $tableMapI18nClass::getFieldnames(TableMap::TYPE_FIELDNAME) : [];

    $query = $queryClass::create();

    foreach ($fields as $fieldName)
    {
      if (in_array($fieldName, $classFields))
      {
        $query = $query->{sprintf('filterBy%s', $tableMapClass::translateFieldname($fieldName, TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME))}($object->getByName($fieldName, TableMap::TYPE_FIELDNAME));
      }
      elseif (in_array($fieldName, $classFieldsI18n))
      {
        $query = $query->useI18nQuery()
        ->{sprintf('filterBy%s', $tableMapI18nClass::translateFieldname($fieldName, TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME))}($object->getCurrentTranslation()->getByName($fieldName, TableMap::TYPE_FIELDNAME))
        ->endUse();
      }
      else
      {
        throw new ConstraintDefinitionException(sprintf('The field "%s" doesn\'t exist in the "%s" class.', $fieldName, $class));
      }
    }

    $objects = $query->find();
    $count = count($objects);

    if ($count > 1 || ($count === 1 && !$object->equals($objects[0])))
    {
      $fieldParts = [];

      foreach ($fields as $fieldName)
      {
        $isI18n = in_array($fieldName, $classFieldsI18n);

        $fieldParts[] = sprintf(
          '%s "%s"',
          call_user_func([$isI18n ? $tableMapI18nClass : $tableMapClass, 'translateFieldname'], $fieldName, TableMap::TYPE_FIELDNAME, TableMap::TYPE_PHPNAME),
          ($isI18n ? $object->getCurrentTranslation() : $object)->getByName($fieldName, TableMap::TYPE_FIELDNAME)
        );
      }

      $this->context
      ->buildViolation($constraint->message)
      ->setParameters([
        '{{ object_class }}' => $class,
        '{{ fields }}' => implode($constraint->messageFieldSeparator, $fieldParts)
      ])
      ->addViolation();
    }
  }
}
