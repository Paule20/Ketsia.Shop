<?php

namespace App\Tests\Unit;

use App\Entity\Order;
use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testOrderStatus(): void
    {
        $order = new Order();

        $order->setStatus('pending');

        $this->assertEquals('pending', $order->getStatus());
    }
}