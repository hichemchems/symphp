<?php

namespace App\Entity;

use App\Repository\StatisticsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: StatisticsRepository::class)]
class Statistics
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?Employee $employee = null;

    #[ORM\Column(length: 20)]
    private ?string $period = null;

    #[ORM\Column]
    private ?\DateTime $date = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $revenueHt = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $revenueTtc = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $charges = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $commission = null;

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private ?string $profit = null;

    #[ORM\Column]
    private ?int $clientsCount = null;

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

    public function getPeriod(): ?string
    {
        return $this->period;
    }

    public function setPeriod(string $period): static
    {
        $this->period = $period;

        return $this;
    }

    public function getDate(): ?\DateTime
    {
        return $this->date;
    }

    public function setDate(\DateTime $date): static
    {
        $this->date = $date;

        return $this;
    }

    public function getRevenueHt(): ?string
    {
        return $this->revenueHt;
    }

    public function setRevenueHt(string $revenueHt): static
    {
        $this->revenueHt = $revenueHt;

        return $this;
    }

    public function getRevenueTtc(): ?string
    {
        return $this->revenueTtc;
    }

    public function setRevenueTtc(string $revenueTtc): static
    {
        $this->revenueTtc = $revenueTtc;

        return $this;
    }

    public function getCharges(): ?string
    {
        return $this->charges;
    }

    public function setCharges(string $charges): static
    {
        $this->charges = $charges;

        return $this;
    }

    public function getCommission(): ?string
    {
        return $this->commission;
    }

    public function setCommission(string $commission): static
    {
        $this->commission = $commission;

        return $this;
    }

    public function getProfit(): ?string
    {
        return $this->profit;
    }

    public function setProfit(string $profit): static
    {
        $this->profit = $profit;

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
}
