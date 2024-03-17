<?php

namespace App\DataFixtures;

use App\Entity\Book;
use App\Entity\Booking;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    private $passwordEncoder;

    public function __construct(UserPasswordHasherInterface $passwordEncoder)
    {
        $this->passwordEncoder = $passwordEncoder;
    }

    public function load(ObjectManager $manager): void
    {
        $this->createBookFixture($manager);
        $this->createUserFixture($manager);
        $this->createBookingFixture($manager);
    }

    public function createBookFixture(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for($i=0; $i<10; $i++){
            $book = new Book();

            $book->setTitle(ucfirst($faker->words(3, true)))
                ->setDescription($faker->paragraph(2))
                ->setAuthor($faker->name())
                ->setCategory($faker->word())
                ->setPublishedAt(\DateTimeImmutable::createFromMutable($faker->dateTime()));

            $manager->persist($book);
            $this->addReference('book_' . $i, $book);
        }

        $manager->flush();
    }

    public function createBookingFixture(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for($i=1; $i<3; $i++){
            $booking = new Booking();
            $userIndex = $i % 2 + 1;

            $booking->setUser($this->getReference('user_' . $userIndex))
                ->setBook($this->getReference('book_' . $i))
                ->setStartDate($faker->dateTimeBetween('-6 months', 'now'))
                ->setEndDate($faker->dateTimeBetween('now', '+6 months'))
                ->setStatus($faker->randomElement(['active', 'cancelled']));

            $manager->persist($booking);
        }

        $manager->flush();
    }

    public function createUserFixture(ObjectManager $manager): void
    {
        $faker = Factory::create('fr_FR');
        for($i=1; $i<3; $i++){
            $user = new User();

            $user->setEmail($faker->email)
                ->setPassword($this->passwordEncoder->hashPassword($user, 'password'))
                ->setRoles(['ROLE_USER']);

            $manager->persist($user);
            $this->addReference('user_' . $i, $user);
        }

        $manager->flush();
    }
}
