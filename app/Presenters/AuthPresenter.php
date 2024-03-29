<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\UserFacade;
use Nette;
use Nette\Application\UI\Form;

final class AuthPresenter extends Nette\Application\UI\Presenter
{
    private $facade;
    private $httpRequest;

    public function __construct(UserFacade $facade, Nette\Http\Request $httpRequest)
    {
        $this->facade = $facade;
        $this->httpRequest = $httpRequest;
    }

    public function startup()
    {
        parent::startup();

        if (
            $this->getUser()->isLoggedIn() &&
            $this->facade->checkUserVerification($this->getUser()->getIdentity()->email) &&
            $this->getAction() !== 'logout'
        ) {
            $this->redirect('Users:');
        }
    }

    protected function createComponentSignInForm(): Form
    {
        $form = new Form;
        $form->addText('email', 'Email:')
            ->setRequired('Please enter your email.');

        $form->addPassword('password', 'Password:')
            ->setRequired('Please enter your password.');

        $form->addSubmit('signin', 'SignIn');

        $form->onSuccess[] = [$this, 'signInFormSucceeded'];

        return $form;
    }

    public function signInFormSucceeded(Form $form): void
    {
        try {
            if (!$this->facade->checkUserVerification($form->getValues()->email)) {
                $this->redirect('Auth:Verification', ['email' => $form->getValues()->email]);
            }

            $this->getUser()->login(
                $form->getValues()->email,
                $form->getValues()->password
            );

            $this->redirect('Users:');
        } catch (Nette\Security\AuthenticationException $e) {
            $form->addError($e->getMessage());
        }
    }

    protected function createComponentSignUpForm(): Form
    {
        $form = new Form;
        $form->addText('username', 'Username:')
            ->setRequired('Please enter your username.');

        $form->addText('email', 'Email:')
            ->setRequired('Please enter your email.');

        $form->addPassword('password', 'Password:')
            ->setRequired('Please enter your password.');

        $form->addSubmit('signup', 'SignUp');

        $form->onSuccess[] = [$this, 'signUpFormSucceeded'];

        return $form;
    }

    public function signUpFormSucceeded(Form $form): void
    {
        try {
            $this->facade->registerUser($form->getValues());

            $this->redirect('Auth:SignIn');
        } catch (Nette\Neon\Exception $e) {
            $form->addError($e->getMessage());
        }
    }

    protected function createComponentVerificationForm(): Form
    {
        $email = $this->httpRequest->getQuery('email');

        $form = new Form;

        $form->addText('email')->setDefaultValue($email);

        $form->addText('verificationCode', 'Verification Code:')
            ->setRequired('Please enter verification code from email.');

        $form->addSubmit('confirm', 'Confirm');

        $form->onSuccess[] = [$this, 'verificationFormSucceeded'];

        return $form;
    }

    public function verificationFormSucceeded(Form $form): void
    {
        try {
            $result = $this->facade->verifyUser($form->getValues());

            if (!$result['success']) {
                throw new Nette\Neon\Exception($result['message']);
            }

            $this->redirect('Users:');
        } catch (Nette\Neon\Exception $e) {
            $form->addError($e->getMessage());
        }
    }

    public function actionLogout(): void
    {
        $this->getUser()->logout(true);
        $this->redirect('Homepage:');
    }
}