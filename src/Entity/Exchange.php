<?php

namespace App\Entity;

use App\Repository\ExchangeRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExchangeRepository::class)]
class Exchange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $Name = null;

    #[ORM\Column(length: 255)]
    private ?string $apiAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $parserClass = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $mainUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->Name;
    }

    public function setName(string $Name): static
    {
        $this->Name = $Name;

        return $this;
    }

    public function getApiAddress(): ?string
    {
        return $this->apiAddress;
    }

    public function setApiAddress(string $apiAddress): static
    {
        $this->apiAddress = $apiAddress;

        return $this;
    }

    public function getParserClass(): ?string
    {
        return $this->parserClass;
    }

    public function setParserClass(?string $parserClass): static
    {
        $this->parserClass = $parserClass;

        return $this;
    }

    public function getMainUrl(): ?string
    {
        return $this->mainUrl;
    }

    public function setMainUrl(?string $mainUrl): static
    {
        $this->mainUrl = $mainUrl;

        return $this;
    }
}
