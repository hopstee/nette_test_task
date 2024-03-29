<?php

declare(strict_types=1);

namespace App\Presenters;

use App\Models\UserFacade;
use Nette;
use Nette\Application\UI\Form;
use Nette\Neon\Exception;

final class UsersPresenter extends Nette\Application\UI\Presenter
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

        if (!$this->getUser()->isLoggedIn()) {
            $this->redirect('Auth:SignIn');
        }
    }

    public function renderDefault()
    {
        $this->template->users = $this->facade->getUsers();
    }

    public function actionDelete(int $id)
    {
        $this->facade->deleteUser($id);
        $this->flashMessage('The user has been deleted');
        $this->redirect('default');
    }

    public function actionAdd(): void
    {
        $recipeForm = $this['userForm'];
        $recipeForm->onSuccess[] = [$this, 'processAddingUser'];
    }

    public function processAddingUser(Form $form, array $data): void
    {
        try {
            $this->facade->addUser($data);
            $this->redirect('default');
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage());
        }
    }

    public function actionEdit(int $userId): void
    {
        try {
            $user = $this->facade->getUser($userId);
            $userForm = $this['userForm'];
            $userForm->setDefaults([
                'username' => $user->username,
                'email' => $user->email,
            ]);
            $userForm->onSuccess[] = [$this, 'processEditingUser'];
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage());
            $this->redirect('default');
        }

    }

    public function processEditingUser(Form $form, array $data): void
    {
        $id = (int) $this->getParameter('userId');

        try {
            $this->facade->editUser($id, $data);
        } catch (Exception $e) {
            $form->addError($e->getMessage());
        }

        $this->redirect('default');
    }

    protected function createComponentUserForm(): Form
    {
        $form = new Form();
        $form->addText('username', 'Username')
            ->setRequired('Please enter username.');
        $form->addText('email', 'Email')
            ->setRequired('Please enter email.');

        if ($this->getAction() === 'add') {
            $form->addSubmit('add', 'Add');
            $form->addText('password', 'Password')
                ->setRequired('Please enter password.');
        }

        if ($this->getAction() === 'edit') {
            $form->addSubmit('edit', 'Edit');
            $form->addText('password', 'Password');
        }

        return $form;
    }
}
