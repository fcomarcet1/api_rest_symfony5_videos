<?php

namespace App\DataFixtures;

use App\Entity\User;
use DateTime;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;


class UserFixture extends Fixture
{

    public function load(ObjectManager $manager)
    {

        for($i = 0; $i < 5; $i++){
            $user = new User();
            $user->setName('name'.$i);
            $user->setSurname('Surname'.$i);
            $user->setEmail('email'.$i.'@mail.com');
            //$password = $this->encoder->hashPassword($user, '123456');
            $password = hash('sha256', 'secret');
            $user->setPassword($password);
            $user->setRoles(['ROLE_USER']);
            $user->setActive(false);
            $user->setCreatedAt(new DateTime('now'));
            $user->setUpdatedAt(new DateTime('now'));
            $user->setEmailToken(null,);
            $user->setEmailTokenExpires(new DateTime('now'));
            $user->setResetPasswordToken(null);
            $user->setResetPasswordExpires(new DateTime('now'));
            $user->setAccessToken(null);

            $manager->persist($user);
        }

        $manager->flush();
    }
}
