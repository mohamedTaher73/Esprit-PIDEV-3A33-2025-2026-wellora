<?php

namespace Tests\Unit\Entity;

use App\Entity\Patient;
use PHPUnit\Framework\TestCase;
use DateTime;

class UserTest extends TestCase
{
    private Patient $user;

    protected function setUp(): void
    {
        $this->user = new Patient();
    }

    public function testEmailGetterAndSetter(): void
    {
        $email = 'test@example.com';
        
        $result = $this->user->setEmail($email);
        
        $this->assertSame($email, $this->user->getEmail());
        $this->assertInstanceOf(Patient::class, $result);
    }

    public function testFirstNameGetterAndSetter(): void
    {
        $firstName = 'John';
        
        $result = $this->user->setFirstName($firstName);
        
        $this->assertSame($firstName, $this->user->getFirstName());
        $this->assertInstanceOf(Patient::class, $result);
    }

    public function testLastNameGetterAndSetter(): void
    {
        $lastName = 'Doe';
        
        $result = $this->user->setLastName($lastName);
        
        $this->assertSame($lastName, $this->user->getLastName());
        $this->assertInstanceOf(Patient::class, $result);
    }

    public function testFullObjectInitialization(): void
    {
        $email = 'john.doe@example.com';
        $firstName = 'John';
        $lastName = 'Doe';
        
        $this->user
            ->setEmail($email)
            ->setFirstName($firstName)
            ->setLastName($lastName);
        
        $this->assertEquals($email, $this->user->getEmail());
        $this->assertEquals($firstName, $this->user->getFirstName());
        $this->assertEquals($lastName, $this->user->getLastName());
    }
}
