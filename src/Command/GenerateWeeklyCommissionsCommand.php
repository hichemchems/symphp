<?php

namespace App\Command;

use App\Entity\Employee;
use App\Entity\WeeklyCommission;
use App\Repository\EmployeeRepository;
use App\Repository\RevenueRepository;
use App\Repository\WeeklyCommissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-weekly-commissions',
    description: 'Generate weekly commissions for all employees based on revenues',
)]
class GenerateWeeklyCommissionsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EmployeeRepository $employeeRepository,
        private RevenueRepository $revenueRepository,
        private WeeklyCommissionRepository $weeklyCommissionRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('weeks-back', null, InputOption::VALUE_OPTIONAL, 'Number of weeks to go back (default: 4)', 4)
            ->addOption('force', null, InputOption::VALUE_NONE, 'Force regeneration of existing commissions')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show what would be done without making changes');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $weeksBack = (int) $input->getOption('weeks-back');
        $force = $input->getOption('force');
        $dryRun = $input->getOption('dry-run');

        $io->title('Generating Weekly Commissions');

        if ($dryRun) {
            $io->warning('DRY RUN MODE - No changes will be made');
        }

        // Get all employees
        $employees = $this->employeeRepository->findAll();
        $io->info(sprintf('Found %d employees', count($employees)));

        $totalCreated = 0;
        $totalUpdated = 0;

        foreach ($employees as $employee) {
            $io->section(sprintf('Processing employee: %s %s', $employee->getFirstName(), $employee->getLastName()));

            // Generate commissions for the last N weeks
            for ($i = 0; $i < $weeksBack; $i++) {
                $weekStart = new \DateTime('monday this week');
                $weekStart->modify("-{$i} weeks");

                $weekEnd = clone $weekStart;
                $weekEnd->modify('next sunday 23:59:59');

                $io->text(sprintf('Processing week: %s to %s',
                    $weekStart->format('Y-m-d'),
                    $weekEnd->format('Y-m-d')
                ));

                // Check if commission already exists
                $existingCommission = $this->weeklyCommissionRepository->findByEmployeeAndWeek(
                    $employee,
                    $weekStart,
                    $weekEnd
                );

                if ($existingCommission && !$force) {
                    $io->text('  Commission already exists, skipping (use --force to regenerate)');
                    continue;
                }

                // Calculate revenues for this week
                $weekRevenues = $this->revenueRepository->createQueryBuilder('r')
                    ->where('r.employee = :employee')
                    ->andWhere('r.date >= :start AND r.date <= :end')
                    ->setParameter('employee', $employee)
                    ->setParameter('start', $weekStart)
                    ->setParameter('end', $weekEnd)
                    ->getQuery()
                    ->getResult();

                $totalRevenueHt = array_reduce($weekRevenues, function($sum, $revenue) {
                    return $sum + $revenue->getAmountHt();
                }, 0);

                $clientsCount = count($weekRevenues);
                $commissionPercentage = (float) ($employee->getCommissionPercentage() ?? 0);
                $totalCommission = $totalRevenueHt * ($commissionPercentage / 100);

                if ($existingCommission && $force) {
                    // Update existing commission
                    $existingCommission->setTotalRevenueHt($totalRevenueHt);
                    $existingCommission->setTotalCommission($totalCommission);
                    $existingCommission->setClientsCount($clientsCount);

                    if (!$dryRun) {
                        $this->entityManager->flush();
                    }

                    $io->text(sprintf('  Updated commission: %.2f € revenue, %.2f € commission, %d clients',
                        $totalRevenueHt, $totalCommission, $clientsCount));
                    $totalUpdated++;
                } elseif (!$existingCommission) {
                    // Create new commission
                    $commission = new WeeklyCommission();
                    $commission->setEmployee($employee);
                    $commission->setWeekStart($weekStart);
                    $commission->setWeekEnd($weekEnd);
                    $commission->setTotalRevenueHt($totalRevenueHt);
                    $commission->setTotalCommission($totalCommission);
                    $commission->setClientsCount($clientsCount);
                    $commission->setValidated(false);
                    $commission->setPaid(false);

                    if (!$dryRun) {
                        $this->entityManager->persist($commission);
                        $this->entityManager->flush();
                    }

                    $io->text(sprintf('  Created commission: %.2f € revenue, %.2f € commission, %d clients',
                        $totalRevenueHt, $totalCommission, $clientsCount));
                    $totalCreated++;
                }
            }
        }

        if (!$dryRun) {
            $this->entityManager->flush();
        }

        $io->success(sprintf('Completed! Created: %d, Updated: %d commissions', $totalCreated, $totalUpdated));

        return Command::SUCCESS;
    }
}
