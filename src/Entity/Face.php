<?php

namespace App\Entity;

use App\Repository\FaceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: FaceRepository::class)]
class Face
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 1)]
    private ?string $lettre = null; // 'A' ou 'B'

    #[ORM\ManyToOne(inversedBy: 'faces')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Panneau $panneau = null;

    #[ORM\OneToMany(mappedBy: 'face', targetEntity: Location::class, orphanRemoval: true)]
    private Collection $locations;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    public function __construct()
    {
        $this->locations = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getLettre(): ?string
    {
        return $this->lettre;
    }

    public function setLettre(string $lettre): static
    {
        $this->lettre = $lettre;

        return $this;
    }

    public function getPanneau(): ?Panneau
    {
        return $this->panneau;
    }

    public function setPanneau(?Panneau $panneau): static
    {
        $this->panneau = $panneau;

        return $this;
    }

    /**
     * @return Collection<int, Location>
     */
    public function getLocations(): Collection
    {
        return $this->locations;
    }

    public function addLocation(Location $location): static
    {
        if (!$this->locations->contains($location)) {
            $this->locations->add($location);
            $location->setFace($this);
        }

        return $this;
    }

    public function removeLocation(Location $location): static
    {
        if ($this->locations->removeElement($location)) {
            // set the owning side to null (unless already changed)
            if ($location->getFace() === $this) {
                $location->setFace(null);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): static
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * Vérifie si la face est disponible pour une période donnée
     */
    public function isDisponible(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin): bool
    {
        $now = new \DateTime();
        
        foreach ($this->locations as $location) {
            // Vérifier si la location est active (en cours ou future)
            if ($location->getDateFin() >= $now) {
                // Vérifier s'il y a un chevauchement de dates
                if (!($dateFin < $location->getDateDebut() || $dateDebut > $location->getDateFin())) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Retourne la location active actuelle (si elle existe)
     */
    public function getLocationActive(): ?Location
    {
        $now = new \DateTime();
        
        foreach ($this->locations as $location) {
            if ($location->getDateDebut() <= $now && $location->getDateFin() >= $now) {
                return $location;
            }
        }
        
        return null;
    }

    /**
     * Retourne le nom complet de la face (ex: PAN-001-A)
     */
    public function getNomComplet(): string
    {
        return $this->panneau?->getReference() . '-' . $this->lettre;
    }
}
