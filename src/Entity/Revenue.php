<?php

namespace App\Entity;

use App\Repository\RevenueRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: RevenueRepository::class)]
class Revenue
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $amountHt = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private ?string $amountTtc = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTimeInterface $date = null;

    #[ORM\ManyToOne(inversedBy: 'revenues')]
    private ?Employee $employee = null;

    #[ORM\ManyToOne(inversedBy: 'revenues')]
    private ?Package $package = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getAmountHt(): ?string
    {
        return $this->amountHt;
    }

    public function setAmountHt(string $amountHt): static
    {
        $this->amountHt = $amountHt;

        return $this;
    }

    public function getAmountTtc(): ?string
    {
        return $this->amountTtc;
    }

    public function setAmountTtc(string $amountTtc): static
    {
        $this->amountTtc = $amountTtc;

        return $this;
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

    public function getEmployee(): ?Employee
    {
        return $this->employee;
    }

    public function setEmployee(?Employee $employee): static
    {
        $this->employee = $employee;

        return $this;
    }

    public function getPackage(): ?Package
    {
        return $this->package;
    }

    public function setPackage(?Package $package): static
    {
        $this->package = $package;

        return $this;
    }
}
