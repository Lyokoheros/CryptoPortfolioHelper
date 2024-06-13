<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 15)]
    private ?string $buySymbol = null;

    #[ORM\Column(length: 15)]
    private ?string $sellSymbol = null;

    #[ORM\Column]
    private ?float $buyValue = null;

    #[ORM\Column]
    private ?float $sellValue = null;

    #[ORM\Column(length: 15)]
    private ?string $feeCurrency = null;
    
    
    #[ORM\Column]
    private ?float $fee = null;
 
    #[ORM\Column]
    private ?float $marketPrice = null;

    #[ORM\Column]
    private ?float $effectivePrice = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?TransactionBatch $transactionBatch = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getBuySymbol(): ?string
    {
        return $this->buySymbol;
    }

    public function setBuySymbol(string $buySymbol): static
    {
        $this->buySymbol = $buySymbol;

        return $this;
    }

    public function getSellSymbol(): ?string
    {
        return $this->sellSymbol;
    }

    public function setSellSymbol(string $sellSymbol): static
    {
        $this->sellSymbol = $sellSymbol;

        return $this;
    }

    public function getBuyValue(): ?float
    {
        return $this->buyValue;
    }

    public function setBuyValue(float $buyValue): static
    {
        $this->buyValue = $buyValue;

        return $this;
    }

    public function getSellValue(): ?float
    {
        return $this->sellValue;
    }

    public function setSellValue(float $sellValue): static
    {
        $this->sellValue = $sellValue;

        return $this;
    }

    public function getFee(): ?float
    {
        return $this->fee;
    }

    public function setFee(float $fee): static
    {
        $this->fee = $fee;

        return $this;
    }

    public function getFeeCurrency(): ?string
    {
        return $this->feeCurrency;
    }

    public function setFeeCurrency(string $feeCurrency): static
    {
        $this->feeCurrency = $feeCurrency;

        return $this;
    }

    public function getMarketPrice(): ?float
    {
        return $this->marketPrice;
    }

    public function setMarketPrice(float $marketPrice): static
    {
        $this->marketPrice = $marketPrice;

        return $this;
    }

    public function getEffectivePrice(): ?float
    {
        return $this->effectivePrice;
    }

    public function setEffectivePrice(float $effectivePrice): static
    {
        $this->effectivePrice = $effectivePrice;

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

    public function getTransactionBatch(): ?TransactionBatch
    {
        return $this->transactionBatch;
    }

    public function setTransactionBatch(?TransactionBatch $transactionBatch): static
    {
        $this->transactionBatch = $transactionBatch;

        return $this;
    }
}
