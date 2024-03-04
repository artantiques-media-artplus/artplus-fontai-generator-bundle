<?php
namespace Fontai\Bundle\GeneratorBundle\Common;


class Utils
{
  public static function notEmpty($vOrArr)
  {
    return is_array($vOrArr)
    ? count(array_filter($vOrArr, function($v)
    {
      return $v !== '' && $v !== NULL;
    }))
    : $vOrArr !== '' && $vOrArr !== NULL;
  }
}