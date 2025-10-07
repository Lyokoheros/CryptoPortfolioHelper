<?php

namespace App\DataFixtures;

use App\Entity\Currency;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;

class UserFixtures extends Fixture implements DependentFixtureInterface
{
    const USERS_DATA = [
        [
            'userName' => 'Lyokoheros',
            'name' => 'Maciej',
            'surname' => 'Tomaszyk',
            'email' => 'maciej.a.tomaszyk@gmail.com',
            'country' => 'Poland',
            'nativeCurrency' => 'PLN',
            'displayCurrency' => 'EUR'
        ]
    ];

    public function getDependencies(): array
    {
        return [
            CurrencyFixtures::class,
        ];
    }

    public function load(ObjectManager $manager): void
    {
        var_dump('asy');
        // $product = new Product();
        // $manager->persist($product);
        $this->loadUsers($manager);

        $manager->flush();
    }

    public function loadUsers(ObjectManager $manager): void
    {
        var_dump('usery');
        $currencyRepository = $manager->getRepository(Currency::class);
        echo "repo";
        var_dump(gettype($currencyRepository->findOneBy(['symbol' => 'PLN'])));//tu się wykrzacza
        
        foreach(self::USERS_DATA as $userData)
        {
            var_dump(gettype($currencyRepository->findOneBy(['symbol' => $userData['nativeCurrency']])));
            $user = new user();
            $user->setName($userData['name']);
            $user->setSurname($userData['surname']);
            var_dump("a-");
            $user->setUserName($userData['userName']);
            var_dump("-b");
            $user->setEMail($userData['email']);
            $user->setCountry($userData['country']);
            $user->setNativeCurrency(
                $currencyRepository->findOneBy(['symbol' => $userData['nativeCurrency']]) 
            );
            $user->setDisplayCurrency(
                $currencyRepository->findOneBy(['symbol' => $userData['displayCurrency']])
            );

            $manager->persist($user);
        }
    }
}
