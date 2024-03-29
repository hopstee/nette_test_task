<?php

declare(strict_types=1);

namespace App\Models;

use Nette;
use Nette\Neon\Exception;
use App\Services\MailService;

final class UserFacade
{
    private $tableName = 'users';
    private $database;
    private $passwords;

    public function __construct(Nette\Database\Context $database)
    {
        $this->database = $database;
        $this->passwords = new Nette\Security\Passwords();
    }

    public function getUsers(): \Nette\Database\Table\Selection
    {
        return $this->database->table($this->tableName)->order('id DESC');
    }

    public function getUser(int $id): \Nette\Database\Table\ActiveRow
    {
        $result = $this->database->table($this->tableName)->select('id, username, email')->get($id);

        if (!$result) {
            throw new Exception('User not found.');
        }

        return $result;
    }

    public function addUser(array $params): \Nette\Database\Table\ActiveRow
    {
        $row = $this->database->table('users')
            ->where('email', $params['email'])
            ->fetch();

        if ($row) {
            throw new Exception('User already exists.');
        }

        $passwordHash = $this->passwords->hash($params['password']);

        return $this->database->table($this->tableName)->insert([
            'username' => $params['username'],
            'email' => $params['email'],
            'password' => $passwordHash,
            'email_verification_status' => true
        ]);
    }

    public function registerUser(object $params): \Nette\Database\Table\ActiveRow
    {
        $row = $this->database->table('users')
            ->where('email', $params->email)
            ->fetch();

        if ($row) {
            throw new Exception('User already exists.');
        }

        $passwordHash = $this->passwords->hash($params->password);
        $emailVerificationCode = rand(100000, 999999);
        $emailVerificationExpire = date('Y-m-d H:i',strtotime('+1 hour',strtotime(date("Y-m-d H:i"))));

        $newUserData = [
            'username' => $params->username,
            'email' => $params->email,
            'password' => $passwordHash,
            'email_verification_code' => $emailVerificationCode,
            'email_verification_expire' => $emailVerificationExpire
        ];

        MailService::sendMail(
            $params->email,
            "Verification code",
            "It is your verification code $emailVerificationCode"
        );

        return $this->database->table($this->tableName)->insert($newUserData);
    }

    public function verifyUser(object $params): array
    {
        $userData = $this->database->table($this->tableName)->where('email', $params->email)->fetch();

        $dateTimeNow = date("Y-m-d H:i");

        if ($userData->email_verification_expire < $dateTimeNow) {
            $emailVerificationCode = rand(100000, 999999);
            $emailVerificationExpire = date('Y-m-d H:i',strtotime('+1 hour',strtotime(date("Y-m-d H:i"))));

            $this->database->table($this->tableName)
                ->where('email', $params->email)
                ->update([
                    'email_verification_code' => $emailVerificationCode,
                    'email_verification_expire' => $emailVerificationExpire
                ]);

            MailService::sendMail(
                $params->email,
                "Verification code",
                "It is your verification code $emailVerificationCode"
            );

            return [
                'success' => false,
                'message' => 'Verification code expires. Please check your email for new verification code.'
            ];
        }

        if ((string) $userData->email_verification_code !== (string) $params->verificationCode) {
            return [
                'success' => false,
                'message' => 'Wrong verification code. Please try again.'
            ];
        }

        $this->database->table($this->tableName)->where('email', $params->email)->update([
            'email_verification_code' => null,
            'email_verification_status' => true,
            'email_verification_expire' => null,
        ]);

        return [
            'success' => true
        ];
    }

    public function checkUserVerification(string $email): bool
    {
        $userData = $this->database->table($this->tableName)->where('email', $email)->fetch();

        if (!$userData) {
            throw new Nette\Security\AuthenticationException('User not found.');
        }

        return $userData->email_verification_status;
    }

    public function editUser(int $id, array $params): int
    {
        $params = [
            'username' => $params['username'],
            'email' => $params['email']
        ];

        if (array_key_exists('password', $params) && $params['password'] !== '') {
            $passwordHash = $this->passwords->hash($params['password']);
            $params['password'] = $passwordHash;
        }

        return $this->database->table($this->tableName)->where('id', $id)->update($params);
    }

    public function deleteUser(int $id): int
    {
        return $this->database->table($this->tableName)->where('id', $id)->delete();
    }
}
