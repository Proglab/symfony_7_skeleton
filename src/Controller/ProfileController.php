<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\ProfileFormType;
use App\Form\UpdatePasswordFormType;
use App\Repository\UserRepository;
use App\Security\EmailVerifier;
use App\Service\Mailer;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Mime\Address;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class ProfileController extends AbstractController
{
    public function __construct(private readonly RequestStack $requestStack, private readonly UserRepository $userRepository, private readonly UserPasswordHasherInterface $userPasswordHasher, private readonly Mailer $mailer)
    {
    }

    #[Route(path: '/profile/edit', name: 'app_profile')]
    public function profile(): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $form = $this->createForm(ProfileFormType::class, $user);
        $form->handleRequest($this->requestStack->getCurrentRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            $user->setVerified(false);
            $this->userRepository->save($user, true);
            $this->addFlash('success', 'Profile updated successfully');
        }

        return $this->render('profile/profile.html.twig', [
            'profileForm' => $form->createView(),
        ]);

    }

    #[Route(path: '/profile/update_password', name: 'app_update_password')]
    public function update_password(): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UpdatePasswordFormType::class);
        $form->handleRequest($this->requestStack->getCurrentRequest());
        if ($form->isSubmitted() && $form->isValid()) {
            if($this->userPasswordHasher->isPasswordValid($user, $form->get('currentPassword')->getData())) {
                $user->setPassword(
                    $this->userPasswordHasher->hashPassword(
                        $user,
                        $form->get('plainPassword')->getData()
                    )
                );

                $this->addFlash('success', 'Current password is updated successfully');
                $this->userRepository->save($user, true);
                return $this->redirectToRoute('app_login');

            }

            $this->addFlash('error', 'Current password is incorrect');
            return $this->redirectToRoute('app_update_password');
        }

        return $this->render('profile/update_password.html.twig', [
            'updatePasswordForm' => $form->createView(),
        ]);
    }

    #[Route(path: '/profile/verify_email', name: 'app_verify_send_email')]
    public function email_verifier(EmailVerifier $emailVerifier): Response
    {
        /** @var User $user */
        $user = $this->getUser();
        $emailVerifier->sendEmailConfirmation('app_verify_email', $user,
            $this->mailer->getVerifyEmailMail($user)
        );

        return $this->redirectToRoute('app_profile');
    }
}
