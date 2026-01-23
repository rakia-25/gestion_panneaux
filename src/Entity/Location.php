<?php

namespace App\Entity;

use App\Repository\LocationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: LocationRepository::class)]
#[ORM\HasLifecycleCallbacks]
class Location
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Face $face = null;

    #[ORM\ManyToOne(inversedBy: 'locations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATE_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $montantMensuel = null;

    #[ORM\Column]
    private ?bool $estPaye = false;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $raisonModificationPrix = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $createdAt = null;

    #[ORM\Column]
    private ?\DateTimeImmutable $updatedAt = null;

    #[ORM\OneToMany(mappedBy: 'location', targetEntity: Paiement::class, orphanRemoval: true, cascade: ['persist', 'remove'])]
    private Collection $paiements;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
        $this->estPaye = false;
        $this->paiements = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getFace(): ?Face
    {
        return $this->face;
    }

    public function setFace(?Face $face): static
    {
        $this->face = $face;

        return $this;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(?Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getMontantMensuel(): ?string
    {
        return $this->montantMensuel;
    }

    public function setMontantMensuel(string $montantMensuel): static
    {
        $this->montantMensuel = $montantMensuel;

        return $this;
    }

    public function isEstPaye(): ?bool
    {
        return $this->estPaye;
    }

    public function setEstPaye(bool $estPaye): static
    {
        $this->estPaye = $estPaye;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;

        return $this;
    }

    public function getRaisonModificationPrix(): ?string
    {
        return $this->raisonModificationPrix;
    }

    public function setRaisonModificationPrix(?string $raisonModificationPrix): static
    {
        $this->raisonModificationPrix = $raisonModificationPrix;

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

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeImmutable $updatedAt): static
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    /**
     * Calcule le nombre de mois de location
     */
    public function getNombreMois(): int
    {
        if (!$this->dateDebut || !$this->dateFin) {
            return 0;
        }

        $diff = $this->dateDebut->diff($this->dateFin);
        return ($diff->y * 12) + $diff->m + ($diff->d > 0 ? 1 : 0);
    }

    /**
     * Calcule le montant total de la location
     */
    public function getMontantTotal(): string
    {
        $nombreMois = $this->getNombreMois();
        return bcmul($this->montantMensuel ?? '0', (string)$nombreMois, 2);
    }

    /**
     * Vérifie si la location est active (en cours)
     */
    public function isActive(): bool
    {
        $now = new \DateTime();
        return $this->dateDebut <= $now && $this->dateFin >= $now;
    }

    /**
     * Vérifie si la location est terminée
     */
    public function isTerminee(): bool
    {
        $now = new \DateTime();
        return $this->dateFin < $now;
    }

    /**
     * Vérifie si la location est à venir
     */
    public function isAVenir(): bool
    {
        $now = new \DateTime();
        return $this->dateDebut > $now;
    }

    /**
     * @return Collection<int, Paiement>
     */
    public function getPaiements(): Collection
    {
        return $this->paiements;
    }

    public function addPaiement(Paiement $paiement): static
    {
        if (!$this->paiements->contains($paiement)) {
            $this->paiements->add($paiement);
            $paiement->setLocation($this);
        }

        return $this;
    }

    public function removePaiement(Paiement $paiement): static
    {
        if ($this->paiements->removeElement($paiement)) {
            // set the owning side to null (unless already changed)
            if ($paiement->getLocation() === $this) {
                $paiement->setLocation(null);
            }
        }

        return $this;
    }

    /**
     * Calcule le montant total payé pour cette location
     */
    public function getMontantTotalPaye(): string
    {
        $total = '0.00';
        foreach ($this->paiements as $paiement) {
            $total = bcadd($total, $paiement->getMontant() ?? '0', 2);
        }
        return $total;
    }

    /**
     * Calcule le montant restant à payer
     */
    public function getMontantRestant(): string
    {
        $montantTotal = $this->getMontantTotal();
        $montantPaye = $this->getMontantTotalPaye();
        return bcsub($montantTotal, $montantPaye, 2);
    }

    /**
     * Retourne le statut de paiement : 'paye', 'partiellement_paye', 'impaye'
     */
    public function getStatutPaiement(): string
    {
        $montantTotal = floatval($this->getMontantTotal());
        $montantPaye = floatval($this->getMontantTotalPaye());

        if ($montantTotal <= 0) {
            return 'paye'; // Si pas de montant, considéré comme payé
        }

        $difference = abs($montantTotal - $montantPaye);

        if ($difference < 0.01) {
            return 'paye';
        } elseif ($montantPaye > 0) {
            return 'partiellement_paye';
        } else {
            return 'impaye';
        }
    }

    /**
     * Vérifie si la location est complètement payée
     */
    public function isCompletementPaye(): bool
    {
        return $this->getStatutPaiement() === 'paye';
    }

    /**
     * Vérifie si la location est partiellement payée
     */
    public function isPartiellementPaye(): bool
    {
        return $this->getStatutPaiement() === 'partiellement_paye';
    }

    /**
     * Vérifie si la location est impayée
     */
    public function isImpaye(): bool
    {
        return $this->getStatutPaiement() === 'impaye';
    }
}
