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

        // Calculate total revenue for current week
        $startOfWeek = new \DateTime('monday this week');
        $endOfWeek = new \DateTime('sunday this week');
        $weeklyRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date >= :start AND r.date <= :end')
            ->setParameter('employee', $user)
            ->setParameter('start', $startOfWeek)
            ->setParameter('end', $endOfWeek)
            ->getQuery()
            ->getResult();

        $totalWeeklyRevenue = array_reduce($weeklyRevenues, function($sum, $revenue) {
            return $sum + $revenue->getAmountHt(); // Use HT for commission calculation
        }, 0);

        // Get commission percentage
        $commissionPercentage = (float) ($user->getCommissionPercentage() ?? 0);

        // Calculate commission for current week (unvalidated weekly commission)
        $currentWeekCommission = $this->getCurrentWeekCommission($user, $weeklyCommissionRepository);
        $totalCommission = $currentWeekCommission ? (float)$currentWeekCommission->getTotalCommission() : 0;

        // Calculate pending commission (unvalidated commissions)
        $pendingCommissions = $weeklyCommissionRepository->createQueryBuilder('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.validated = false')
            ->setParameter('employee', $user)
            ->getQuery()
            ->getResult();

        $pendingCommission = array_reduce($pendingCommissions, function($sum, $commission) {
            return $sum + (float)$commission->getTotalCommission();
        }, 0);

        // Calculate validated and paid commissions
        $validatedCommissions = $weeklyCommissionRepository->createQueryBuilder('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.validated = true')
            ->andWhere('wc.paid = false')
            ->setParameter('employee', $user)
            ->getQuery()
            ->getResult();

        $validatedCommission = array_reduce($validatedCommissions, function($sum, $commission) {
            return $sum + (float)$commission->getTotalCommission();
        }, 0);

        $paidCommissions = $weeklyCommissionRepository->createQueryBuilder('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.paid = true')
            ->setParameter('employee', $user)
            ->getQuery()
            ->getResult();

        $paidCommission = array_reduce($paidCommissions, function($sum, $commission) {
            return $sum + (float)$commission->getTotalCommission();
        }, 0);

        // Get client count for current week
        $weeklyClientCount = count($weeklyRevenues);

        // Calculate today's CA HT
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

        // Get current week commission for validation
        $currentWeekCommission = $this->getCurrentWeekCommission($user, $weeklyCommissionRepository);

        // Get commission history (validated commissions)
        $commissionHistory = $this->getCommissionHistory($user, $weeklyCommissionRepository);

        return $this->render('employee_dashboard/index.html.twig', [
            'todayAppointments' => $todayAppointments,
            'recentRevenues' => $recentRevenues,
            'totalWeeklyRevenue' => $totalWeeklyRevenue,
            'totalCommission' => $totalCommission,
            'pendingCommission' => $pendingCommission,
            'validatedCommission' => $validatedCommission,
            'paidCommission' => $paidCommission,
            'commissionPercentage' => $commissionPercentage,
            'totalCaHt' => $totalCaHt,
            'weeklyClientCount' => $weeklyClientCount,
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
        // Get validated commissions for the last 12 weeks
        return $weeklyCommissionRepository->findBy(
            ['employee' => $user, 'validated' => true],
            ['weekStart' => 'DESC'],
            12
        );
    }
}
