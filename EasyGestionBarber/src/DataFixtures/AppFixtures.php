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
        // No sample data loaded - new installations start with clean database
        // All admin accounts created via registration will start with zero data
        // Employees created by admins will also start with zero statistics

        $manager->flush();
    }
}
