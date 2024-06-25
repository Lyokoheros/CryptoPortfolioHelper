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

    

    #[ORM\Column]
    private ?float $ExchangeRate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $baseCurrency = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Currency $exchangedCurrency = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $Date = null;

    #[ORM\ManyToOne(inversedBy: 'ExchengedCurrencies')]
    #[ORM\JoinColumn(nullable: false)]
    private ?FiatCurrencyPair $pairGlobalData = null;

    public function getId(): ?int
    {
        return $this->id;
    }
    
    public function getBaseCurrency(): ?Currency
    {
        return $this->baseCurrency;
    }

    public function setBaseCurrency(?Currency $baseCurrency): static
    {
        $this->baseCurrency = $baseCurrency;

        return $this;
    }

    public function getExchangedCurrency(): ?Currency
    {
        return $this->exchangedCurrency;
    }

    public function setExchangedCurrency(?Currency $exchangedCurrency): static
    {
        $this->exchangedCurrency = $exchangedCurrency;

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

    public function getPairGlobalData(): ?FiatCurrencyPair
    {
        return $this->pairGlobalData;
    }

    public function setPairGlobalData(?FiatCurrencyPair $pairGlobalData): static
    {
        $this->pairGlobalData = $pairGlobalData;

        return $this;
    }
}
