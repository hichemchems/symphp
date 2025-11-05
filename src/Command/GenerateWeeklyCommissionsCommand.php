<?php

namespace App\Command;

use App\Entity\WeeklyCommission;
use App\Repository\EmployeeRepository;
use App\Repository\RevenueRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-weekly-commissions',
    description: 'Generate weekly commissions for all employees',
)]
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
            $existingCommission = $this->entityManager->getRepository(WeeklyCommission::class)
                ->findByEmployeeAndWeek($employee, $currentMonday, $currentSunday);

            if ($existingCommission) {
                $io->note(sprintf('Commission already exists for employee %s for week %s to %s',
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

            // Create weekly commission entry
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

        $this->entityManager->flush();

        $io->success(sprintf('Generated weekly commissions for %d employees', count($employees)));

        return Command::SUCCESS;
    }
}
