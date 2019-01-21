<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Services\StartService;
use Doctrine\Common\Persistence\ManagerRegistry;

class StartCommand extends Command
{
    protected static $defaultName = 'app:start';

    /** @var ManagerRegistry */
    public $doctrine;
    
    public function __construct( ManagerRegistry $doctrine ) {
        parent::__construct();
        $this->doctrine = $doctrine;
    }
    
    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('config', InputArgument::REQUIRED, 'Yaml Configuration File')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('config');
        
        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
            (new StartService( $this->doctrine, $arg1))->runService();
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
