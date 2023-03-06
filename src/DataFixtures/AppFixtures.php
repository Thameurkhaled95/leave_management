<?php

namespace App\DataFixtures;

use App\Entity\Employe;
use App\Repository\EmployeRepository;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    protected static $users = [
        [
            "email"=>"admin@gmail.com",
            "nom"=>"admin",
            "is_admin"=>true
        ], 
        [
            "email"=>"thameur@gmail.com",
            "nom"=>"thameur"
        ], 
        [
            "email"=>"khaled@gmail.com",
            "nom"=>"khaled"
        ]
    ];
    protected static $password = "adminadmin";
    private $employeRepository;
    private $userPasswordHasher;

    public function __construct(
        UserPasswordHasherInterface $userPasswordHasher,
        EmployeRepository $employeRepository
    ) {
        $this->employeRepository = $employeRepository;
        $this->userPasswordHasher = $userPasswordHasher;
    }

    public function load(ObjectManager $manager): void
    {
        foreach($this::$users as $user)
        {
            $user_db = $this->employeRepository->findOneBy(["email" => $user["email"]]);
            if (empty($user_db)) {
                $user_db = $this->createUser($user);
                // persist object
                $manager->persist($user_db);
            }
            $manager->flush();
        }
    }

    private function createUser($rows)
    {
        $rows["roles"] = (array_key_exists("is_admin", $rows) &&  $rows["is_admin"] == true) ? ["ROLE_ADMIN"] : ["ROLE_EMPLOYE"];
        $rows["password"] = $this->userPasswordHasher->hashPassword(
            new Employe(),
            $this::$password,
        );
        $user = Employe::withRow(
            $rows
        );
        return $user;
    }
}
