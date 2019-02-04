<?php

namespace App\Command;

use App\Services\VboxmanageService;
use Doctrine\Common\Persistence\ManagerRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class VboxmanageCommand extends Command
{
    protected static $defaultName = 'app:vboxmanage';

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
            ->addOption('start', null, InputOption::VALUE_OPTIONAL, 'all, {clone_name}', null)
            ->addOption('stop', null, InputOption::VALUE_OPTIONAL, 'all, {clone_name}', null)
            ->addOption('remove', null, InputOption::VALUE_OPTIONAL, 'all, {clone_name}', null)
            ->addOption('spawn')
            ->addOption('respawn', null, InputOption::VALUE_OPTIONAL, 'all, {clone_name}', null)
            ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('config');
        
        if ($arg1) {
            $io->note(sprintf('You passed an argument: %s', $arg1));
            (new VboxmanageService( $this->doctrine, $arg1 ))->runService( $input->getOption('start'), $input->getOption('stop'), $input->getOption('remove'), $input->getOption('spawn'), $input->getOption('respawn') );
        }

        $io->success('done.');
    }
}
