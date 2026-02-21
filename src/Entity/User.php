<?php

namespace App\Entity;

use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    #[Groups(['userProfile'])]
    private int $id;

    #[ORM\Column(length: 255)]
    #[Groups(['userProfile'])]
    private ?string $userName = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['userProfile'])]
    private ?string $name = null;

    #[ORM\Column(length: 255, nullable: true)]
    #[Groups(['userProfile'])]
    private ?string $surname = null;

    #[ORM\Column(length: 255)]
    #[Groups(['userProfile'])]
    private ?string $country = null;

    /**
     * @var Collection<int, Portfolio>
     */
    #[ORM\OneToMany(targetEntity: Portfolio::class, mappedBy: 'user')]
    #[Groups(['userProfile'])]
    #[MaxDepth(1)]
    private Collection $portfolios;

    #[ORM\Column(length: 127)]
    #[Groups(['userProfile'])]
    private ?string $eMail = null;

    #[ORM\ManyToOne]
    #[Groups(['userProfile'])]
    #[MaxDepth(1)]
    private ?Currency $nativeCurrency = null;

    #[ORM\ManyToOne]
    #[Groups(['userProfile'])]
    #[MaxDepth(1)]
    private ?Currency $displayCurrency = null;

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
        $this->userName = $userName ?? ($this->getName() + $this->getSurname());

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

    public function getNativeCurrency(): ?Currency
    {
        return $this->nativeCurrency;
    }

    public function setNativeCurrency(?Currency $nativeCurrency): static
    {
        $this->nativeCurrency = $nativeCurrency;

        return $this;
    }

    public function getDisplayCurrency(): ?Currency
    {
        return $this->displayCurrency;
    }

    public function setDisplayCurrency(?Currency $displayCurrency): static
    {
        $this->displayCurrency = $displayCurrency;

        return $this;
    }
}
