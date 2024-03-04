<?php
namespace Fontai\Bundle\GeneratorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;


class EmailDelimitedValidator extends ConstraintValidator
{
  /**
   * @var bool
   */
  private $defaultMode;

  public function __construct(string $defaultMode = Email::VALIDATION_MODE_LOOSE)
  {
    $this->defaultMode = $defaultMode;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint)
  {
    if (!$constraint instanceof EmailDelimited)
    {
      throw new UnexpectedTypeException($constraint, __NAMESPACE__ . '\EmailDelimited');
    }

    if (NULL === $value || '' === $value)
    {
      return;
    }

    if (!is_scalar($value) && !(is_object($value) && method_exists($value, '__toString')))
    {
      throw new UnexpectedTypeException($value, 'string');
    }

    $value = (string) $value;

    if (NULL === $constraint->mode)
    {
      $constraint->mode = $this->defaultMode;
    }

    $emailConstraint = new Email();

    foreach (['message', 'mode'] as $prop)
    {
      $emailConstraint->{$prop} = $constraint->{$prop};
    }

    $context = $this->context;

    $validator = $context->getValidator()->inContext($context);

    foreach (array_map('trim', explode(',', $value)) as $valueSplit)
    {
      if (!$valueSplit)
      {
        $this->context->buildViolation('This value is not valid.')
        ->addViolation();
      }
      else
      {
        $validator->validate(
          $valueSplit,
          $emailConstraint
        );
      }
    }
  }
}
