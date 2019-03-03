<?php

namespace App\Command;

use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Models\Options;
use App\Services\MainService;

class MainCommand extends Command
{
    protected static $defaultName = 'app:main';

    /** @var ManagerRegistry */
    public $doctrine;
    
    public function __construct( ManagerRegistry $doctrine ) {
        parent::__construct();
        $this->doctrine = $doctrine;
    }
    
    protected function configure()
    {
        $this
            ->setDescription('Vboxmanage command')
            ->addArgument('config', InputArgument::REQUIRED, 'Yaml Configuration File')
            ->addOption('clones', null, InputOption::VALUE_OPTIONAL, 'all, {clone_name}', null)
            ->addOption('start')
            ->addOption('stop')
            ->addOption('remove')
            ->addOption('spawn')
            ->addOption('init-base')
            ->addOption('respawn')
            ->addOption('start-service')
            ->addOPtion('stop-service')
            ->addOPtion('exit-service')
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('config');
        
        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
            if( $input->getOption('init-base') ) {
                (new MainService( $this->doctrine, $arg1 ))->initBase();
            } else {
                (new MainService( $this->doctrine, $arg1 ))->runService( (new Options())->deserialize( $input->getOptions() ) );
            }
        }

        $io->success('done.');
    }
}
