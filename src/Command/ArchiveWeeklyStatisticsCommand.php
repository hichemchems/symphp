<?php

namespace App\Command;

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
    name: 'app:archive-weekly-statistics',
    description: 'Archive weekly statistics for all employees',
)]
class ArchiveWeeklyStatisticsCommand extends Command
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
        $this->setDescription('Archives weekly statistics for all employees');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get last week (Monday to Sunday)
        $now = new \DateTime();
        $lastMonday = new \DateTime('last monday');
        $lastSunday = new \DateTime('last sunday');

        $employees = $this->employeeRepository->findAll();

        foreach ($employees as $employee) {
            // Calculate weekly revenue HT and TTC
            $weeklyRevenues = $this->revenueRepository->createQueryBuilder('r')
                ->where('r.employee = :employee')
                ->andWhere('r.date >= :start AND r.date <= :end')
                ->setParameter('employee', $employee)
                ->setParameter('start', $lastMonday)
                ->setParameter('end', $lastSunday)
                ->getQuery()
                ->getResult();

            $revenueHt = 0;
            $revenueTtc = 0;
            $clientsCount = count($weeklyRevenues);

            foreach ($weeklyRevenues as $revenue) {
                $revenueHt += (float) $revenue->getAmountHt();
                $revenueTtc += (float) $revenue->getAmountTtc();
            }

            // Calculate weekly charges
            $weeklyCharges = $this->chargeRepository->createQueryBuilder('c')
                ->where('c.date >= :start AND c.date <= :end')
                ->setParameter('start', $lastMonday)
                ->setParameter('end', $lastSunday)
                ->getQuery()
                ->getResult();

            $charges = 0;
            foreach ($weeklyCharges as $charge) {
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
            $statistics->setPeriod('weekly');
            $statistics->setDate($lastSunday);
            $statistics->setRevenueHt((string) $revenueHt);
            $statistics->setRevenueTtc((string) $revenueTtc);
            $statistics->setCharges((string) $charges);
            $statistics->setCommission((string) $commission);
            $statistics->setProfit((string) $profit);
            $statistics->setClientsCount($clientsCount);

            $this->entityManager->persist($statistics);
        }

        $this->entityManager->flush();

        $io->success(sprintf('Archived weekly statistics for %d employees', count($employees)));

        return Command::SUCCESS;
    }
}
