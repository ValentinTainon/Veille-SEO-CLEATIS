<?php

namespace App\Command;

use App\Repository\ArticleRepository;
use App\Repository\RssFeedRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use App\Services\ManageArticlesGeneratedByRssFeedEvent;

#[AsCommand(name: 'manage:articles', description: 'Gérer les articles provenant des flux RSS')]
class ManageArticlesCommand extends Command
{
    public function __construct(private ManageArticlesGeneratedByRssFeedEvent $ManageArticlesGeneratedByRssFeedEvent, private ArticleRepository $articleRepository, private RssFeedRepository $rssFeedRepository, private MailerInterface $mailer)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->ManageArticlesGeneratedByRssFeedEvent->manageArticlesGeneratedByRssFeed($this->articleRepository, $this->rssFeedRepository, $this->mailer);

        $output->writeln('Command executed successfully.');

        return Command::SUCCESS;
    }
}
