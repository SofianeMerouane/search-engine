<?php
namespace App\DataFixtures;

use App\Entity\Product;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Faker\Factory;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        $faker = Factory::create('fr_FR');
        // create 20 products! Bam!
        for ($i = 0; $i < 600000; $i++) {
            $product = new Product();

            $product->setLabel($faker->company);
            $product->setCity($faker->city);
            $product->setContry($faker->country);
            $product->setPrice($faker->randomFloat());

            $manager->persist($product);
        }

        $manager->flush();
    }
}