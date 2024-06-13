<?php

namespace App\Entity;

use App\Repository\DailyExchangeRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DailyExchangeRateRepository::class)]
class DailyExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 15)]
    private ?string $BaseCurrency = null;

    #[ORM\Column(length: 15)]
    private ?string $ExchangedCurrency = null;

    #[ORM\Column]
    private ?float $ExchangeRate = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $Date = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBaseCurrency(): ?string
    {
        return $this->BaseCurrency;
    }

    public function setBaseCurrency(string $BaseCurrency): static
    {
        $this->BaseCurrency = $BaseCurrency;

        return $this;
    }

    public function getExchangedCurrency(): ?string
    {
        return $this->ExchangedCurrency;
    }

    public function setExchangedCurrency(string $ExchangedCurrency): static
    {
        $this->ExchangedCurrency = $ExchangedCurrency;

        return $this;
    }

    public function getExchangeRate(): ?float
    {
        return $this->ExchangeRate;
    }

    public function setExchangeRate(float $ExchangeRate): static
    {
        $this->ExchangeRate = $ExchangeRate;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->Date;
    }

    public function setDate(\DateTimeInterface $Date): static
    {
        $this->Date = $Date;

        return $this;
    }
}
