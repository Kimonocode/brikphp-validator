<?php

namespace Brikphp\Validator\Tests;

use Brikphp\Validator\Validator;
use GuzzleHttp\Psr7\ServerRequest;
use PHPUnit\Framework\TestCase;

class UserValidator extends Validator
{
    protected function getSchema(): array
    {
        return [
            'username' => [
                'type' => 'string',
                'min' => 3,
                'max' => 20,
                'required' => true,
                'regex' => '/^[a-zA-Z0-9_]+$/',
            ],
            'password' => [
                'type' => 'string',
                'min' => 8,
                'required' => true,
                'confirm' => 'passwordConfirmation',
            ],
        ];
    }
}

class ValidatorTest extends TestCase
{
    public function testValidationSuccess()
    {
        $request = (new ServerRequest('POST', '/test', []))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'username' => 'user123',
                'password' => 'securePassword',
                'passwordConfirmation' => 'securePassword'
            ]);

        $validator = new UserValidator();
        $isValid = $validator->validate($request->getParsedBody(), $request);

        $this->assertTrue($isValid);
        $this->assertEmpty($validator->getErrors());
    }

    public function testValidationFailure()
    {
        $request = (new ServerRequest('POST', '/test', []))
            ->withHeader('Content-Type', 'application/x-www-form-urlencoded')
            ->withParsedBody([
                'username' => 'us', // Trop court
                'password' => 'securePassword',
                'passwordConfirmation' => 'differentPassword' // Ne correspond pas
            ]);

        $validator = new UserValidator();
        $isValid = $validator->validate($request->getParsedBody(), $request);

        $this->assertFalse($isValid);
        $errors = $validator->getErrors();

        $this->assertArrayHasKey('username', $errors);
        $this->assertArrayHasKey('password', $errors);
        $this->assertEquals("La longueur minimale est de 3 caractères.", $errors['username'][0]);
        $this->assertEquals("Les champs 'password' et 'passwordConfirmation' ne correspondent pas.", $errors['password'][0]);
    }
}
