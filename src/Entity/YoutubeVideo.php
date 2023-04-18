<?php

namespace App\Entity;

use App\Repository\YoutubeVideoRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: YoutubeVideoRepository::class)]
class YoutubeVideo
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $iframe = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getIframe(): ?string
    {
        return $this->iframe;
    }

    public function setIframe(?string $iframe): self
    {
        $this->iframe = $iframe;

        return $this;
    }
}
