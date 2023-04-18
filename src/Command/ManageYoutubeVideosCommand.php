<?php

namespace App\Command;

use App\Services\ManageYoutubeVideosBdd;
use App\Repository\YoutubeVideoRepository;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'manage-youtube-videos', description: 'Gérer les vidéos Youtube depuis l\'API Youtube')]
class ManageYoutubeVideosCommand extends Command
{
    public function __construct(private ManageYoutubeVideosBdd $manageYoutubeVideosBdd, private YoutubeVideoRepository $youtubeVideoRepository)
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
        $this->manageYoutubeVideosBdd->ManageYoutubeVideos($this->youtubeVideoRepository);

        $output->writeln('Command executed successfully.');

        return Command::SUCCESS;
    }
}
