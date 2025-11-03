<?php

namespace App\Controller;

use App\Repository\AppointmentRepository;
use App\Repository\PackageRepository;
use App\Repository\RevenueRepository;
use App\Repository\StatisticsRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
final class EmployeeDashboardController extends AbstractController
{
    #[Route('/employee/dashboard', name: 'app_employee_dashboard')]
    public function index(AppointmentRepository $appointmentRepository, RevenueRepository $revenueRepository, PackageRepository $packageRepository, StatisticsRepository $statisticsRepository): Response
    {
        $user = $this->getUser();

        // Get today's appointments for the employee
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $todayAppointments = $appointmentRepository->findBy([
            'employee' => $user,
            'date' => [$today, $tomorrow]
        ], ['date' => 'ASC']);

        // Get recent revenues for the employee
        $recentRevenues = $revenueRepository->findBy(
            ['employee' => $user],
            ['date' => 'DESC'],
            10
        );

        // Calculate total revenue for current month
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');
        $monthlyRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date BETWEEN :start AND :end')
            ->setParameter('employee', $user)
            ->setParameter('start', $startOfMonth)
            ->setParameter('end', $endOfMonth)
            ->getQuery()
            ->getResult();

        $totalMonthlyRevenue = array_reduce($monthlyRevenues, function($sum, $revenue) {
            return $sum + $revenue->getAmountHt(); // Use HT for commission calculation
        }, 0);

        // Calculate commission for current month (based on HT revenue)
        $commissionPercentage = $user->getCommissionPercentage() ?? 0;
        $totalCommission = $totalMonthlyRevenue * ($commissionPercentage / 100);

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
            $priceHt = $package->getPrice() / 1.20; // Remove 20% TVA
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

        return $this->render('employee_dashboard/index.html.twig', [
            'todayAppointments' => $todayAppointments,
            'recentRevenues' => $recentRevenues,
            'totalMonthlyRevenue' => $totalMonthlyRevenue,
            'totalCommission' => $totalCommission,
            'commissionPercentage' => $commissionPercentage,
            'totalCaHt' => $totalCaHt,
            'packagesWithCommission' => $packagesWithCommission,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
        ]);
    }
}
