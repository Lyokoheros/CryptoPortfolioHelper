<?php

namespace App\Entity;

use App\Repository\TransactionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Doctrine\ORM\Events;

#[ORM\Entity(repositoryClass: TransactionRepository::class)]
#[ORM\Table(name: 'transactions')]
#[ORM\HasLifecycleCallbacks]
class Transaction
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['portfolioView', 'transactionBatchList', 'transactionList', 'transactionDetails'])]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'transactions')]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['transactionList', 'transactionDetails'])]
    private ?TransactionBatch $transactionBatch = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['transactionDetails'])]
    private ?Exchange $Exchange = null;
    
    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['portfolioView', 'transactionBatchList', 'transactionList', 'transactionDetails'])]

    private ?Currency $boughtCurrency = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    #[Groups(['portfolioView', 'transactionBatchList', 'transactionList', 'transactionDetails'])]
    private ?Currency $soldCurrency = null;
    
    #[ORM\ManyToOne]
    #[Groups(['transactionDetails'])]
    private ?Currency $feeCurrency = null;

    #[ORM\Column]
    #[Groups(['transactionBatchList', 'transactionList', 'transactionDetails'])]
    private ?float $buyValue = null;

    #[ORM\Column]
    #[Groups(['transactionBatchList', 'transactionList', 'transactionDetails'])]
    private ?float $sellValue = null;
   
    #[ORM\Column]
    #[Groups(['transactionDetails'])]
    private ?float $fee = null;
 
    #[ORM\Column]
    #[Groups(['transactionDetails'])]
    private ?float $marketPrice = null;

    #[ORM\Column(nullable: true)]
    #[Groups(['transactionDetails'])]
    private ?float $effectivePrice = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['transactionBatchList', 'transactionList', 'transactionDetails'])]
    private ?\DateTimeInterface $date = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getExchange(): ?Exchange
    {
        return $this->Exchange;
    }

    public function setExchange(?Exchange $Exchange): static
    {
        $this->Exchange = $Exchange;

        return $this;
    }

    public function getBoughtCurrency(): ?Currency
    {
        return $this->boughtCurrency;
    }

    public function setBoughtCurrency(?Currency $boughtCurrency): static
    {
        $this->boughtCurrency = $boughtCurrency;

        return $this;
    }

    public function getSoldCurrency(): ?Currency
    {
        return $this->soldCurrency;
    }

    public function setSoldCurrency(?Currency $soldCurrency): static
    {
        $this->soldCurrency = $soldCurrency;

        return $this;
    }

    public function getFeeCurrency(): ?Currency
    {
        return $this->feeCurrency;
    }

    public function setFeeCurrency(?Currency $feeCurrency): static
    {
        $this->feeCurrency = $feeCurrency;

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

    public function calculateEffectivePrice(): float
    {
        $effectivePrice = 0.0;

        if($this->getBuyValue() <= 0 || $this->getSellValue() <= 0)
        {
            $this->setEffectivePrice($effectivePrice);
            return $effectivePrice;
        }

        if ($this->getFeeCurrency() === $this->getSoldCurrency())
        {            
            $effectivePrice = $this->getBuyValue()/
                ($this->getSellValue() + $this->getFee());
        }
        elseif (($this->getFeeCurrency() === $this->getBoughtCurrency()))
        {
            $effectivePrice = ($this->getBuyValue()-$this->getFee())/
                $this->getSellValue();
        }
        else
        {
            $effectivePrice = $this->getBuyValue()/$this->getSellValue();
        }
            
        $this->setEffectivePrice($effectivePrice);
        return $effectivePrice;
    }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function onPrePersistOrUpdate(): void
    {
        $this->calculateEffectivePrice();
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
