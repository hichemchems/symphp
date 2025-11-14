<?php

namespace App\Controller;

use App\Entity\Employee;
use App\Entity\Package;
use App\Entity\Charge;
use App\Form\EmployeeFormType;
use App\Form\PackageFormType;
use App\Form\ChargeFormType;
use App\Repository\AppointmentRepository;
use App\Repository\EmployeeRepository;
use App\Repository\PackageRepository;
use App\Repository\RevenueRepository;

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
        PackageRepository $packageRepository,
        RevenueRepository $revenueRepository,

        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ): Response {
        $this->entityManager = $entityManager;

        // Handle employee creation form
        $employee = new Employee();
        $employeeForm = $this->createForm(EmployeeFormType::class, $employee);
        $employeeForm->handleRequest($request);

        if ($employeeForm->isSubmitted() && $employeeForm->isValid()) {
            // Hash the password
            $hashedPassword = $passwordHasher->hashPassword($employee, $employeeForm->get('plainPassword')->getData());
            $employee->setPassword($hashedPassword);

            // Set employee role
            $employee->setRoles(['ROLE_EMPLOYEE', 'ROLE_USER']);

            // Set verified to true for admin-created employees
            $employee->setIsVerified(true);

            // Set the creator (current admin)
            $employee->setCreatedBy($this->getUser());

            $entityManager->persist($employee);
            $entityManager->flush();

            $this->addFlash('success', 'Employé créé avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        // Handle package creation form
        $package = new Package();
        $packageForm = $this->createForm(PackageFormType::class, $package);
        $packageForm->handleRequest($request);

        if ($packageForm->isSubmitted() && $packageForm->isValid()) {
            // The price is already set as HT in the entity
            $entityManager->persist($package);
            $entityManager->flush();

            $this->addFlash('success', 'Forfait créé avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        // Handle charge creation form
        $charge = new Charge();
        $chargeForm = $this->createForm(ChargeFormType::class, $charge);
        $chargeForm->handleRequest($request);

        if ($chargeForm->isSubmitted() && $chargeForm->isValid()) {
            // Set the creator (current admin) for the charge
            $charge->setEmployee($this->getUser());

            $entityManager->persist($charge);
            $entityManager->flush();

            $this->addFlash('success', 'Charge ajoutée avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }
        // Get only employees created by this admin OR employees with no creator (orphaned employees)
        $employees = $employeeRepository->createQueryBuilder('e')
            ->where('e.createdBy = :admin OR e.createdBy IS NULL')
            ->setParameter('admin', $this->getUser())
            ->getQuery()
            ->getResult();

        // Calculate global statistics for today (only from employees created by this admin OR orphaned employees)
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $todayRevenues = $revenueRepository->createQueryBuilder('r')
            ->join('r.employee', 'e')
            ->where('r.date >= :start AND r.date < :end')
            ->andWhere('e.createdBy = :admin OR e.createdBy IS NULL')
            ->setParameter('start', $today)
            ->setParameter('end', $tomorrow)
            ->setParameter('admin', $this->getUser())
            ->getQuery()
            ->getResult();

        $todayRevenueHt = 0;
        $todayRevenueTtc = 0;
        foreach ($todayRevenues as $revenue) {
            $todayRevenueHt += (float) $revenue->getAmountHt();
            $todayRevenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate global statistics for current week (only from employees created by this admin OR orphaned employees)
        $weekStart = new \DateTime('monday this week');
        $weekRevenues = $revenueRepository->createQueryBuilder('r')
            ->join('r.employee', 'e')
            ->where('r.date >= :start')
            ->andWhere('e.createdBy = :admin OR e.createdBy IS NULL')
            ->setParameter('start', $weekStart)
            ->setParameter('admin', $this->getUser())
            ->getQuery()
            ->getResult();

        $weekRevenueHt = 0;
        $weekRevenueTtc = 0;
        foreach ($weekRevenues as $revenue) {
            $weekRevenueHt += (float) $revenue->getAmountHt();
            $weekRevenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate global statistics for current month (only from employees created by this admin OR orphaned employees)
        $monthStart = new \DateTime('first day of this month');
        $monthRevenues = $revenueRepository->createQueryBuilder('r')
            ->join('r.employee', 'e')
            ->where('r.date >= :start')
            ->andWhere('e.createdBy = :admin OR e.createdBy IS NULL')
            ->setParameter('start', $monthStart)
            ->setParameter('admin', $this->getUser())
            ->getQuery()
            ->getResult();

        $monthRevenueHt = 0;
        $monthRevenueTtc = 0;
        foreach ($monthRevenues as $revenue) {
            $monthRevenueHt += (float) $revenue->getAmountHt();
            $monthRevenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate yearly statistics (only from employees created by this admin)
        $yearStart = new \DateTime('first day of January this year');
        $yearRevenues = $revenueRepository->createQueryBuilder('r')
            ->join('r.employee', 'e')
            ->where('r.date >= :start')
            ->andWhere('e.createdBy = :admin')
            ->setParameter('start', $yearStart)
            ->setParameter('admin', $this->getUser())
            ->getQuery()
            ->getResult();

        $yearRevenueHt = 0;
        $yearRevenueTtc = 0;
        foreach ($yearRevenues as $revenue) {
            $yearRevenueHt += (float) $revenue->getAmountHt();
            $yearRevenueTtc += (float) $revenue->getAmountTtc();
        }

        // Calculate charges for different periods (only from employees created by this admin)
        $todayCharges = $this->calculateCharges($today, $this->getUser());
        $weekCharges = $this->calculateCharges($weekStart, $this->getUser());
        $monthCharges = $this->calculateCharges($monthStart, $this->getUser());
        $yearCharges = $this->calculateCharges($yearStart, $this->getUser());

        // Calculate commissions for different periods (only from employees created by this admin)
        $todayCommissions = $this->calculateCommissions($today, $this->getUser());
        $weekCommissions = $this->calculateCommissions($weekStart, $this->getUser());
        $monthCommissions = $this->calculateCommissions($monthStart, $this->getUser());
        $yearCommissions = $this->calculateCommissions($yearStart, $this->getUser());

        // Get employee statistics (monthly accumulation)
        $monthStart = new \DateTime('first day of this month');
        $monthEnd = new \DateTime('last day of this month');
        $employeeStats = [];
        foreach ($employees as $employee) {
            $employeeStats[$employee->getId()] = $this->getEmployeeStats($employee, $monthStart, $monthEnd, $entityManager);
        }

        // Get all packages
        $packages = $packageRepository->findAll();

        // Get all charges (from admin's own charges OR employees created by this admin) with optional date filtering
        $chargesQuery = $this->entityManager->getRepository(Charge::class)->createQueryBuilder('c')
            ->join('c.employee', 'e')
            ->where('e.id = :admin OR e.createdBy = :admin')
            ->setParameter('admin', $this->getUser());

        // Apply date filter if provided
        $startDate = $request->query->get('start_date');
        $endDate = $request->query->get('end_date');

        if ($startDate) {
            $chargesQuery->andWhere('c.date >= :startDate')
                ->setParameter('startDate', new \DateTime($startDate));
        }

        if ($endDate) {
            $chargesQuery->andWhere('c.date <= :endDate')
                ->setParameter('endDate', new \DateTime($endDate . ' 23:59:59'));
        }

        $charges = $chargesQuery->orderBy('c.date', 'DESC')
            ->getQuery()
            ->getResult();

        // Get recent appointments (only from employees created by this admin)
        $recentAppointments = $appointmentRepository->createQueryBuilder('a')
            ->join('a.employee', 'e')
            ->where('e.createdBy = :admin')
            ->setParameter('admin', $this->getUser())
            ->orderBy('a.date', 'DESC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult();

        return $this->render('admin_dashboard/index.html.twig', [
            'employees' => $employees,
            'packages' => $packages,
            'charges' => $charges,
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
            'employeeForm' => $employeeForm->createView(),
            'packageForm' => $packageForm->createView(),
            'chargeForm' => $chargeForm->createView(),
        ]);
    }

    private function calculateCharges(\DateTime $startDate, Employee $admin): float
    {
        // Calculate total charges (from admin's own charges OR employees created by this admin) - include all charges regardless of date
        $chargeRepository = $this->entityManager->getRepository(\App\Entity\Charge::class);
        $charges = $chargeRepository->createQueryBuilder('c')
            ->join('c.employee', 'e')
            ->where('e.id = :admin OR e.createdBy = :admin')
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getResult();

        $totalCharges = 0;
        foreach ($charges as $charge) {
            $totalCharges += (float) $charge->getAmount();
        }

        // Prorate charges based on period
        $today = new \DateTime('today');
        $mondayThisWeek = new \DateTime('monday this week');

        if ($startDate->format('Y-m-d') === $today->format('Y-m-d')) {
            // Daily: divide by 20 days
            return $totalCharges / 20;
        } elseif ($startDate->format('Y-m-d') === $mondayThisWeek->format('Y-m-d')) {
            // Weekly: divide by 4 weeks
            return $totalCharges / 4;
        } else {
            // Monthly: return total
            return $totalCharges;
        }
    }

    private function calculateCommissions(\DateTime $startDate, Employee $admin): float
    {
        // Calculate total validated commissions from WeeklyCommission for employees created by this admin
        $weeklyCommissionRepository = $this->entityManager->getRepository(\App\Entity\WeeklyCommission::class);
        $commissions = $weeklyCommissionRepository->createQueryBuilder('wc')
            ->join('wc.employee', 'e')
            ->where('wc.validated = true')
            ->andWhere('wc.weekStart >= :start')
            ->andWhere('e.createdBy = :admin')
            ->setParameter('start', $startDate)
            ->setParameter('admin', $admin)
            ->getQuery()
            ->getResult();

        $totalCommissions = 0;
        foreach ($commissions as $commission) {
            $totalCommissions += (float) $commission->getTotalCommission();
        }

        return $totalCommissions;
    }

    private function getEmployeeStats(\App\Entity\Employee $employee, \DateTime $startDate, \DateTime $endDate, EntityManagerInterface $entityManager): array
    {
        // Get revenue data from Revenue entity for the period
        $revenueRepository = $entityManager->getRepository(\App\Entity\Revenue::class);
        $revenues = $revenueRepository->createQueryBuilder('r')
            ->where('r.employee = :employee')
            ->andWhere('r.date >= :start AND r.date <= :end')
            ->setParameter('employee', $employee)
            ->setParameter('start', $startDate)
            ->setParameter('end', $endDate)
            ->getQuery()
            ->getResult();

        $totalRevenue = 0;
        $totalRevenueHt = 0;
        $clientCount = count($revenues);

        foreach ($revenues as $revenue) {
            $totalRevenueHt += (float) $revenue->getAmountHt();
            $totalRevenue += (float) $revenue->getAmountTtc();
        }

        // Calculate commission based on revenue HT and commission percentage
        $commissionPercentage = (float) ($employee->getCommissionPercentage() ?? 0);
        $totalCommission = $totalRevenueHt * ($commissionPercentage / 100);

        return [
            'revenue' => $totalRevenue,
            'revenueHt' => $totalRevenueHt,
            'commission' => $totalCommission,
            'clients' => $clientCount,
        ];
    }

    #[Route('/admin/employee/{id}/edit', name: 'app_admin_employee_edit', methods: ['GET', 'POST'])]
    public function editEmployee(Request $request, Employee $employee, UserPasswordHasherInterface $passwordHasher): Response
    {
        // Check if employee belongs to current admin
        if ($employee->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(EmployeeFormType::class, $employee);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Hash password if changed
            $plainPassword = $form->get('plainPassword')->getData();
            if ($plainPassword) {
                $employee->setPassword($passwordHasher->hashPassword($employee, $plainPassword));
            }

            $this->entityManager->flush();

            $this->addFlash('success', 'Employé modifié avec succès !');
            return $this->redirectToRoute('app_admin_dashboard');
        }

        return $this->render('admin_dashboard/edit_employee.html.twig', [
            'employee' => $employee,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/admin/employee/{id}/delete', name: 'app_admin_employee_delete', methods: ['POST'])]
    public function deleteEmployee(Request $request, Employee $employee): Response
    {
        // Check if employee belongs to current admin
        if ($employee->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete'.$employee->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($employee);
            $this->entityManager->flush();

            $this->addFlash('success', 'Employé supprimé avec succès !');
        }

        return $this->redirectToRoute('app_admin_dashboard');
    }

    #[Route('/admin/employee/{id}/details', name: 'app_admin_employee_details')]
    public function employeeDetails(Employee $employee, EntityManagerInterface $entityManager): Response
    {
        $this->entityManager = $entityManager;

        // Check if employee belongs to current admin
        if ($employee->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        // Calculate daily stats (today)
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');
        $dailyStats = $this->getEmployeeStats($employee, $today, $tomorrow, $entityManager);

        // Calculate weekly stats (current week SANS aujourd'hui)
        $weekStart = new \DateTime('monday this week');
        $weekEnd = new \DateTime('yesterday 23:59:59');
        $weeklyStats = $this->getEmployeeStats($employee, $weekStart, $weekEnd, $entityManager);

        // Calculate monthly stats (current month SANS la semaine en cours)
        $monthStart = new \DateTime('first day of this month');
        $monthEnd = new \DateTime('last sunday 23:59:59');
        $monthlyStats = $this->getEmployeeStats($employee, $monthStart, $monthEnd, $entityManager);

        // No need for weekly stats anymore since we removed the buttons

        // Get all executed packages (revenues) for the employee
        $allRevenues = $entityManager->getRepository(\App\Entity\Revenue::class)->findBy(
            ['employee' => $employee],
            ['date' => 'DESC']
        );

        // Get validation history (validated commissions) - only completed weeks
        $today = new \DateTime('today');

        $validatedCommissions = $entityManager->getRepository(\App\Entity\WeeklyCommission::class)->createQueryBuilder('wc')
            ->where('wc.employee = :employee')
            ->andWhere('wc.validated = true')
            ->andWhere('wc.weekEnd <= :today')  // Only completed weeks
            ->setParameter('employee', $employee)
            ->setParameter('today', $today)
            ->orderBy('wc.validatedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('admin_dashboard/employee_details.html.twig', [
            'employee' => $employee,
            'dailyStats' => $dailyStats,
            'weeklyStats' => $weeklyStats,
            'monthlyStats' => $monthlyStats,
            'allRevenues' => $allRevenues,
            'validatedCommissions' => $validatedCommissions,
        ]);
    }

    #[Route('/admin/validate-commission', name: 'app_admin_validate_commission', methods: ['POST'])]
    public function validateCommission(Request $request, \App\Repository\WeeklyCommissionRepository $weeklyCommissionRepository, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $commissionId = $request->request->get('commission_id');
        $admin = $this->getUser();

        $commission = $weeklyCommissionRepository->find($commissionId);

        if (!$commission) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => false, 'message' => 'Commission non trouvée.']);
        }

        // Check if commission belongs to an employee created by this admin
        if ($commission->getEmployee()->getCreatedBy() !== $admin) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => false, 'message' => 'Accès non autorisé.']);
        }

        if ($commission->isValidated()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => false, 'message' => 'Cette commission est déjà validée.']);
        }

        $commission->setValidated(true);
        $commission->setValidatedAt(new \DateTime());

        $entityManager->flush();

        return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => true, 'message' => 'Commission validée avec succès.']);
    }

    #[Route('/admin/mark-commission-paid', name: 'app_admin_mark_commission_paid', methods: ['POST'])]
    public function markCommissionPaid(Request $request, \App\Repository\WeeklyCommissionRepository $weeklyCommissionRepository, EntityManagerInterface $entityManager): \Symfony\Component\HttpFoundation\JsonResponse
    {
        $commissionId = $request->request->get('commission_id');
        $admin = $this->getUser();

        $commission = $weeklyCommissionRepository->find($commissionId);

        if (!$commission) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => false, 'message' => 'Commission non trouvée.']);
        }

        // Check if commission belongs to an employee created by this admin
        if ($commission->getEmployee()->getCreatedBy() !== $admin) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => false, 'message' => 'Accès non autorisé.']);
        }

        if (!$commission->isValidated()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => false, 'message' => 'Cette commission doit d\'abord être validée.']);
        }

        if ($commission->isPaid()) {
            return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => false, 'message' => 'Cette commission est déjà payée.']);
        }

        $commission->setPaid(true);
        $commission->setPaidAt(new \DateTime());

        $entityManager->flush();

        return new \Symfony\Component\HttpFoundation\JsonResponse(['success' => true, 'message' => 'Commission marquée comme payée avec succès.']);
    }
}
