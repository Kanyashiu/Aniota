<?php

namespace App\Entity;

use App\Repository\ExcludeAnimeRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * @ORM\Entity(repositoryClass=ExcludeAnimeRepository::class)
 */
class ExcludeAnime
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     */
    private $name;

    /**
     * @ORM\Column(type="integer")
     */
    private $mal_id;

    /**
     * @ORM\Column(type="integer")
     */
    private $page;

    /**
     * @ORM\Column(type="integer")
     */
    private $genre_id;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getMalId(): ?int
    {
        return $this->mal_id;
    }

    public function setMalId(int $mal_id): self
    {
        $this->mal_id = $mal_id;

        return $this;
    }

    public function getPage(): ?int
    {
        return $this->page;
    }

    public function setPage(int $page): self
    {
        $this->page = $page;

        return $this;
    }

    public function getGenreId(): ?int
    {
        return $this->genre_id;
    }

    public function setGenreId(int $genre_id): self
    {
        $this->genre_id = $genre_id;

        return $this;
    }
}
