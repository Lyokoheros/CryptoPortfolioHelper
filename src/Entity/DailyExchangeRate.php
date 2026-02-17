<?php

namespace App\Entity;

use App\Repository\DailyExchangeRateRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
 
#[ORM\Entity(repositoryClass: DailyExchangeRateRepository::class)]
#[ORM\Table(name: 'daily_exchange_rates')]
class DailyExchangeRate
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['exchangeRate'])]
    private ?int $id = null;
    

    #[ORM\Column]
    #[Groups(['exchangeRate'])]
    private ?float $exchangeRate = null;
    //quantity of baseCurrency needed to buy 1 unit of exchangedCurrency

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['exchangeRate'])]
    private ?Currency $baseCurrency = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['exchangeRate'])]
    private ?Currency $exchangedCurrency = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['exchangeRate'])]
    private ?\DateTimeInterface $date = null;

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
        return $this->exchangeRate;
    }

    public function setExchangeRate(float $exchangeRate): static
    {
        $this->exchangeRate = $exchangeRate;

        return $this;
    }

    public function getDate(): ?\DateTimeInterface
    {
        return $this->date;
    }

    public function setDate(\DateTimeInterface $date): static
    {
        $this->date = $date;

        return $this;
    }
}
