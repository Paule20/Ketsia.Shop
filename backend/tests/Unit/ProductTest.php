<?php

namespace App\Tests\Unit;

use App\Entity\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{
    public function testProductCreation(): void
    {
        $product = new Product();

        $product->setName('T-Shirt');
        $product->setPrice(29.99);

        $this->assertEquals('T-Shirt', $product->getName());
        $this->assertEquals(29.99, $product->getPrice());
    }
}