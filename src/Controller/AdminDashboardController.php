<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Form\EmployeeFormType;
use App\Repository\AppointmentRepository;
use App\Repository\EmployeeRepository;
use App\Repository\PackageRepository;
use App\Repository\RevenueRepository;
use App\Repository\StatisticsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class AdminDashboardController extends AbstractController
{
    private EntityManagerInterface $entityManager;

    #[Route('/admin/dashboard', name: 'app_admin_dashboard')]
    public function index(
        Request $request,
        AppointmentRepository $appointmentRepository,
        EmployeeRepository $employeeRepository,
        RevenueRepository $revenueRepository,
        StatisticsRepository $statisticsRepository,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->entityManager = $entityManager;
        // Handle employee creation form
        $employee = new Employee();
        $form = $this->createForm(EmployeeFormType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($employee, $form->get('plainPassword')->getData());
            $employee->setPassword($hashedPassword);

            // Set employee role
            $employee->setRoles(['ROLE_EMPLOYEE']);

            // Set verified to true for admin-created employees
            $employee->setIsVerified(true);

            $entityManager->persist($employee);
            $entityManager->flush();

            $this->addFlash('success', 'Employé créé avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }
        // Get all employees
        $employees = $employeeRepository->findAll();

        // Calculate global statistics for today
        $today = new \DateTime('today');
        $todayRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.date >= :start')
            ->setParameter('start', $today)
            ->getQuery()
            ->getResult();

        $todayRevenueHt = 0;
        $todayRevenueTtc = 0;
        foreach ($todayRevenues as $revenue) {
            $todayRevenueHt += (float) $revenue->getAmountHt();
            $todayRevenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate global statistics for current week
        $weekStart = new \DateTime('monday this week');
        $weekRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.date >= :start')
            ->setParameter('start', $weekStart)
            ->getQuery()
            ->getResult();

        $weekRevenueHt = 0;
        $weekRevenueTtc = 0;
        foreach ($weekRevenues as $revenue) {
            $weekRevenueHt += (float) $revenue->getAmountHt();
            $weekRevenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate global statistics for current month
        $monthStart = new \DateTime('first day of this month');
        $monthRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.date >= :start')
            ->setParameter('start', $monthStart)
            ->getQuery()
            ->getResult();

        $monthRevenueHt = 0;
        $monthRevenueTtc = 0;
        foreach ($monthRevenues as $revenue) {
            $monthRevenueHt += (float) $revenue->getAmountHt();
            $monthRevenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate yearly statistics
        $yearStart = new \DateTime('first day of January this year');
        $yearRevenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.date >= :start')
            ->setParameter('start', $yearStart)
            ->getQuery()
            ->getResult();

        $yearRevenueHt = 0;
        $yearRevenueTtc = 0;
        foreach ($yearRevenues as $revenue) {
            $yearRevenueHt += (float) $revenue->getAmountHt();
            $yearRevenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate charges for different periods
        $todayCharges = $this->calculateCharges($today);
        $weekCharges = $this->calculateCharges($weekStart);
        $monthCharges = $this->calculateCharges($monthStart);
        $yearCharges = $this->calculateCharges($yearStart);

        // Calculate commissions for different periods
        $todayCommissions = $this->calculateCommissions($today);
        $weekCommissions = $this->calculateCommissions($weekStart);
        $monthCommissions = $this->calculateCommissions($monthStart);
        $yearCommissions = $this->calculateCommissions($yearStart);

        // Get employee statistics
        $employeeStats = [];
        foreach ($employees as $employee) {
            $employeeStats[$employee->getId()] = $this->getEmployeeStats($employee, $today);
        }

        // Get recent appointments
        $recentAppointments = $appointmentRepository->findBy(
            [],
            ['date' => 'DESC'],
            10
        );

        return $this->render('admin_dashboard/index.html.twig', [
            'employees' => $employees,
            'todayRevenueHt' => $todayRevenueHt,
            'todayRevenueTtc' => $todayRevenueTtc,
            'weekRevenueHt' => $weekRevenueHt,
            'weekRevenueTtc' => $weekRevenueTtc,
            'monthRevenueHt' => $monthRevenueHt,
            'monthRevenueTtc' => $monthRevenueTtc,
            'yearRevenueHt' => $yearRevenueHt,
            'yearRevenueTtc' => $yearRevenueTtc,
            'todayCharges' => $todayCharges,
            'weekCharges' => $weekCharges,
            'monthCharges' => $monthCharges,
            'yearCharges' => $yearCharges,
            'todayCommissions' => $todayCommissions,
            'weekCommissions' => $weekCommissions,
            'monthCommissions' => $monthCommissions,
            'yearCommissions' => $yearCommissions,
            'employeeStats' => $employeeStats,
            'recentAppointments' => $recentAppointments,
            'employeeForm' => $form->createView(),
        ]);
    }

    private function calculateCharges(\DateTime $startDate): float
    {
        // Calculate total charges from start date to now
        $chargeRepository = $this->entityManager->getRepository(\App\Entity\Charge::class);
        $charges = $chargeRepository->createQueryBuilder('c')
            ->where('c.date >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult();

        $totalCharges = 0;
        foreach ($charges as $charge) {
            $totalCharges += (float) $charge->getAmount();
        }

        return $totalCharges;
    }

    private function calculateCommissions(\DateTime $startDate): float
    {
        // Calculate total commissions paid to employees from start date to now
        $appointmentRepository = $this->entityManager->getRepository(\App\Entity\Appointment::class);
        $appointments = $appointmentRepository->createQueryBuilder('a')
            ->where('a.date >= :start')
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult();

        $totalCommissions = 0;
        foreach ($appointments as $appointment) {
            $commissionPercentage = $appointment->getEmployee()->getCommissionPercentage();
            $commission = ((float) $appointment->getPackage()->getPrice() * $commissionPercentage) / 100;
            $totalCommissions += $commission;
        }

        return $totalCommissions;
    }

    private function getEmployeeStats(\App\Entity\Employee $employee, \DateTime $startDate): array
    {
        $appointmentRepository = $this->entityManager->getRepository(\App\Entity\Appointment::class);
        $appointments = $appointmentRepository->createQueryBuilder('a')
            ->where('a.employee = :employee')
            ->andWhere('a.date >= :start')
            ->setParameter('employee', $employee)
            ->setParameter('start', $startDate)
            ->getQuery()
            ->getResult();

        $totalRevenue = 0;
        $totalCommission = 0;
        $clientCount = count($appointments);

        foreach ($appointments as $appointment) {
            $revenue = (float) $appointment->getPackage()->getPrice();
            $totalRevenue += $revenue;
            $commission = ($revenue * $employee->getCommissionPercentage()) / 100;
            $totalCommission += $commission;
        }

        return [
            'revenue' => $totalRevenue,
            'commission' => $totalCommission,
            'clients' => $clientCount,
        ];
    }
}
