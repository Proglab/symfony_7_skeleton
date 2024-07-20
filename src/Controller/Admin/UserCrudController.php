<?php

declare(strict_types=1);

namespace App\Controller\Admin;

use App\Entity\User;
use App\Repository\ResetPasswordRequestRepository;
use App\Service\Mailer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\KeyValueStore;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\ArrayField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Security\Http\Impersonate\ImpersonateUrlGenerator;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

class UserCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly ImpersonateUrlGenerator $impersonateUrlGenerator,
        private readonly ResetPasswordHelperInterface $resetPasswordHelper,
        private readonly MailerInterface $mailerInterface,
        private readonly AdminUrlGenerator $adminUrlGenerator,
        private readonly ResetPasswordRequestRepository $resetPasswordRequestRepository,
        private readonly Mailer $mailer,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return User::class;
    }

    public function configureFields(string $pageName): iterable
    {
        if (Crud::PAGE_INDEX === $pageName) {
            return [
                IdField::new('id'),
                TextField::new('email'),
                BooleanField::new('verified'),
                BooleanField::new('active'),
                ArrayField::new('roles'),
            ];
        }

        return [
            TextField::new('email'),
            BooleanField::new('verified'),
            BooleanField::new('active'),
            ChoiceField::new('roles')->allowMultipleChoices(true)->setChoices([
                'User'            => 'ROLE_USER',
                'Admin'           => 'ROLE_ADMIN',
                'Can switch user' => 'ROLE_ALLOWED_TO_SWITCH',
            ]),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        $switch = Action::new('switch', 'Connect as this user', 'fa fa-toggle-on')
            ->linkToCrudAction('switch')->displayIf(fn ($entity) => $entity->getUserIdentifier() !== $this->getUser()->getUserIdentifier());

        $sendNewPassword = Action::new('sendNewPassword', 'Send new password', 'fa fa-envelope')
            ->linkToCrudAction('sendNewPassword')->displayIf(fn ($entity) => $entity->getUserIdentifier() !== $this->getUser()->getUserIdentifier());

        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setLabel('Create new User');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-pen-to-square');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash text-danger');
            })
            ->add(Crud::PAGE_INDEX, $switch)
            ->add(Crud::PAGE_INDEX, $sendNewPassword)
            ->setPermission('switch', 'CAN_SWITCH_USER')
        ;
    }

    public function switch(AdminContext $adminContext): Response
    {
        /** @var User $user */
        $user = $adminContext->getEntity()->getInstance();

        return $this->redirect($this->impersonateUrlGenerator->generateImpersonationPath($user->getUserIdentifier()));
    }

    public function sendNewPassword(AdminContext $adminContext): Response
    {
        $user = $adminContext->getEntity()->getInstance();

        $resetRequest = $this->resetPasswordRequestRepository->findOneBy(['user' => $user]);
        if ($resetRequest) {
            $this->resetPasswordRequestRepository->removeResetPasswordRequest($resetRequest);
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('error', 'An error occurred while generating the password reset token.');

            return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->generateUrl());
        }

        $email = $this->mailer->getPasswordRequestMail($resetToken, $user);
        try {
            $this->mailerInterface->send($email);
        } catch (TransportExceptionInterface $e) {
            $this->addFlash('error', 'An error occurred while sending the email.');

            return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->generateUrl());
        }
        $this->addFlash('success', 'The new password has been sent to the user.');

        return $this->redirect($this->adminUrlGenerator->setAction(Action::INDEX)->generateUrl());
    }

    /**
     * @param User $entityInstance
     */
    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $entityInstance->setPassword(
            uniqid('password_', true),
        );
        parent::persistEntity($entityManager, $entityInstance);

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($entityInstance);
            $email      = $this->mailer->getNewPasswordRequestMail($resetToken, $entityInstance);
            $this->mailerInterface->send($email);
        } catch (ResetPasswordExceptionInterface $e) {
            $this->addFlash('error', 'An error occurred while generating the password reset token.');
        } catch (TransportExceptionInterface $e) {
            $this->addFlash('error', 'An error occurred while sending the email.');
        }

        $this->addFlash('success', 'The new password has been sent to the user.');
    }

    public function delete(AdminContext $context): KeyValueStore|Response
    {
        $user         = $context->getEntity()->getInstance();
        $resetRequest = $this->resetPasswordRequestRepository->findOneBy(['user' => $user]);
        if ($resetRequest) {
            $this->resetPasswordRequestRepository->removeResetPasswordRequest($resetRequest);
        }

        return parent::delete($context);
    }
}
