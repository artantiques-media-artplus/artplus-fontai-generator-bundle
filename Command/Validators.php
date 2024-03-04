<?php
namespace Fontai\Bundle\GeneratorBundle\Command;


class Validators
{
  public static function validateEntityName($entity)
  {
    if (!preg_match('{^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff\\\]+$}', $entity))
    {
      throw new \InvalidArgumentException(sprintf('The entity name isn\'t valid ("%s" given, expecting something like App\Model\Post)', $entity));
    }

    return $entity;
  }

  public static function validateFormat($format)
  {
    if (!$format)
    {
      throw new \RuntimeException('Please enter a configuration format.');
    }

    $format = strtolower($format);

    if ($format == 'yml')
    {
      $format = 'yaml';
    }

    if (!in_array($format, ['php', 'xml', 'yaml', 'annotation']))
    {
      throw new \RuntimeException(sprintf('Format "%s" is not supported.', $format));
    }

    return $format;
  }
}
