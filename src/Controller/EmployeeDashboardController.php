<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use App\Repository\PackageRepository;
use App\Repository\RevenueRepository;
use App\Repository\StatisticsRepository;
use App\Repository\WeeklyCommissionRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDashboardController extends AbstractController
{
    #[Route('/employee/dashboard', name: 'app_employee_dashboard')]
    public function index(AppointmentRepository $appointmentRepository, RevenueRepository $revenueRepository, PackageRepository $packageRepository, StatisticsRepository $statisticsRepository, WeeklyCommissionRepository $weeklyCommissionRepository): Response
    {
        /** @var \App\Entity\Employee $user */
        $user = $this->getUser();

        // Get today's appointments for the employee
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $todayAppointments = $appointmentRepository->findBy([
            'employee' => $user,
            'date' => [$today, $tomorrow]
        ], ['date' => 'ASC']);

        // Get all revenues for the employee (remove limit to show all 14)
        $recentRevenues = $revenueRepository->findBy(
            ['employee' => $user],
            ['date' => 'DESC']
        );

        // Calculate total revenue HT for current month (all revenues, not just validated)
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        $monthlyRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date >= :start AND r.date <= :end')
            ->setParameter('employee', $user)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getResult();

        $totalMonthlyRevenueHt = array_reduce($monthlyRevenues, function($sum, $revenue) {
            return $sum + $revenue->getAmountHt(); // Use HT for commission calculation
        }, 0);

        // Get commission percentage
        $commissionPercentage = (float) ($user->getCommissionPercentage() ?? 0);

        // Get current week commission for validation
        $currentWeekCommission = $this->getCurrentWeekCommission($user, $weeklyCommissionRepository);

        // Calculate validated revenue HT for the month (sum of totalRevenueHt from validated weekly commissions)
        $validatedMonthlyCommissions = $weeklyCommissionRepository->createQueryBuilder('wc')
            ->select('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.validated = true')
            ->andWhere('wc.weekStart >= :startOfMonth')
            ->andWhere('wc.weekEnd <= :endOfMonth')
            ->setParameter('employee', $user)
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->orderBy('wc.weekStart', 'ASC')
            ->getQuery()
            ->getResult();

        // Remove duplicates by week (keep the latest one)
        $uniqueValidatedCommissions = [];
        foreach ($validatedMonthlyCommissions as $commission) {
            $weekKey = $commission->getWeekStart()->format('Y-m-d') . '-' . $commission->getWeekEnd()->format('Y-m-d');
            if (!isset($uniqueValidatedCommissions[$weekKey]) || $commission->getId() > $uniqueValidatedCommissions[$weekKey]->getId()) {
                $uniqueValidatedCommissions[$weekKey] = $commission;
            }
        }

        $validatedRevenueHt = array_reduce($uniqueValidatedCommissions, function($sum, $commission) {
            return $sum + (float)$commission->getTotalRevenueHt();
        }, 0);

        // Calculate total commission for the month (based on validated revenues)
        $totalCommission = $validatedRevenueHt * ($commissionPercentage / 100);

        // Calculate paid commission for the month (sum of paid weekly commissions for current month)
        // Exclude duplicates
        $paidCommissions = $weeklyCommissionRepository->createQueryBuilder('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.paid = true')
            ->andWhere('wc.weekStart >= :startOfMonth')
            ->andWhere('wc.weekEnd <= :endOfMonth')
            ->setParameter('employee', $user)
            ->setParameter('startOfMonth', $startOfMonth)
            ->setParameter('endOfMonth', $endOfMonth)
            ->orderBy('wc.weekStart', 'ASC')
            ->getQuery()
            ->getResult();

        // Remove duplicates by week (keep the latest one)
        $uniquePaidCommissions = [];
        foreach ($paidCommissions as $commission) {
            $weekKey = $commission->getWeekStart()->format('Y-m-d') . '-' . $commission->getWeekEnd()->format('Y-m-d');
            if (!isset($uniquePaidCommissions[$weekKey]) || $commission->getId() > $uniquePaidCommissions[$weekKey]->getId()) {
                $uniquePaidCommissions[$weekKey] = $commission;
            }
        }

        $validatedCommission = array_reduce($uniquePaidCommissions, function($sum, $commission) {
            return $sum + (float)$commission->getTotalCommission();
        }, 0);

        // Calculate pending commission (current week's commission if not validated)
        $currentWeekCommission = $this->getCurrentWeekCommission($user, $weeklyCommissionRepository);
        if ($currentWeekCommission && !$currentWeekCommission->isValidated()) {
            $pendingCommission = (float)$currentWeekCommission->getTotalCommission();
            $pendingRevenueHt = (float)$currentWeekCommission->getTotalRevenueHt();
            $pendingClientsCount = $currentWeekCommission->getClientsCount();
        } else {
            $pendingCommission = 0;
            $pendingRevenueHt = 0;
            $pendingClientsCount = 0;
        }

        // No need for additional pending calculation since we use current week commission

        // Get client count for current month
        $monthlyClientCount = count($monthlyRevenues);

        // Calculate today's CA HT (revenus d'aujourd'hui depuis 00:05)
        $today = new \DateTime('today');
        $todayStart = new \DateTime('today 00:05');
        $tomorrow = new \DateTime('tomorrow');
        $todayRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date >= :start AND r.date < :end')
            ->setParameter('employee', $user)
            ->setParameter('start', $todayStart)
            ->setParameter('end', $tomorrow)
            ->getQuery()
            ->getResult();

        $totalCaHt = array_reduce($todayRevenues, function($sum, $revenue) {
            return $sum + $revenue->getAmountHt();
        }, 0);

        // Calculate today's commission (based on today's revenues depuis 00:05)
        $todayCommission = $totalCaHt * ($commissionPercentage / 100);

        // Calculate clients today
        $todayClientsCount = count($todayRevenues);

        // Get all available packages
        $packages = $packageRepository->findAll();

        // Calculate commission for each package
        $packagesWithCommission = [];
        foreach ($packages as $package) {
            $priceHt = $package->getPrice() / 1.20; // Price stored is TTC, so divide by 1.20 to get HT
            $commission = $priceHt * ($commissionPercentage / 100);
            $packagesWithCommission[] = [
                'package' => $package,
                'priceHt' => $priceHt,
                'commission' => $commission,
            ];
        }

        // Get statistics for the employee
        $weeklyStats = $statisticsRepository->findBy(
            ['employee' => $user, 'period' => 'weekly'],
            ['date' => 'DESC'],
            4 // Last 4 weeks
        );

        $monthlyStats = $statisticsRepository->findBy(
            ['employee' => $user, 'period' => 'monthly'],
            ['date' => 'DESC'],
            12 // Last 12 months
        );

        // Get commission history (validated commissions)
        $commissionHistory = $this->getCommissionHistory($user, $weeklyCommissionRepository);

        return $this->render('employee_dashboard/index.html.twig', [
            'todayAppointments' => $todayAppointments,
            'recentRevenues' => $recentRevenues,
            'totalMonthlyRevenueHt' => $totalMonthlyRevenueHt,
            'totalCommission' => $totalCommission,
            'validatedCommission' => $validatedCommission,
            'validatedRevenueHt' => $validatedRevenueHt,
            'pendingCommission' => $pendingCommission,
            'pendingRevenueHt' => $pendingRevenueHt,
            'pendingClientsCount' => $pendingClientsCount,
            'commissionPercentage' => $commissionPercentage,
            'totalCaHt' => $totalCaHt,
            'todayCommission' => $todayCommission,
            'todayClientsCount' => $todayClientsCount,
            'monthlyClientCount' => $monthlyClientCount,
            'packagesWithCommission' => $packagesWithCommission,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'currentWeekCommission' => $currentWeekCommission,
            'commissionHistory' => $commissionHistory,
        ]);
    }

    #[Route('/employee/validate-commission', name: 'app_employee_validate_commission', methods: ['POST'])]
    public function validateCommission(Request $request, WeeklyCommissionRepository $weeklyCommissionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $commissionId = $request->request->get('commission_id');
        $user = $this->getUser();

        $commission = $weeklyCommissionRepository->find($commissionId);

        if (!$commission || $commission->getEmployee() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Commission non trouvée ou accès non autorisé.']);
        }

        if ($commission->isValidated()) {
            return new JsonResponse(['success' => false, 'message' => 'Cette commission est déjà validée.']);
        }

        $commission->setValidated(true);
        $commission->setValidatedAt(new \DateTime());

        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Commission validée avec succès.']);
    }

    #[Route('/employee/mark-commission-paid', name: 'app_employee_mark_commission_paid', methods: ['POST'])]
    public function markCommissionPaid(Request $request, WeeklyCommissionRepository $weeklyCommissionRepository, EntityManagerInterface $entityManager): JsonResponse
    {
        $commissionId = $request->request->get('commission_id');
        $user = $this->getUser();

        $commission = $weeklyCommissionRepository->find($commissionId);

        if (!$commission || $commission->getEmployee() !== $user) {
            return new JsonResponse(['success' => false, 'message' => 'Commission non trouvée ou accès non autorisé.']);
        }

        if (!$commission->isValidated()) {
            return new JsonResponse(['success' => false, 'message' => 'Cette commission doit d\'abord être validée.']);
        }

        if ($commission->isPaid()) {
            return new JsonResponse(['success' => false, 'message' => 'Cette commission est déjà payée.']);
        }

        $commission->setPaid(true);
        $commission->setPaidAt(new \DateTime());

        $entityManager->flush();

        return new JsonResponse(['success' => true, 'message' => 'Commission marquée comme payée avec succès.']);
    }

    private function getCurrentWeekCommission($user, WeeklyCommissionRepository $weeklyCommissionRepository): ?object
    {
        $now = new \DateTime();
        $currentMonday = new \DateTime('monday this week');
        $currentSunday = new \DateTime('sunday this week');

        return $weeklyCommissionRepository->findByEmployeeAndWeek($user, $currentMonday, $currentSunday);
    }

    private function getCommissionHistory($user, WeeklyCommissionRepository $weeklyCommissionRepository): array
    {
        // Get all commissions for the last 12 weeks (validated or not, paid or not)
        $allCommissions = $weeklyCommissionRepository->findBy(
            ['employee' => $user],
            ['weekStart' => 'DESC'],
            12
        );

        // Remove duplicates by week (keep the latest one)
        $uniqueCommissions = [];
        foreach ($allCommissions as $commission) {
            $weekKey = $commission->getWeekStart()->format('Y-m-d') . '-' . $commission->getWeekEnd()->format('Y-m-d');
            if (!isset($uniqueCommissions[$weekKey]) || $commission->getId() > $uniqueCommissions[$weekKey]->getId()) {
                $uniqueCommissions[$weekKey] = $commission;
            }
        }

        // Return only the unique commissions, sorted by weekStart DESC
        return array_values(array_slice($uniqueCommissions, 0, 12));
    }

    private function calculatePendingCommission($user, $lastValidatedCommission, RevenueRepository $revenueRepository): array
    {
        $startDate = null;

        if ($lastValidatedCommission) {
            // Start from the date of last validation
            $startDate = $lastValidatedCommission->getValidatedAt();
        } else {
            // If no validation yet, start from beginning of current month
            $startDate = new \DateTime('first day of this month');
        }

        // Get revenues since last validation
        $pendingRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date >= :startDate')
            ->setParameter('employee', $user)
            ->setParameter('startDate', $startDate)
            ->getQuery()
            ->getResult();

        $totalRevenueHt = array_reduce($pendingRevenues, function($sum, $revenue) {
            return $sum + $revenue->getAmountHt();
        }, 0);

        $commissionPercentage = (float) ($user->getCommissionPercentage() ?? 0);
        $totalCommission = $totalRevenueHt * ($commissionPercentage / 100);
        $clientsCount = count($pendingRevenues);

        return [
            'revenueHt' => $totalRevenueHt,
            'commission' => $totalCommission,
            'clientsCount' => $clientsCount
        ];
    }
}
