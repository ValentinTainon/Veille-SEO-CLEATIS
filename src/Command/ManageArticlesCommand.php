<?php

namespace App\Command;

use App\Services\ManageArticlesBdd;
use App\Repository\ArticleRepository;
use App\Repository\FluxRssRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'manage-articles', description: 'Gérer les articles depuis les flux RSS')]
class ManageArticlesCommand extends Command
{
    public function __construct(private ManageArticlesBdd $manageArticlesBdd, private ArticleRepository $articleRepository, private FluxRssRepository $fluxRssRepository)
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
        $this->manageArticlesBdd->ManageArticles($this->articleRepository, $this->fluxRssRepository);

        $output->writeln('Command executed successfully.');

        return Command::SUCCESS;
    }
}
