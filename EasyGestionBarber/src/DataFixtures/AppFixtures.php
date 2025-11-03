<?php

namespace App\DataFixtures;

use App\Entity\Employee;
use App\Entity\Package;
use App\Entity\Charge;
use App\Entity\Revenue;
use App\Entity\Appointment;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private UserPasswordHasherInterface $passwordHasher;

    public function __construct(UserPasswordHasherInterface $passwordHasher)
    {
        $this->passwordHasher = $passwordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        // Create sample employees
        $employees = [];
        $employeeData = [
            ['John', 'Doe', 'john.doe@example.com', 'password123', 10.0],
            ['Jane', 'Smith', 'jane.smith@example.com', 'password123', 15.0],
            ['Bob', 'Johnson', 'bob.johnson@example.com', 'password123', 12.0],
        ];

        foreach ($employeeData as $data) {
            $employee = new Employee();
            $employee->setFirstName($data[0]);
            $employee->setLastName($data[1]);
            $employee->setEmail($data[2]);
            $hashedPassword = $this->passwordHasher->hashPassword($employee, $data[3]);
            $employee->setPassword($hashedPassword);
            $employee->setCommissionPercentage($data[4]);
            $employee->setIsVerified(true);
            if ($data[2] === 'john.doe@example.com') {
                $employee->setRoles(['ROLE_ADMIN']);
            }
            $manager->persist($employee);
            $employees[] = $employee;
        }

        // Create sample packages
        $packages = [];
        $packageData = [
            ['Coupe Classique', 25.0, 'Coupe de cheveux classique'],
            ['Coupe + Barbe', 35.0, 'Coupe de cheveux avec taille de barbe'],
            ['Coloration', 50.0, 'Coloration complète'],
            ['Coiffure Mariage', 80.0, 'Coiffure spéciale pour mariage'],
        ];

        foreach ($packageData as $data) {
            $package = new Package();
            $package->setName($data[0]);
            $package->setPrice($data[1]);
            $package->setDescription($data[2]);
            $manager->persist($package);
            $packages[] = $package;
        }

        // Create sample charges
        $chargeData = [
            ['Loyer', 1200.0, 'Loyer mensuel du salon'],
            ['Électricité', 150.0, 'Facture d\'électricité'],
            ['URSSAF', 300.0, 'Cotisations sociales'],
            ['Adonement', 50.0, 'Assurance'],
            ['Internet', 40.0, 'Connexion internet'],
        ];

        foreach ($chargeData as $data) {
            $charge = new Charge();
            $charge->setName($data[0]);
            $charge->setAmount($data[1]);
            $charge->setDescription($data[2]);
            $manager->persist($charge);
        }

        // Create sample revenues
        for ($i = 0; $i < 20; $i++) {
            $revenue = new Revenue();
            $revenue->setAmountHt($packages[array_rand($packages)]->getPrice());
            $revenue->setAmountTtc($revenue->getAmountHt() * 1.20); // 20% TVA
            $revenue->setDate(new \DateTime('now -' . rand(0, 30) . ' days'));
            $revenue->setEmployee($employees[array_rand($employees)]);
            $revenue->setPackage($packages[array_rand($packages)]);
            $manager->persist($revenue);
        }

        // Create sample appointments
        $clientNames = ['Alice Dupont', 'Marie Martin', 'Pierre Durand', 'Sophie Bernard', 'Thomas Petit'];
        $clientEmails = ['alice@example.com', 'marie@example.com', 'pierre@example.com', 'sophie@example.com', 'thomas@example.com'];
        $clientPhones = ['0612345678', '0698765432', '0655566677', '0644433322', '0677788899'];

        for ($i = 0; $i < 15; $i++) {
            $appointment = new Appointment();
            $appointment->setDate(new \DateTime('now +' . rand(1, 30) . ' days'));
            $appointment->setClientName($clientNames[array_rand($clientNames)]);
            $appointment->setClientEmail($clientEmails[array_rand($clientEmails)]);
            $appointment->setClientPhone($clientPhones[array_rand($clientPhones)]);
            $appointment->setEmployee($employees[array_rand($employees)]);
            $appointment->setPackage($packages[array_rand($packages)]);
            $manager->persist($appointment);
        }

        $manager->flush();
    }
}
