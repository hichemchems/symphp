<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use App\Repository\PackageRepository;
use App\Repository\RevenueRepository;
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
    public function index(AppointmentRepository $appointmentRepository, RevenueRepository $revenueRepository, PackageRepository $packageRepository, WeeklyCommissionRepository $weeklyCommissionRepository): Response
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



        // Get validated and paid commissions from weekly_commissions table
        $validatedCommissions = $weeklyCommissionRepository->findBy([
            'employee' => $user,
            'validated' => true
        ]);

        $validatedRevenueHt = 0;
        $totalCommission = 0;
        $validatedCommission = 0;

        foreach ($validatedCommissions as $commission) {
            $validatedRevenueHt += $commission->getTotalRevenueHt();
            $totalCommission += $commission->getTotalCommission();
            if ($commission->isPaid()) {
                $validatedCommission += $commission->getTotalCommission();
            }
        }

        // Get pending commissions (weekly commissions not yet validated AND for completed weeks only)
        $today = new \DateTime('today');

        $pendingCommissions = $weeklyCommissionRepository->createQueryBuilder('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.validated = false')
            ->andWhere('wc.weekEnd <= :today')  // Only completed weeks
            ->setParameter('employee', $user)
            ->setParameter('today', $today)
            ->getQuery()
            ->getResult();

        $pendingCommission = 0;
        $pendingRevenueHt = 0;
        $pendingClientsCount = 0;

        foreach ($pendingCommissions as $commission) {
            $pendingCommission += $commission->getTotalCommission();
            $pendingRevenueHt += $commission->getTotalRevenueHt();
            $pendingClientsCount += $commission->getClientsCount();
        }

        // Get client count for current month
        $monthlyClientCount = count($monthlyRevenues);

        // Calculate today's CA HT (revenus d'aujourd'hui depuis 00:00)
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $todayRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date >= :start AND r.date < :end')
            ->setParameter('employee', $user)
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->getQuery()
            ->getResult();

        $totalCaHt = array_reduce($todayRevenues, function($sum, $revenue) {
            return $sum + $revenue->getAmountHt();
        }, 0);

        // Calculate today's commission (based on today's revenues)
        $todayCommission = $totalCaHt * ($commissionPercentage / 100);

        // Calculate clients today
        $todayClientsCount = count($todayRevenues);

        // Calculate current week's CA HT (revenus de la semaine actuelle SANS aujourd'hui)
        $weekStart = new \DateTime('monday this week');
        $yesterday = new \DateTime('yesterday 23:59:59');
        $weekRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date >= :start AND r.date <= :end')
            ->setParameter('employee', $user)
            ->setParameter('start', $weekStart)
            ->setParameter('end', $yesterday)
            ->getQuery()
            ->getResult();

        $weeklyCaHt = array_reduce($weekRevenues, function($sum, $revenue) {
            return $sum + $revenue->getAmountHt();
        }, 0);

        // Calculate weekly commission
        $weeklyCommission = $weeklyCaHt * ($commissionPercentage / 100);

        // Calculate monthly CA HT (revenus du mois actuel SANS la semaine en cours)
        $lastWeekEnd = new \DateTime('last sunday 23:59:59');
        $monthlyRevenuesWithoutCurrentWeek = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date >= :start AND r.date <= :end')
            ->setParameter('employee', $user)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $lastWeekEnd)
            ->getQuery()
            ->getResult();

        $monthlyCaHt = array_reduce($monthlyRevenuesWithoutCurrentWeek, function($sum, $revenue) {
            return $sum + $revenue->getAmountHt();
        }, 0);

        // Calculate monthly commission
        $monthlyCommission = $monthlyCaHt * ($commissionPercentage / 100);

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

        // No need for statistics anymore since we removed the buttons

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
            'weeklyCaHt' => $weeklyCaHt,
            'weeklyCommission' => $weeklyCommission,
            'monthlyCaHt' => $monthlyCaHt,
            'monthlyCommission' => $monthlyCommission,
            'monthlyClientCount' => $monthlyClientCount,
            'packagesWithCommission' => $packagesWithCommission,
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
        // Get all commissions for the last 12 weeks (validated or not, paid or not) BUT exclude current week
        $today = new \DateTime('today');

        $allCommissions = $weeklyCommissionRepository->createQueryBuilder('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.weekEnd <= :today')  // Only completed weeks
            ->setParameter('employee', $user)
            ->setParameter('today', $today)
            ->orderBy('wc.weekStart', 'DESC')
            ->setMaxResults(12)
            ->getQuery()
            ->getResult();

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
