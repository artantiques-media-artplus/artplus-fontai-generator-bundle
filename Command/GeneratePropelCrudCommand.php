<?php
namespace Fontai\Bundle\GeneratorBundle\Command;

use Fontai\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Container;
use Propel\Runtime\Propel;
use Propel\Runtime\Map\TableMap;
use Fontai\Bundle\GeneratorBundle\Generator\PropelCrudGenerator;
use Fontai\Bundle\GeneratorBundle\Generator\PropelFormGenerator;
use Fontai\Bundle\GeneratorBundle\Manipulator\RoutingManipulator;

/**
 * Generates a CRUD for a Propel entity.
 *
 * @author Lumír Toman <toman@websource.cz>
 */
class GeneratePropelCrudCommand extends GeneratePropelCommand
{
  private $formGenerator;

  /**
   * @see Command
   */
  protected function configure()
  {
    $this
      ->setName('propel:generate:crud')
      ->setAliases(['generate:propel:crud'])
      ->setDescription('Generates a CRUD based on a Propel entity')
      ->setDefinition([
        new InputArgument('entity', InputArgument::OPTIONAL, 'The entity class name to initialize (shortcut notation)'),
        new InputOption('entity', '', InputOption::VALUE_REQUIRED, 'The entity class name to initialize (shortcut notation)'),
        new InputOption('with-write', '', InputOption::VALUE_NONE, 'Whether or not to generate create, new and delete actions'),
        new InputOption('format', '', InputOption::VALUE_REQUIRED, 'The format used for configuration files (php, xml, yaml)', 'yaml'),
        new InputOption('overwrite', '', InputOption::VALUE_NONE, 'Overwrite any existing controller or form class when generating the CRUD contents'),
      ]);
  }

  /**
   * @see Command
   */
  protected function execute(InputInterface $input, OutputInterface $output)
  {
    $questionHelper = $this->getQuestionHelper();

    if ($input->isInteractive())
    {
      $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you confirm generation', 'yes', '?'), true);
      if (!$questionHelper->ask($input, $output, $question))
      {
        $output->writeln('<error>Command aborted</error>');
        return 1;
      }
    }

    $entity          = Validators::validateEntityName($input->getOption('entity'));
    $entityParts     = explode('\\', $entity);
    $entityClass     = array_pop($entityParts);
    $entityNamespace = implode('\\', $entityParts);

    $format         = Validators::validateFormat($input->getOption('format'));
    $withWrite      = $input->getOption('with-write');
    $forceOverwrite = $input->getOption('overwrite');

    $questionHelper->writeSection($output, 'CRUD generation');

    try {
      $tableMap = call_user_func(['\\' . $entityNamespace . '\\Map\\' . $entityClass . 'TableMap', 'getTableMap']);
    } catch (\Exception $e) {
      throw new \RuntimeException(sprintf('Entity "%s" does not exist in the App namespace. Create it with the "propel:generate:entity" command and then execute this command again.', $entity));
    }

    $prefix = $this->getRoutePrefix($input, $entityClass);

    $generator = $this->getGenerator();
    $generator->generate(
      $entity,
      $tableMap,
      $format,
      $prefix,
      $withWrite,
      $forceOverwrite
    );

    $output->writeln('Generating the CRUD code: <info>OK</info>');

    $errors = [];
    $runner = $questionHelper->getRunner($output, $errors);

    // form
    if ($withWrite)
    {
      $this->generateForm(
        $entity,
        $tableMap,
        $prefix,
        $forceOverwrite
      );
      $output->writeln('Generating the Form code: <info>OK</info>');
    }

    // routing
    $output->write('Updating the routing: ');
    $runner($this->updateRouting(
      $questionHelper,
      $input,
      $output,
      $format,
      $entityClass,
      $prefix
    ));

    $questionHelper->writeGeneratorSummary($output, $errors);

    return 0;
  }

  protected function interact(InputInterface $input, OutputInterface $output)
  {
    $questionHelper = $this->getQuestionHelper();
    $questionHelper->writeSection($output, 'Welcome to the Propel2 CRUD generator');

    // namespace
    $output->writeln([
      '',
      'This command helps you generate CRUD controllers and templates.',
      '',
      'First, give the name of the existing entity for which you want to generate a CRUD',
      '(use the entity class name like <comment>App\Model\Post</comment>)',
      '',
    ]);

    if ($input->hasArgument('entity') && $input->getArgument('entity') != '') {
      $input->setOption('entity', $input->getArgument('entity'));
    }

    $question = new Question($questionHelper->getQuestion('The Entity shortcut name', $input->getOption('entity')), $input->getOption('entity'));
    $question->setValidator(['Fontai\Bundle\GeneratorBundle\Command\Validators', 'validateEntityName']);

    $entity = $questionHelper->ask($input, $output, $question);

    $input->setOption('entity', $entity);
    $entityParts = explode('\\', $entity);
    $entityClass = array_pop($entityParts);
    
    try {
      $tableMap = call_user_func(['\\' . implode('\\', array_slice($entityParts, 0, -1)) . '\\Map\\' . current(array_slice($entityParts, -1, 1)) . 'TableMap', 'getTableMap']);
    } catch (\Exception $e) {
      throw new \RuntimeException(sprintf('Entity "%s" does not exist in the App namespace. You may have mistyped the entity class name or maybe the entity doesn\'t exist yet (create it first with the "propel:generate:entity" command).', $entity));
    }

    // write?
    $withWrite = $input->getOption('with-write') ?: false;
    $output->writeln([
      '',
      'By default, the generator creates two actions: list and edit.',
      'You can also ask it to generate "write" actions: new and delete.',
      '',
    ]);
    $question = new ConfirmationQuestion($questionHelper->getQuestion('Do you want to generate the "write" actions', $withWrite ? 'yes' : 'no', '?', $withWrite), $withWrite);

    $withWrite = $questionHelper->ask($input, $output, $question);
    $input->setOption('with-write', $withWrite);

    // format
    $format = $input->getOption('format');
    $output->writeln([
      '',
      'Determine the format to use for the generated CRUD.',
      '',
    ]);
    $question = new Question($questionHelper->getQuestion('Configuration format (yaml, xml, php)', $format), $format);
    $question->setValidator(['Fontai\Bundle\GeneratorBundle\Command\Validators', 'validateFormat']);
    $format = $questionHelper->ask($input, $output, $question);
    $input->setOption('format', $format);

    // route prefix
    $prefix = $this->getRoutePrefix($input, $entityClass);

    // summary
    $output->writeln([
      '',
      $this->getHelper('formatter')->formatBlock('Summary before generation', 'bg=blue;fg=white', true),
      '',
      sprintf('You are going to generate a CRUD controller for "<info>%s</info>"', $entity),
      sprintf('using the "<info>%s</info>" format.', $format),
      '',
    ]);
  }

  /**
   * Tries to generate forms if they don't exist yet and if we need write operations on entities.
   */
  protected function generateForm($entity, TableMap $tableMap, $prefix, $forceOverwrite = false)
  {
    $this->getFormGenerator()->generate($entity, $tableMap, $prefix, $forceOverwrite);
  }

  protected function updateRouting(QuestionHelper $questionHelper, InputInterface $input, OutputInterface $output, $format, $entityClass, $prefix)
  {
    $auto = true;
    if ($input->isInteractive())
    {
      $question = new ConfirmationQuestion($questionHelper->getQuestion('Confirm automatic update of the Routing', 'yes', '?'), true);
      $auto = $questionHelper->ask($input, $output, $question);
    }

    $projectDir = $this->container->getParameter('kernel.project_dir');

    $output->write('Importing the CRUD routes: ');
    $this->filesystem->mkdir($projectDir . '/config/routes/fontai_generator');

    // first, import the routing file from the bundle's main fontai_generator.yaml file
    $entityUnderscored = Container::underscore($entityClass);
    $routing = new RoutingManipulator($projectDir . '/config/routes/fontai_generator.yaml');
    try {
      $ret = $auto ? $routing->addResource(
        $format,
        '/' . $entityUnderscored,
        'fontai_generator/' . $entityUnderscored
      ) : false;
    } catch (\RuntimeException $exc) {
      $ret = false;
    }

    if (!$ret)
    {
      $help = sprintf("        <comment>resource: \"config/routes/fontai_generator/%s.%s\"</comment>\n",  $entityUnderscored, $format);
      $help .= sprintf("        <comment>prefix:   /%s</comment>\n", $prefix);

      return [
        '- Import the routing resource in the bundle routing file',
        sprintf('  (%s).', $projectDir . '/config/routes/fontai_generator.yaml'),
        '',
        sprintf('    <comment>%s:</comment>', $routing->getImportedResourceYamlKey($prefix)),
        $help,
        '',
      ];
    }

    // second, import the bundle's routing.yaml file from the application's routing.yaml file
    /*$routing = new RoutingManipulator($projectDir . '/config/routing.yaml');
    try {
      $ret = $auto ? $routing->addResource(
        $bundle->getName(),
        'yaml',
        '/' . $prefix
      ) : false;
    } catch (\RuntimeException $e) {
      // the bundle is already imported form app's routing.yaml file
      $errorMessage = sprintf(
        "\n\n[ERROR] The bundle's \"Resources/config/routing.yaml\" file cannot be imported\n".
        "from \"app/config/routing.yaml\" because the \"%s\" bundle is\n".
        "already imported. Make sure you are not using two different\n".
        "configuration/routing formats in the same bundle because it won't work.\n",
        $bundle->getName()
      );
      $output->write($errorMessage);
      $ret = true;
    } catch (\Exception $e) {
      $ret = false;
    }

    if (!$ret) return [
      '- Import the bundle\'s routing.yaml file in the application routing.yaml file',
      sprintf('# app/config/routing.yaml'),
      sprintf('%s:', $bundle->getName()),
      sprintf('    <comment>resource: "@%s/Resources/config/routing.yaml"</comment>', $bundle->getName()),
      '',
      '# ...',
      '',
    ];*/
  }

  protected function getRoutePrefix(InputInterface $input, $entityClass)
  {
    $prefix = 'fontai_generator_' . Container::underscore($entityClass);
    
    if ($prefix && '/' === $prefix[0]) $prefix = substr($prefix, 1);

    return $prefix;
  }

  protected function createGenerator()
  {
    return new PropelCrudGenerator(
      $this->filesystem,
      $this->container->getParameter('kernel.project_dir'),
      $this->container->getParameter('fontai_generator')
    );
  }

  protected function getFormGenerator()
  {
    if (null === $this->formGenerator)
    {
      $this->formGenerator = new PropelFormGenerator(
        $this->filesystem,
        $this->container->getParameter('kernel.project_dir'),
        $this->container->getParameter('fontai_generator')
      );

      $this->formGenerator->setSkeletonDirs($this->getSkeletonDirs());
    }

    return $this->formGenerator;
  }

  public function setFormGenerator(PropelFormGenerator $formGenerator)
  {
    $this->formGenerator = $formGenerator;
  }
}