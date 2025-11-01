<?php

namespace App\Command;

use App\Entity\Employee;
use App\Entity\Statistics;
use App\Repository\ChargeRepository;
use App\Repository\EmployeeRepository;
use App\Repository\RevenueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:archive-daily-statistics',
    description: 'Archive daily statistics for all employees',
)]
class ArchiveDailyStatisticsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmployeeRepository $employeeRepository,
        private RevenueRepository $revenueRepository,
        private ChargeRepository $chargeRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDescription('Archives daily statistics for all employees');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $yesterday = new \DateTime('yesterday');
        $today = new \DateTime('today');

        $employees = $this->employeeRepository->findAll();

        foreach ($employees as $employee) {
            // Calculate daily revenue HT and TTC
            $dailyRevenues = $this->revenueRepository->createQueryBuilder('r')
                ->where('r.employee = :employee')
                ->andWhere('r.date >= :start AND r.date < :end')
                ->setParameter('employee', $employee)
                ->setParameter('start', $yesterday)
                ->setParameter('end', $today)
                ->getQuery()
                ->getResult();

            $revenueHt = 0;
            $revenueTtc = 0;
            $clientsCount = count($dailyRevenues);

            foreach ($dailyRevenues as $revenue) {
                $revenueHt += (float) $revenue->getAmountHt();
                $revenueTtc += (float) $revenue->getAmountTtc();
            }

            // Calculate daily charges
            $dailyCharges = $this->chargeRepository->createQueryBuilder('c')
                ->where('c.date >= :start AND c.date < :end')
                ->setParameter('start', $yesterday)
                ->setParameter('end', $today)
                ->getQuery()
                ->getResult();

            $charges = 0;
            foreach ($dailyCharges as $charge) {
                $charges += (float) $charge->getAmount();
            }

            // Calculate commission
            $commissionPercentage = (float) ($employee->getCommissionPercentage() ?? 0);
            $commission = $revenueHt * ($commissionPercentage / 100);

            // Calculate profit
            $profit = $revenueHt - $charges - $commission;

            // Create statistics entry
            $statistics = new Statistics();
            $statistics->setEmployee($employee);
            $statistics->setPeriod('daily');
            $statistics->setDate($yesterday);
            $statistics->setRevenueHt((string) $revenueHt);
            $statistics->setRevenueTtc((string) $revenueTtc);
            $statistics->setCharges((string) $charges);
            $statistics->setCommission((string) $commission);
            $statistics->setProfit((string) $profit);
            $statistics->setClientsCount($clientsCount);

            $this->entityManager->persist($statistics);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Archived daily statistics for %d employees', count($employees)));

        return Command::SUCCESS;
    }
}
