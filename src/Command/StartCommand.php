<?php

namespace App\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use App\Services\StartService;

class StartCommand extends Command
{
    protected static $defaultName = 'app:start';

    protected function configure()
    {
        $this
            ->setDescription('Add a short description for your command')
            ->addArgument('server_ip', InputArgument::REQUIRED, 'Server Ip')
            ->addArgument('start_page', InputArgument::REQUIRED, 'Start Page')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $arg1 = $input->getArgument('server_ip');
        $arg2 = $input->getArgument('start_page');
        
        if ($arg1 && $arg2) {
            
            $service = new StartService($arg1, $arg2);
            $service->runService();
            
            $io->note(sprintf('You passed an argument: %s', $arg1));
        }

        if ($input->getOption('option1')) {
            // ...
        }

        $io->success('You have a new command! Now make it your own! Pass --help to see your options.');
    }
}
