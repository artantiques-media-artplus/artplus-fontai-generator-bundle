<?php
namespace Fontai\Bundle\GeneratorBundle\Command;

use Fontai\Bundle\GeneratorBundle\Command\Helper\QuestionHelper;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Base class for generator commands.
 *
 * @author Lumír Toman <toman@websource.cz>
 */
abstract class GeneratorCommand extends Command
{
  protected $container;
  protected $filesystem;

  /**
   * @var Generator
   */
  private $generator;

  public function __construct(
    ContainerInterface $container,
    Filesystem $filesystem
  )
  {
    $this->container = $container;
    $this->filesystem = $filesystem;

    parent::__construct();
  }

  // only useful for unit tests
  public function setGenerator(Generator $generator)
  {
    $this->generator = $generator;
  }

  abstract protected function createGenerator();

  protected function getGenerator()
  {
    if (null === $this->generator)
    {
      $this->generator = $this->createGenerator();
      $this->generator->setSkeletonDirs($this->getSkeletonDirs());
    }

    return $this->generator;
  }

  protected function getSkeletonDirs()
  {
    $skeletonDirs = array();

    if (is_dir($dir = $this->container->getParameter('kernel.project_dir') . '/templates/FontaiGeneratorBundle/skeleton'))
    {
      $skeletonDirs[] = $dir;
    }

    $skeletonDirs[] = __DIR__ . '/../Resources/skeleton';

    return $skeletonDirs;
  }

  protected function getQuestionHelper()
  {
    $question = $this->getHelperSet()->get('question');
    
    if (!$question || get_class($question) !== 'Fontai\Bundle\GeneratorBundle\Command\Helper\QuestionHelper')
    {
      $this->getHelperSet()->set($question = new QuestionHelper());
    }

    return $question;
  }

  /**
   * Tries to make a path relative to the project, which prints nicer.
   *
   * @param string $absolutePath
   *
   * @return string
   */
  protected function makePathRelative($absolutePath)
  {
    $projectDir = dirname($this->container->getParameter('kernel.project_dir'));

    return str_replace($projectDir.'/', '', realpath($absolutePath) ?: $absolutePath);
  }
}