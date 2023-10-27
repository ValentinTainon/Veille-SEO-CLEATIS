<?php

namespace App\Command;

use App\Repository\YoutubeVideoRepository;
use App\Services\ManageYoutubeVideosEvent;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'manage:youtube-videos', description: 'Gérer les vidéos Youtube depuis l\'API Youtube')]
class ManageYoutubeVideosCommand extends Command
{
    public function __construct(private ManageYoutubeVideosEvent $manageYoutubeVideosEvent, private YoutubeVideoRepository $youtubeVideoRepository)
    {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->manageYoutubeVideosEvent->ManageYoutubeVideos($this->youtubeVideoRepository);

        $output->writeln('Command executed successfully.');

        return Command::SUCCESS;
    }
}
