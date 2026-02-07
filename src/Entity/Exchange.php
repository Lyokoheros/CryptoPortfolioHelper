<?php

namespace App\Entity;

use App\Repository\ExchangeRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Attribute\Groups;

#[ORM\Entity(repositoryClass: ExchangeRepository::class)]
#[ORM\Table(name: 'exchanges')]
class Exchange
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['exchangeDetails'])]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Groups(['exchangeDetails'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['exchangeDetails'])]
    private ?string $apiAddress = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['exchangeDetails'])]
    private ?string $parserClass = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['exchangeDetails'])]
    private ?string $mainUrl = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): ?string
    {
        return $this->name; 
    }

    public function setName(string $name): static
    {
        $this->name = $name;

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
