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

    #[ORM\Column(length: 255)]
    private ?string $Nazwa = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $StartingDate = null;

    #[ORM\Column]
    private ?int $BatchSize = null;

    #[ORM\ManyToOne(inversedBy: 'portfolios')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $User = null;

    /**
     * @var Collection<int, TransactionBatch>
     */
    #[ORM\OneToMany(targetEntity: TransactionBatch::class, mappedBy: 'portfolio')]
    private Collection $TransactionBatches;

    #[ORM\Column]
    private bool $isDefault = false;

    public function __construct()
    {
        $this->TransactionBatches = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNazwa(): ?string
    {
        return $this->Nazwa;
    }

    public function setNazwa(string $Nazwa): static
    {
        $this->Nazwa = $Nazwa;

        return $this;
    }

    public function getStartingDate(): ?\DateTimeInterface
    {
        return $this->StartingDate;
    }

    public function setStartingDate(\DateTimeInterface $StartingDate): static
    {
        $this->StartingDate = $StartingDate;

        return $this;
    }

    public function getBatchSize(): ?int
    {
        return $this->BatchSize;
    }

    public function setBatchSize(int $BatchSize): static
    {
        $this->BatchSize = $BatchSize;

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->User;
    }

    public function setUser(?User $User): static
    {
        $this->User = $User;

        return $this;
    }

    /**
     * @return Collection<int, TransactionBatch>
     */
    public function getTransactionBatches(): Collection
    {
        return $this->TransactionBatches;
    }

    public function addTransactionBatch(TransactionBatch $transactionBatch): static
    {
        if (!$this->TransactionBatches->contains($transactionBatch)) {
            $this->TransactionBatches->add($transactionBatch);
            $transactionBatch->setPortfolio($this);
        }

        return $this;
    }

    public function removeTransactionBatch(TransactionBatch $transactionBatch): static
    {
        if ($this->TransactionBatches->removeElement($transactionBatch)) {
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
