<?php
namespace Fontai\Bundle\GeneratorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;


class Unique extends Constraint
{
  /**
   * @var string
   */
  public $message = 'A {{ object_class }} object already exists with {{ fields }}';

  /**
   * @var string Used to merge multiple fields in the message
   */
  public $messageFieldSeparator = ' and ';

  /**
   * @var
   */
  public $object = '';

  /**
   * @var array
   */
  public $fields = [];

  public function __construct($options = null)
  {
    parent::__construct($options);

    if (!is_array($this->fields) && !is_string($this->fields))
    {
      throw new UnexpectedTypeException($this->fields, 'array');
    }

    if (0 === count($this->fields))
    {
      throw new ConstraintDefinitionException("At least one field must be specified.");
    }
  }

  /**
   * {@inheritDoc}
   */
  public function getRequiredOptions()
  {
    return ['object', 'fields'];
  }
}
