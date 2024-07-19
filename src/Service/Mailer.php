<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;
use Symfony\Contracts\Translation\TranslatorInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class Mailer
{
    public function __construct(
        private readonly TranslatorInterface $translator
    ) {
    }

    public function getPasswordRequestMail(ResetPasswordToken $resetToken, User $user): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address('no-reply@proglab.com', 'Proglab Bot'))
            ->to($user->getEmail())
            ->subject($this->translator->trans('Your password reset request'))
            ->htmlTemplate('reset_password/email_update.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);
    }


    public function getNewPasswordRequestMail(ResetPasswordToken $resetToken, User $user): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address('no-reply@proglab.com', 'Proglab Bot'))
            ->to($user->getEmail())
            ->subject($this->translator->trans('Create your new password'))
            ->htmlTemplate('reset_password/email_new.html.twig')
            ->context([
                'resetToken' => $resetToken,
                'title' => 'Create your new password',
            ]);
    }

    public function getVerifyEmailMail(User $user): TemplatedEmail
    {
        return (new TemplatedEmail())
            ->from(new Address('no-reply@proglab.com', 'Proglab Bot'))
            ->to($user->getEmail())
            ->subject('Please Confirm your Email')
            ->htmlTemplate('registration/confirmation_email.html.twig');
    }

}