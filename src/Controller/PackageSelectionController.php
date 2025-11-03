<?php

namespace App\Controller;

use App\Entity\Revenue;
use App\Repository\PackageRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_EMPLOYEE')]
final class PackageSelectionController extends AbstractController
{
    #[Route('/employee/select-package', name: 'app_employee_select_package', methods: ['POST'])]
    public function selectPackage(
        Request $request,
        PackageRepository $packageRepository,
        EntityManagerInterface $entityManager
    ): JsonResponse {
        $user = $this->getUser();
        $packageId = $request->request->get('package_id');

        if (!$packageId) {
            return new JsonResponse(['success' => false, 'message' => 'ID du forfait manquant'], 400);
        }

        $package = $packageRepository->find($packageId);
        if (!$package) {
            return new JsonResponse(['success' => false, 'message' => 'Forfait non trouvé'], 404);
        }

        // Calculate HT price (remove 20% TVA)
        $priceHt = (float) $package->getPrice() / 1.20;

        // Create revenue entry
        $revenue = new Revenue();
        $revenue->setAmountHt($priceHt);
        $revenue->setAmountTtc((float) $package->getPrice());
        $revenue->setDate(new \DateTime());
        $revenue->setEmployee($user);
        $revenue->setPackage($package);

        $entityManager->persist($revenue);
        $entityManager->flush();

        // Calculate commission
        $commissionPercentage = (float) $user->getCommissionPercentage();
        $commission = $priceHt * ($commissionPercentage / 100);

        return new JsonResponse([
            'success' => true,
            'message' => 'Forfait sélectionné avec succès',
            'data' => [
                'price_ht' => $priceHt,
                'commission' => $commission,
                'package_name' => $package->getName()
            ]
        ]);
    }

    #[Route('/employee/package-stats', name: 'app_employee_package_stats', methods: ['GET'])]
    public function getPackageStats(): JsonResponse
    {
        $user = $this->getUser();

        // Calculate today's CA HT for the employee
        $today = new \DateTime('today');
        $tomorrow = new \DateTime('tomorrow');

        $revenues = $user->getRevenues()->filter(function($revenue) use ($today, $tomorrow) {
            return $revenue->getDate() >= $today && $revenue->getDate() < $tomorrow;
        });

        $totalCaHt = 0;
        foreach ($revenues as $revenue) {
            $totalCaHt += (float) $revenue->getAmountHt();
        }

        // Calculate monthly commission
        $startOfMonth = new \DateTime('first day of this month');
        $endOfMonth = new \DateTime('last day of this month');

        $monthlyRevenues = $user->getRevenues()->filter(function($revenue) use ($startOfMonth, $endOfMonth) {
            return $revenue->getDate() >= $startOfMonth && $revenue->getDate() <= $endOfMonth;
        });

        $totalMonthlyRevenue = 0;
        foreach ($monthlyRevenues as $revenue) {
            $totalMonthlyRevenue += (float) $revenue->getAmountHt();
        }

        $commissionPercentage = (float) $user->getCommissionPercentage();
        $totalCommission = $totalMonthlyRevenue * ($commissionPercentage / 100);

        return new JsonResponse([
            'total_ca_ht' => $totalCaHt,
            'total_monthly_revenue' => $totalMonthlyRevenue,
            'total_commission' => $totalCommission
        ]);
    }
}
