<?php
namespace Fontai\Bundle\GeneratorBundle\Command\Helper;

use Symfony\Component\Console\Helper\QuestionHelper as BaseQuestionHelper;
use Symfony\Component\Console\Output\OutputInterface;


class QuestionHelper extends BaseQuestionHelper
{
  public function writeGeneratorSummary(
    OutputInterface $output,
    $errors
  )
  {
    if (!$errors)
    {
      $this->writeSection($output, 'Everything is OK! Now get to work :).');
    }
    else
    {
      $this->writeSection($output, [
        'The command was not able to configure everything automatically.',
        'You\'ll need to make the following changes manually.',
      ], 'error');

      $output->writeln($errors);
    }
  }

  public function getRunner(
    OutputInterface $output,
    &$errors
  )
  {
    $runner = function ($err) use ($output, &$errors)
    {
      if ($err)
      {
        $output->writeln('<fg=red>FAILED</>');
        $errors = array_merge($errors, $err);
      }
      else
      {
        $output->writeln('<info>OK</info>');
      }
    };

    return $runner;
  }

  public function writeSection(
    OutputInterface $output,
    $text,
    $style = 'bg=blue;fg=white'
  )
  {
    $output->writeln([
      '',
      $this->getHelperSet()->get('formatter')->formatBlock($text, $style, true),
      '',
    ]);
  }

  public function getQuestion(
    $question,
    $default,
    $sep = ':'
  )
  {
    return $default ? sprintf('<info>%s</info> [<comment>%s</comment>]%s ', $question, $default, $sep) : sprintf('<info>%s</info>%s ', $question, $sep);
  }
}
