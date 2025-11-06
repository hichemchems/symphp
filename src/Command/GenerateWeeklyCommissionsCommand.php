<?php

namespace App\Command;

use App\Entity\WeeklyCommission;
use App\Repository\EmployeeRepository;
use App\Repository\RevenueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class GenerateWeeklyCommissionsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmployeeRepository $employeeRepository,
        private RevenueRepository $revenueRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName('app:generate-weekly-commissions');
        $this->setDescription('Generates weekly commissions for all employees');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get current week (Monday to Sunday)
        $now = new \DateTime();
        $currentMonday = new \DateTime('monday this week');
        $currentSunday = new \DateTime('sunday this week');

        $employees = $this->employeeRepository->findAll();

        foreach ($employees as $employee) {
            // Check if commission already exists for this week
            $existingCommissions = $this->entityManager->getRepository(WeeklyCommission::class)
                ->createQueryBuilder('wc')
                ->where('wc.employee = :employee')
                ->andWhere('wc.weekStart = :weekStart')
                ->andWhere('wc.weekEnd = :weekEnd')
                ->setParameter('employee', $employee)
                ->setParameter('weekStart', $currentMonday)
                ->setParameter('weekEnd', $currentSunday)
                ->getQuery()
                ->getResult();

            $existingCommission = count($existingCommissions) > 0 ? $existingCommissions[0] : null;

            if ($existingCommission && $existingCommission->isPaid()) {
                $io->note(sprintf('Commission already paid for employee %s for week %s to %s',
                    $employee->getFirstName() . ' ' . $employee->getLastName(),
                    $currentMonday->format('Y-m-d'),
                    $currentSunday->format('Y-m-d')
                ));
                continue;
            }

            // Calculate weekly revenue HT and client count
            $weeklyRevenues = $this->revenueRepository->createQueryBuilder('r')
                ->where('r.employee = :employee')
                ->andWhere('r.date >= :start AND r.date <= :end')
                ->setParameter('employee', $employee)
                ->setParameter('start', $currentMonday)
                ->setParameter('end', $currentSunday)
                ->getQuery()
                ->getResult();

            $revenueHt = 0;
            $clientsCount = count($weeklyRevenues);

            foreach ($weeklyRevenues as $revenue) {
                $revenueHt += (float) $revenue->getAmountHt();
            }

            // Calculate commission
            $commissionPercentage = (float) ($employee->getCommissionPercentage() ?? 0);
            $totalCommission = $revenueHt * ($commissionPercentage / 100);

            if ($existingCommission && !$existingCommission->isPaid()) {
                // Update existing non-paid commission
                $existingCommission->setTotalCommission((string) $totalCommission);
                $existingCommission->setTotalRevenueHt((string) $revenueHt);
                $existingCommission->setClientsCount($clientsCount);

                $io->note(sprintf('Updated commission for employee %s: €%s for %d clients',
                    $employee->getFirstName() . ' ' . $employee->getLastName(),
                    number_format($totalCommission, 2, ',', ' '),
                    $clientsCount
                ));
            } else {
                // Create new commission entry if none exists or if existing is paid
                $weeklyCommission = new WeeklyCommission();
                $weeklyCommission->setEmployee($employee);
                $weeklyCommission->setTotalCommission((string) $totalCommission);
                $weeklyCommission->setTotalRevenueHt((string) $revenueHt);
                $weeklyCommission->setClientsCount($clientsCount);
                $weeklyCommission->setWeekStart($currentMonday);
                $weeklyCommission->setWeekEnd($currentSunday);
                $weeklyCommission->setValidated(false);
                $weeklyCommission->setPaid(false);

                $this->entityManager->persist($weeklyCommission);

                $io->success(sprintf('Created commission for employee %s: €%s for %d clients',
                    $employee->getFirstName() . ' ' . $employee->getLastName(),
                    number_format($totalCommission, 2, ',', ' '),
                    $clientsCount
                ));
            }
        }

        $this->entityManager->flush();

        $io->success(sprintf('Generated weekly commissions for %d employees', count($employees)));

        return Command::SUCCESS;
    }
}
