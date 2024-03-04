<?php
namespace Fontai\Bundle\GeneratorBundle\Validator\Constraints;

use Symfony\Component\Validator\Constraints\Email;

/**
 * @Annotation
 * @Target({"PROPERTY", "METHOD", "ANNOTATION"})
 */
class EmailDelimited extends Email
{
}
