<?php

namespace App\Entity;

use App\Repository\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\Ignore;

#[ORM\Entity(repositoryClass: PortfolioRepository::class)]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['userProfile', 'portfolioList', 'portfolioView', 'transactionBatchList'])]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['default' => 'Main Portfolio'])]
    #[Groups(['userProfile', 'portfolioList', 'portfolioView', 'transactionBatchList'])]
    private string $name = 'Main Portfolio';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    #[Groups(['userProfile', 'portfolioList', 'portfolioView'])]
    private \DateTimeInterface $startingDate;

    #[ORM\Column]
    #[Groups(['portfolioList', 'portfolioView'])]
    private ?int $batchSize = null;

    #[ORM\Column(options: ['default' => 'regular'])]
    #[Groups(['portfolioList', 'portfolioView'])]
    private string $defualtBatchType = 'regular';

    #[ORM\ManyToOne(inversedBy: 'portfolios')]
    #[ORM\JoinColumn(nullable: false)]
    #[Ignore]
    private ?User $user = null;

    /**
     * @var Collection<int, TransactionBatch>
     */
    #[ORM\OneToMany(
        targetEntity: TransactionBatch::class,
        mappedBy: 'portfolio',
        fetch: 'LAZY'
    )]
    private Collection $transactionBatches;

    #[ORM\Column]
    #[Groups(['userProfile', 'portfolioList', 'portfolioView'])]
    private bool $isDefault = false;

    
    #[ORM\Column(options: ['default' => 0.0])]
    #[Groups(['userProfile', 'portfolioList', 'portfolioView'])]
    private float $totalPortfolioValue = 0.0;

    /**
     * @var Collection<int, Currency>
     */
    #[ORM\ManyToMany(targetEntity: Currency::class)]
    #[ORM\JoinTable(name: "portfolio_bought_assets")]
    #[Groups(['portfolioView'])]
    private Collection $boughtAssets;

    /**
     * @var Collection<int, Currency>
     */
    #[ORM\ManyToMany(targetEntity: Currency::class)]
    #[ORM\JoinTable(name: "portfolio_sold_assets")]
    #[Groups(['portfolioView'])]
    private Collection $soldAssets;

    public function __construct()
    {
        $this->transactionBatches = new ArrayCollection();
        $this->startingDate = new \DateTime();
        $this->boughtAssets = new ArrayCollection();
        $this->soldAssets = new ArrayCollection();
    }

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

    public function getStartingDate(): ?\DateTimeInterface
    {
        return $this->startingDate;
    }

    public function setStartingDate(\DateTimeInterface $startingDate): static
    {
        $this->startingDate = $startingDate;

        return $this;
    }

    public function getBatchSize(): ?int
    {
        return $this->batchSize;
    }

    public function setBatchSize(int $batchSize): static
    {
        $this->batchSize = $batchSize ?? 1;

        return $this;
    }

    public function getDefualtBatchType(): ?string
    {
        return $this->defualtBatchType;
    }

    public function setDefualtBatchType(string $batchType): static
    {
        $this->defualtBatchType = $batchType;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;

        return $this;
    }

    /**
     * @return Collection<int, TransactionBatch>
     */
    public function getTransactionBatches(): Collection
    {
        return $this->transactionBatches;
    }

    public function addTransactionBatch(TransactionBatch $transactionBatch): static
    {
        if (!$this->transactionBatches->contains($transactionBatch)) {
            $this->transactionBatches->add($transactionBatch);
            $transactionBatch->setPortfolio($this);
        }

        return $this;
    }

    public function removeTransactionBatch(TransactionBatch $transactionBatch): static
    {
        if ($this->transactionBatches->removeElement($transactionBatch)) {
            // set the owning side to null (unless already changed)
            if ($transactionBatch->getPortfolio() === $this) {
                $transactionBatch->setPortfolio(null);
            }
        }

        return $this;
    }

    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    public function setDefault(bool $isDefault): static
    {
        $this->isDefault = $isDefault;

        return $this;
    }

    public function getTotalPortfolioValue(): ?float
    {
        return $this->totalPortfolioValue;
    }

    public function setTotalPortfolioValue(float $totalPortfolioValue): static
    {
        $this->totalPortfolioValue = $totalPortfolioValue;

        return $this;
    }

    /** 
     * @return array<string, Currency> 
     */
    public function getBoughtAssets(): array
    {
        $indexed = [];
        foreach ($this->boughtAssets as $currency)
        {
            $indexed[$currency->getSymbol()] = $currency;
        }
        return $indexed;
    }

    public function addBoughtAsset(Currency $boughtAsset): static
    {
        if (!$this->boughtAssets->contains($boughtAsset)) {
            $this->boughtAssets->add($boughtAsset);
        }

        return $this;
    }

    public function removeBoughtAsset(Currency $boughtAsset): static
    {
        $this->boughtAssets->removeElement($boughtAsset);

        return $this;
    }

    /** 
     * @return array<string, Currency> 
     */
    public function getSoldAssets():  array
    {
        $indexed = [];
        foreach ($this->soldAssets as $currency)
        {
            $indexed[$currency->getSymbol()] = $currency;
        }
        return $indexed;
    }

    public function addSoldAsset(Currency $soldAsset): static
    {
        if (!$this->soldAssets->contains($soldAsset)) {
            $this->soldAssets->add($soldAsset);
        }

        return $this;
    }

    public function removeSoldAsset(Currency $soldAsset): static
    {
        $this->soldAssets->removeElement($soldAsset);

        return $this;
    }
}
