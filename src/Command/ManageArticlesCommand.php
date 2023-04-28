<?php

namespace App\Command;

use App\Repository\ArticleRepository;
use App\Repository\FluxRssRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Services\ManageArticlesGeneratedByRssFeedEvent;

#[AsCommand(name: 'manage-articles', description: 'Gérer les articles générés par les flux RSS')]
class ManageArticlesCommand extends Command
{
    public function __construct(private ManageArticlesGeneratedByRssFeedEvent $ManageArticlesGeneratedByRssFeedEvent, private ArticleRepository $articleRepository, private FluxRssRepository $fluxRssRepository)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ManageArticlesGeneratedByRssFeedEvent->manageArticlesGeneratedByRssFeed($this->articleRepository, $this->fluxRssRepository);

        $output->writeln('Command executed successfully.');

        return Command::SUCCESS;
    }
}
