<?php

namespace App\Entity;

use App\Repository\RssFeedRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RssFeedRepository::class)]
class RssFeed
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $xmlLink = null;

    #[ORM\OneToMany(mappedBy: 'rssFeed', targetEntity: Article::class)]
    private Collection $articles;

    public function __construct()
    {
        $this->articles = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->xmlLink;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getXmlLink(): ?string
    {
        return $this->xmlLink;
    }

    public function setXmlLink(string $xmlLink): self
    {
        $this->xmlLink = $xmlLink;

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
            $article->setRssFeed($this);
        }

        return $this;
    }

    public function removeArticle(Article $article): self
    {
        if ($this->articles->removeElement($article)) {
            // set the owning side to null (unless already changed)
            if ($article->getRssFeed() === $this) {
                $article->setRssFeed(null);
            }
        }

        return $this;
    }
}
