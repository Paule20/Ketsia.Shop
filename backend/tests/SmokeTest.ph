<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class SmokeTest extends WebTestCase
{
    public function testApplicationLoads(): void
    {
        $client = static::createClient();

        $this->assertTrue(true);
    }
}