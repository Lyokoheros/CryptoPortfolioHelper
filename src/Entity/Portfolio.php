<?php

namespace App\Entity;

use App\Repository\PortfolioRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: PortfolioRepository::class)]
class Portfolio
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255, options: ['default' => 'Main Portfolio'])]
    private string $name = 'Main Portfolio';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $startingDate = null;

    #[ORM\Column]
    private ?int $batchSize = null;

    #[ORM\ManyToOne(inversedBy: 'portfolios')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    /**
     * @var Collection<int, TransactionBatch>
     */
    #[ORM\OneToMany(targetEntity: TransactionBatch::class, mappedBy: 'portfolio')]
    private Collection $transactionBatches;

    #[ORM\Column]
    private bool $isDefault = false;

    public function __construct()
    {
        $this->transactionBatches = new ArrayCollection();
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
}
