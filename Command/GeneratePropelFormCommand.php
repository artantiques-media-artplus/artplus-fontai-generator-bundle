<?php
namespace Fontai\Bundle\GeneratorBundle\Command;

use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Fontai\Bundle\GeneratorBundle\Generator\PropelFormGenerator;

/**
 * Generates a form type class for a given Propel entity.
 *
 * @author Lumír Toman <toman@websource.cz>
 */
class GeneratePropelFormCommand extends GeneratePropelCommand
{
  /**
   * @see Command
   */
  protected function configure()
  {
    $this
      ->setName('propel:generate:form')
      ->setAliases(['generate:propel:form'])
      ->setDescription('Generates a form type class based on a Propel entity')
      ->setDefinition([
        new InputArgument('entity', InputArgument::REQUIRED, 'The entity class name to initialize (shortcut notation)'),
      ]);
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $entity      = Validators::validateEntityName($input->getArgument('entity'));
    $entityParts = explode('\\', $entity);

    $tableMap = call_user_func(['\\' . implode('\\', array_slice($entityParts, 0, -1)) . '\\Map\\' . current(array_slice($entityParts, -1, 1)) . 'TableMap', 'getTableMap']);
    $generator = $this->getGenerator();

    $generator->generate($entity, $tableMap, 'fontai_generator_');

    $output->writeln(sprintf(
      'The new %s.php class file has been created under %s.',
      $generator->getClassName(),
      $generator->getClassPath()
    ));

    return 0;
  }

  protected function createGenerator()
  {
    return new PropelFormGenerator($this->filesystem);
  }
}