<?php

namespace Wexample\SymfonyRemoteDbIp\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Wexample\SymfonyRemoteDbIp\Service\DbIpDatabaseUpdater;

#[AsCommand(name: 'db-ip:update', description: 'Downloads the DB-IP IP to Country Lite database of the month')]
class DbIpUpdateCommand extends Command
{
    public function __construct(
        private readonly DbIpDatabaseUpdater $updater,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addOption('if-stale', null, InputOption::VALUE_NONE, 'Only when the file is missing or older than a month: for a scheduled run.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if ($input->getOption('if-stale') && ! $this->updater->isStale()) {
            $io->success('The database is up to date.');

            return Command::SUCCESS;
        }

        $io->success(sprintf('Database of %s in place.', $this->updater->update()));

        return Command::SUCCESS;
    }
}
