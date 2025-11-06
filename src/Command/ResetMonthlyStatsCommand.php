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
    name: 'app:reset-monthly-stats',
    description: 'Reset monthly global statistics and archive them',
)]
class ResetMonthlyStatsCommand extends Command
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
        $this->setDescription('Resets monthly global statistics and archives them');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get last month
        $lastMonth = new \DateTime('first day of last month');
        $lastMonthEnd = new \DateTime('last day of last month');

        // Calculate and archive monthly global statistics
        $monthlyRevenues = $this->revenueRepository->createQueryBuilder('r')
            ->where('r.date >= :start AND r.date <= :end')
            ->setParameter('start', $lastMonth)
            ->setParameter('end', $lastMonthEnd)
            ->getQuery()
            ->getResult();

        $revenueHt = 0;
        $revenueTtc = 0;
        $clientsCount = count($monthlyRevenues);

        foreach ($monthlyRevenues as $revenue) {
            $revenueHt += (float) $revenue->getAmountHt();
            $revenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate monthly charges
        $monthlyCharges = $this->chargeRepository->createQueryBuilder('c')
            ->where('c.date >= :start AND c.date <= :end')
            ->setParameter('start', $lastMonth)
            ->setParameter('end', $lastMonthEnd)
            ->getQuery()
            ->getResult();

        $charges = 0;
        foreach ($monthlyCharges as $charge) {
            $charges += (float) $charge->getAmount();
        }

        // Calculate total commissions for the month
        $employees = $this->employeeRepository->findAll();
        $totalCommissions = 0;
        foreach ($employees as $employee) {
            $commissionPercentage = (float) ($employee->getCommissionPercentage() ?? 0);
            $employeeRevenues = $this->revenueRepository->createQueryBuilder('r')
                ->where('r.employee = :employee')
                ->andWhere('r.date >= :start AND r.date <= :end')
                ->setParameter('employee', $employee)
                ->setParameter('start', $lastMonth)
                ->setParameter('end', $lastMonthEnd)
                ->getQuery()
                ->getResult();

            $employeeRevenueHt = 0;
            foreach ($employeeRevenues as $revenue) {
                $employeeRevenueHt += (float) $revenue->getAmountHt();
            }

            $totalCommissions += $employeeRevenueHt * ($commissionPercentage / 100);
        }

        // Calculate profit
        $profit = $revenueHt - $charges - $totalCommissions;

        // Create monthly global statistics entry
        $statistics = new Statistics();
        $statistics->setEmployee(null); // Global stats
        $statistics->setPeriod('monthly_global');
        $statistics->setDate($lastMonthEnd);
        $statistics->setRevenueHt((string) $revenueHt);
        $statistics->setRevenueTtc((string) $revenueTtc);
        $statistics->setCharges((string) $charges);
        $statistics->setCommission((string) $totalCommissions);
        $statistics->setProfit((string) $profit);
        $statistics->setClientsCount($clientsCount);

        $this->entityManager->persist($statistics);
        $this->entityManager->flush();

        $io->success(sprintf('Archived monthly global statistics for %s: Revenue HT: €%s, Charges: €%s, Commissions: €%s, Profit: €%s',
            $lastMonthEnd->format('M Y'),
            number_format($revenueHt, 2, ',', ' '),
            number_format($charges, 2, ',', ' '),
            number_format($totalCommissions, 2, ',', ' '),
            number_format($profit, 2, ',', ' ')
        ));

        return Command::SUCCESS;
    }
}
