<?php

namespace App\Entity;

use App\Repository\FluxRssRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FluxRssRepository::class)]
class FluxRss
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $lienRss = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLienRss(): ?string
    {
        return $this->lienRss;
    }

    public function setLienRss(string $lienRss): self
    {
        $this->lienRss = $lienRss;

        return $this;
    }
}
