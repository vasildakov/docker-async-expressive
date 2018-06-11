<?php declare(strict_types = 1);

namespace Infrastructure\Doctrine\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputOption;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManager;

/**
 * Class ImportFixturesCommand
 *
 * @author     Vasil Dakov <vasil.dakov@dunelm.com>
 */
class ImportFixturesCommand extends Command
{
    /**
     * @var array $paths
     */
    protected $paths;

    /**
     * @var \Doctrine\ORM\EntityManager $em
     */
    protected $em;

    /**
     * @var \Doctrine\Common\DataFixtures\Loader $loader
     */
    protected $loader;

    /**
     * @var \Doctrine\Common\DataFixtures\Purger\ORMPurger $purger
     */
    protected $purger;

    /**
     * @var \Doctrine\Common\DataFixtures\Executor\ORMExecutor
     */
    protected $executor;

    /**
     * ImportFixturesCommand constructor.
     *
     * @param EntityManager $em
     * @param Loader $loader
     * @param ORMExecutor $executor
     * @param ORMPurger $purger
     */
    public function __construct(
        EntityManager $em,
        Loader $loader,
        ORMExecutor $executor,
        ORMPurger $purger
    ) {
        $this->em       = $em;
        $this->loader   = $loader;
        $this->executor = $executor;
        $this->purger   = $purger;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('fixtures:import-data-fixtures')
             ->setDescription('Import Doctrine data fixtures')
             ->setHelp('The import command Imports data-fixtures')
             ->addOption('append', null, InputOption::VALUE_NONE, 'Append data to existing data.')
             ->addOption('purge-with-truncate', null, InputOption::VALUE_NONE, 'Truncate tables before inserting data');

        parent::configure();
    }

    /**
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return mixed
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        foreach ($this->paths as $key => $path) {
            if (is_dir($path)) {
                $this->loader->loadFromDirectory($path);
            }
        }

        $fixtures = $this->loader->getFixtures();

        if (!$fixtures) {
            throw new \InvalidArgumentException(
                sprintf('Could not find any fixtures to load in: %s', "\n\n- ".implode("\n- ", $this->paths))
            );
        }

        $purgeMode = $input->getOption('purge-with-truncate')
            ? ORMPurger::PURGE_MODE_TRUNCATE
            : ORMPurger::PURGE_MODE_DELETE;

        $this->purger->setPurgeMode($purgeMode);

        $this->executor->setLogger(function ($message) use ($output) {
            $output->writeln(sprintf('<comment>></comment> <info>%s</info>', $message));
        });

        $this->executor->execute($fixtures, $input->getOption('append'));
    }

    /**
     * @param array $paths
     */
    public function setPath($paths)
    {
        $this->paths = $paths;
    }
}
