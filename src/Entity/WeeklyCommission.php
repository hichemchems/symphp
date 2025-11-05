<?php

namespace App\Entity;

use App\Repository\WeeklyCommissionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: WeeklyCommissionRepository::class)]
class WeeklyCommission
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalCommission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $totalRevenueHt = null;

    #[ORM\Column]
    private ?int $clientsCount = null;

    #[ORM\Column]
    private ?\DateTime $weekStart = null;

    #[ORM\Column]
    private ?\DateTime $weekEnd = null;

    #[ORM\Column(type: 'boolean')]
    private ?bool $validated = false;

    #[ORM\Column(type: 'boolean')]
    private ?bool $paid = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $validatedAt = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $paidAt = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getTotalCommission(): ?string
    {
        return $this->totalCommission;
    }

    public function setTotalCommission(string $totalCommission): static
    {
        $this->totalCommission = $totalCommission;

        return $this;
    }

    public function getTotalRevenueHt(): ?string
    {
        return $this->totalRevenueHt;
    }

    public function setTotalRevenueHt(string $totalRevenueHt): static
    {
        $this->totalRevenueHt = $totalRevenueHt;

        return $this;
    }

    public function getClientsCount(): ?int
    {
        return $this->clientsCount;
    }

    public function setClientsCount(int $clientsCount): static
    {
        $this->clientsCount = $clientsCount;

        return $this;
    }

    public function getWeekStart(): ?\DateTime
    {
        return $this->weekStart;
    }

    public function setWeekStart(\DateTime $weekStart): static
    {
        $this->weekStart = $weekStart;

        return $this;
    }

    public function getWeekEnd(): ?\DateTime
    {
        return $this->weekEnd;
    }

    public function setWeekEnd(\DateTime $weekEnd): static
    {
        $this->weekEnd = $weekEnd;

        return $this;
    }

    public function isValidated(): ?bool
    {
        return $this->validated;
    }

    public function setValidated(bool $validated): static
    {
        $this->validated = $validated;

        return $this;
    }

    public function isPaid(): ?bool
    {
        return $this->paid;
    }

    public function setPaid(bool $paid): static
    {
        $this->paid = $paid;

        return $this;
    }

    public function getValidatedAt(): ?\DateTime
    {
        return $this->validatedAt;
    }

    public function setValidatedAt(?\DateTime $validatedAt): static
    {
        $this->validatedAt = $validatedAt;

        return $this;
    }

    public function getPaidAt(): ?\DateTime
    {
        return $this->paidAt;
    }

    public function setPaidAt(?\DateTime $paidAt): static
    {
        $this->paidAt = $paidAt;

        return $this;
    }
}
