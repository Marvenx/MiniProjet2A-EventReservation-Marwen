<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {}

    public function load(ObjectManager $manager): void
    {
        // Create admin user
        $admin = new User();
        $admin->setEmail('admin@example.com');
        $admin->setRoles(['ROLE_ADMIN']);
        $admin->setPassword($this->passwordHasher->hashPassword($admin, 'admin123'));
        $manager->persist($admin);
        $this->addReference('user-admin', $admin);

        // Create regular user
        $user = new User();
        $user->setEmail('user@example.com');
        $user->setRoles(['ROLE_USER']);
        $user->setPassword($this->passwordHasher->hashPassword($user, 'user123'));
        $manager->persist($user);
        $this->addReference('user-regular', $user);

        // Create additional test users
        $testUsers = [
            ['email' => 'john.doe@example.com', 'password' => 'password123'],
            ['email' => 'jane.smith@example.com', 'password' => 'password123'],
        ];

        foreach ($testUsers as $index => $userData) {
            $testUser = new User();
            $testUser->setEmail($userData['email']);
            $testUser->setRoles(['ROLE_USER']);
            $testUser->setPassword($this->passwordHasher->hashPassword($testUser, $userData['password']));
            $manager->persist($testUser);
            $this->addReference('user-test-' . $index, $testUser);
        }

        $manager->flush();
    }
}
