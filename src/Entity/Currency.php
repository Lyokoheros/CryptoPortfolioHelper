<?php

namespace App\Entity;

use App\Repository\CurrencyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CurrencyRepository::class)]
class Currency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 63)]
    private ?string $name = null;

    #[ORM\Column(length: 15)]
    private ?string $symbol = null;

    #[ORM\Column(length: 127, nullable: true)]
    private ?string $nativeBlockchain = null;

    #[ORM\Column(length: 127, nullable: true)]
    private ?string $network = null;

    #[ORM\Column(nullable: true)]
    private ?float $allTimeHigh = null;

    #[ORM\Column(nullable: true)]
    private ?float $allTimeLow = null;

    #[ORM\Column(nullable: true)]
    private ?int $maxSupply = null;

    #[ORM\Column(type: Types::ARRAY, nullable: true)]
    private ?array $niches = null;

    #[ORM\Column(nullable: true)]
    private ?int $currentSupply = null;

    #[ORM\Column(nullable: true)]
    private ?float $currentPrice = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $lastPriceUpdate = null;

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

    public function getSymbol(): ?string
    {
        return $this->symbol;
    }

    public function setSymbol(string $symbol): static
    {
        $this->symbol = $symbol;

        return $this;
    }

    public function getNativeBlockchain(): ?string
    {
        return $this->nativeBlockchain;
    }

    public function setNativeBlockchain(?string $nativeBlockchain): static
    {
        $this->nativeBlockchain = $nativeBlockchain;

        return $this;
    }

    public function getNetwork(): ?string
    {
        return $this->network;
    }

    public function setNetwork(?string $network): static
    {
        $this->network = $network;

        return $this;
    }

    public function getAllTimeHigh(): ?float
    {
        return $this->allTimeHigh;
    }

    public function setAllTimeHigh(?float $allTimeHigh): static
    {
        $this->allTimeHigh = $allTimeHigh;

        return $this;
    }

    public function getAllTimeLow(): ?float
    {
        return $this->allTimeLow;
    }

    public function setAllTimeLow(?float $allTimeLow): static
    {
        $this->allTimeLow = $allTimeLow;

        return $this;
    }

    public function getMaxSupply(): ?int
    {
        return $this->maxSupply;
    }

    public function setMaxSupply(?int $maxSupply): static
    {
        $this->maxSupply = $maxSupply;

        return $this;
    }

    public function getCurrentSupply(): ?int
    {
        return $this->currentSupply;
    }

    public function setCurrentSupply(?int $currentSupply): static
    {
        $this->currentSupply = $currentSupply;

        return $this;
    }

    public function getCurrentPrice(): ?float
    {
        return $this->currentPrice;
    }

    public function setCurrentPrice(?float $currentPrice): static
    {
        $this->currentPrice = $currentPrice;

        return $this;
    }

    public function getLastPriceUpdate(): ?\DateTimeInterface
    {
        return $this->lastPriceUpdate;
    }

    public function setLastPriceUpdate(?\DateTimeInterface $lastPriceUpdate): static
    {
        $this->lastPriceUpdate = $lastPriceUpdate;

        return $this;
    }

    public function getNiches(): ?array
    {
        return $this->niches;
    }

    public function setNiches(?array $niches): static
    {
        $this->niches = $niches;

        return $this;
    }
}
