<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $userName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    private ?string $country = null;

    #[ORM\Column(length: 15)]
    private ?string $nativeCurrency = null;

    #[ORM\Column(length: 15)]
    private ?string $displayCurrency = null;

    /**
     * @var Collection<int, Portfolio>
     */
    #[ORM\OneToMany(targetEntity: Portfolio::class, mappedBy: 'User')]
    private Collection $portfolios;

    #[ORM\Column(length: 127)]
    private ?string $eMail = null;

    public function __construct()
    {
        $this->portfolios = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUserName(): ?string
    {
        return $this->userName;
    }

    public function setUserName(string $userName): static
    {
        $this->userName = $userName;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(?string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getSurname(): ?string
    {
        return $this->surname;
    }

    public function setSurname(?string $surname): static
    {
        $this->surname = $surname;

        return $this;
    }

    public function getCountry(): ?string
    {
        return $this->country;
    }

    public function setCountry(string $Country): static
    {
        $this->country = $Country;

        return $this;
    }

    public function getNativeCurrency(): ?string
    {
        return $this->nativeCurrency;
    }

    public function setNativeCurrency(string $nativeCurrency): static
    {
        $this->nativeCurrency = $nativeCurrency;

        return $this;
    }

    public function getDisplayCurrency(): ?string
    {
        return $this->displayCurrency;
    }

    public function setDisplayCurrency(string $displayCurrency): static
    {
        $this->displayCurrency = $displayCurrency;

        return $this;
    }

    /**
     * @return Collection<int, Portfolio>
     */
    public function getPortfolios(): Collection
    {
        return $this->portfolios;
    }

    public function addPortfolio(Portfolio $portfolio): static
    {
        if (!$this->portfolios->contains($portfolio)) {
            $this->portfolios->add($portfolio);
            $portfolio->setUser($this);
        }

        return $this;
    }

    public function removePortfolio(Portfolio $portfolio): static
    {
        if ($this->portfolios->removeElement($portfolio)) {
            // set the owning side to null (unless already changed)
            if ($portfolio->getUser() === $this) {
                $portfolio->setUser(null);
            }
        }

        return $this;
    }

    public function getEMail(): ?string
    {
        return $this->eMail;
    }

    public function setEMail(string $eMail): static
    {
        $this->eMail = $eMail;

        return $this;
    }
}
