<?php

namespace App\Services;

use Nette;
use Nette\Security\IIdentity;
use Nette\Security\SimpleIdentity;

class CustomAuthenticator implements Nette\Security\Authenticator, Nette\Security\IdentityHandler
{
    private $database;
    private $passwords;

    public function __construct(
        Nette\Database\Context $database,
        Nette\Security\Passwords $passwords
    ) {
        $this->database = $database;
        $this->passwords = $passwords;
    }

    public function authenticate(string $email, string $password): SimpleIdentity
    {
        $row = $this->database->table('users')
            ->where('email', $email)
            ->fetch();

        if (!$row) {
            throw new Nette\Security\AuthenticationException('User not found.');
        }

        if (!$this->passwords->verify($password, $row->password)) {
            throw new Nette\Security\AuthenticationException('Invalid password.');
        }

        $rolesRow = $this->database->table('roles')
            ->where('id', $row->role_id)
            ->fetch();

        return new SimpleIdentity(
            $row->id,
            $rolesRow->name,
            [
                'name' => $row->username,
                'email' => $row->email,
            ]
        );
    }

    public function sleepIdentity(IIdentity $identity): IIdentity
    {
        return $identity;
    }

    public function wakeupIdentity(IIdentity $identity): ?IIdentity
    {
        $userId = $identity->getId();

        $roleId = $this->database->table('users')
            ->where('id', $userId)
            ->fetch();

        $role = $this->database->table('roles')
            ->where('id', $roleId->role_id)
            ->fetch();

        $identity->setRoles([$role->name]);

        return $identity;
    }
}
