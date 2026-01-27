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
     * 
     * @param \DateTimeInterface $dateDebut Date de début de la période
     * @param \DateTimeInterface $dateFin Date de fin de la période
     * @param Location|null $excludeLocation Location à exclure de la vérification (utile lors de l'édition)
     * @return bool True si disponible, false sinon
     */
    public function isDisponible(\DateTimeInterface $dateDebut, \DateTimeInterface $dateFin, ?Location $excludeLocation = null): bool
    {
        // Normaliser les dates pour la comparaison (ignorer l'heure)
        $dateDebutNormalisee = new \DateTime($dateDebut->format('Y-m-d'));
        $dateFinNormalisee = new \DateTime($dateFin->format('Y-m-d'));
        
        foreach ($this->locations as $location) {
            // Exclure les locations annulées
            if ($location->isAnnulee()) {
                continue;
            }
            
            // Exclure la location spécifiée (utile lors de l'édition)
            if ($excludeLocation && $location->getId() === $excludeLocation->getId()) {
                continue;
            }
            
            // Normaliser les dates de la location
            $locDateDebut = new \DateTime($location->getDateDebut()->format('Y-m-d'));
            $locDateFin = new \DateTime($location->getDateFin()->format('Y-m-d'));
            
            // Vérifier s'il y a un chevauchement de dates
            // Chevauchement si : dateDebut <= locDateFin ET dateFin >= locDateDebut
            if ($dateDebutNormalisee <= $locDateFin && $dateFinNormalisee >= $locDateDebut) {
                return false;
            }
        }
        
        return true;
    }

    /**
     * Retourne la location active actuelle (si elle existe, exclut les annulées)
     */
    public function getLocationActive(): ?Location
    {
        $now = new \DateTime();
        
        foreach ($this->locations as $location) {
            if (!$location->isAnnulee() && $location->getDateDebut() <= $now && $location->getDateFin() >= $now) {
                return $location;
            }
        }
        
        return null;
    }

    /**
     * Retourne toutes les locations actives ou futures (non terminées, exclut les annulées)
     */
    public function getLocationsActivesOuFutures(): array
    {
        $now = new \DateTime();
        $locations = [];
        
        foreach ($this->locations as $location) {
            if (!$location->isAnnulee() && $location->getDateFin() >= $now) {
                $locations[] = $location;
            }
        }
        
        // Trier par date de début
        usort($locations, function($a, $b) {
            return $a->getDateDebut() <=> $b->getDateDebut();
        });
        
        return $locations;
    }

    /**
     * Vérifie si la face a des locations futures (non terminées, exclut les annulées)
     */
    public function hasLocationsFutures(): bool
    {
        $now = new \DateTime();
        
        foreach ($this->locations as $location) {
            if (!$location->isAnnulee() && $location->getDateFin() >= $now) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Retourne le nom complet de la face (ex: PAN-001-A)
     */
    public function getNomComplet(): string
    {
        return $this->panneau?->getReference() . '-' . $this->lettre;
    }
}
