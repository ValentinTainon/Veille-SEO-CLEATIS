<?php

namespace App\Entity;

use App\Repository\FluxRssRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\OneToMany(mappedBy: 'fluxRss', targetEntity: Article::class)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->lienRss;
    }

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

    /**
     * @return Collection<int, Article>
     */
    public function getArticles(): Collection
    {
        return $this->articles;
    }

    public function addArticle(Article $article): self
    {
        if (!$this->articles->contains($article)) {
            $this->articles->add($article);
            $article->setFluxRss($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getFluxRss() === $this) {
                $article->setFluxRss(null);
            }
        }

        return $this;
    }
}
