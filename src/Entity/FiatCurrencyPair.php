<?php

namespace App\Entity;

use App\Repository\FiatCurrencyPairRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FiatCurrencyPairRepository::class)]
class FiatCurrencyPair
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 127)]
    private ?string $name = null;

    /**
     * @var Collection<int, DailyExchangeRate>
     */
    #[ORM\OneToMany(targetEntity: DailyExchangeRate::class, mappedBy: 'pairGlobalData')]
    private Collection $ExchengedCurrencies;

    #[ORM\Column(nullable: true)]
    private ?float $AverageRatio = null;

    #[ORM\Column(nullable: true)]
    private ?float $allTimeHigh = null;

    #[ORM\Column(nullable: true)]
    private ?float $AllTimeLow = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $lastUpdate = null;

    public function __construct()
    {
        $this->ExchengedCurrencies = new ArrayCollection();
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

    /**
     * @return Collection<int, DailyExchangeRate>
     */
    public function getExchengedCurrencies(): Collection
    {
        return $this->ExchengedCurrencies;
    }

    public function addExchengedCurrency(DailyExchangeRate $exchengedCurrency): static
    {
        if (!$this->ExchengedCurrencies->contains($exchengedCurrency)) {
            $this->ExchengedCurrencies->add($exchengedCurrency);
            $exchengedCurrency->setPairGlobalData($this);
        }

        return $this;
    }

    public function removeExchengedCurrency(DailyExchangeRate $exchengedCurrency): static
    {
        if ($this->ExchengedCurrencies->removeElement($exchengedCurrency)) {
            // set the owning side to null (unless already changed)
            if ($exchengedCurrency->getPairGlobalData() === $this) {
                $exchengedCurrency->setPairGlobalData(null);
            }
        }

        return $this;
    }

    public function getAverageRatio(): ?float
    {
        return $this->AverageRatio;
    }

    public function setAverageRatio(float $AverageRatio): static
    {
        $this->AverageRatio = $AverageRatio;

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
        return $this->AllTimeLow;
    }

    public function setAllTimeLow(?float $AllTimeLow): static
    {
        $this->AllTimeLow = $AllTimeLow;

        return $this;
    }

    public function getLastUpdate(): ?\DateTimeInterface
    {
        return $this->lastUpdate;
    }

    public function setLastUpdate(\DateTimeInterface $lastUpdate): static
    {
        $this->lastUpdate = $lastUpdate;

        return $this;
    }
}
