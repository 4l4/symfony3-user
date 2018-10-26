<?php
/**
 * Created by PhpStorm.
 * User: Alex
 * Date: 26/10/18
 */

namespace AppBundle\Controller;


use AppBundle\Entity\User;
use AppBundle\Form\User\RegistrationType;
use AppBundle\Form\User\RequestResetPasswordType;
use AppBundle\Form\User\ResetPasswordType;
use AppBundle\Security\LoginFormAuthenticator;
use AppBundle\Service\Mailer;
use AppBundle\Service\TokenGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Guard\GuardAuthenticatorHandler;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Exception\ValidatorException;

/**
 * @Route("/user", name="user_")
 */
class UserController extends AbstractController
{
    const DOUBLE_OPT_IN = true;

    /**
     * @Route("/register", name="register")
     * @param Request $request
     * @param TokenGenerator $tokenGenerator
     * @param UserPasswordEncoderInterface $encoder
     * @param Mailer $mailer
     * @param TranslatorInterface $translator
     * @throws \Throwable
     * @return Response
     */
    public function register(Request $request, TokenGenerator $tokenGenerator, UserPasswordEncoderInterface $encoder,
                             Mailer $mailer, TranslatorInterface $translator)
    {
        $form = $this->createForm(RegistrationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();

            try {

                $user->setPassword($encoder->encodePassword($user, $user->getPassword()));
                $token = $tokenGenerator->generateToken();
                $user->setToken($token);
                $user->setIsActive(false);

                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                if (self::DOUBLE_OPT_IN) {
                    $mailer->sendActivationEmailMessage($user);
                    return $this->redirect($this->generateUrl('homepage'));
                }

//                return $this->redirect($this->generateUrl('user_activate', ['token' => $token]));

            } catch (ValidatorException $exception) {

            }
        }

        return $this->render('user/register.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/activate/{token}", name="activate")
     * @param $request Request
     * @return Response
     * @throws \Exception
     */
    public function activate(Request $request)
    {
        $email = $request->get('email');

        $user = $this->getUserRepository()->findOneBy(['email'=>$email]);

        if($user == null){
            throw new \Exception('User not found');
        }

        if($user->getToken() == null){
            $this->redirect($this->generateUrl('security_login'));
        }

        $user->setIsActive(true);
        $user->setToken(null);
        $user->setActivatedAt(new \DateTime());

        $em = $this->getDoctrine()->getManager();
        $em->persist($user);
        $em->flush();

        return $this->redirect($this->generateUrl('security_login'));
    }

    /**
     * @Route("/request-password-reset", name="request_password_reset")
     * @param Request $request
     * @param TokenGenerator $tokenGenerator
     * @param Mailer $mailer
     * @throws \Throwable
     * @return Response
     */
    public function requestPasswordReset(Request $request, TokenGenerator $tokenGenerator, Mailer $mailer)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        $form = $this->createForm(RequestResetPasswordType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            try {
                $repository = $this->getDoctrine()->getRepository(User::class);

                /** @var User $user */
                $user = $repository->findOneBy(['email' => $form->get('_username')->getData(), 'isActive' => true]);
                if (!$user) {
                    return $this->render('user/request-password-reset.html.twig', [
                        'form' => $form->createView(),
                    ]);
                }

                $token = $tokenGenerator->generateToken();
                $user->setToken($token);
                $em = $this->getDoctrine()->getManager();
                $em->persist($user);
                $em->flush();

                $mailer->sendResetPasswordEmailMessage($user);

                return $this->redirect($this->generateUrl('homepage'));
            } catch (ValidatorException $exception) {

            }
        }

        return $this->render('user/request-password-reset.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/reset-password/{token}", name="reset_password")
     * @param $request Request
     * @param UserPasswordEncoderInterface $encoder
     * @return Response
     * @throws \Exception
     */
    public function resetPassword(Request $request, UserPasswordEncoderInterface $encoder)
    {
        if ($this->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            return $this->redirect($this->generateUrl('homepage'));
        }

        $email = $request->get('email');

        $user = $this->getUserRepository()->findOneBy(['email'=>$email]);

        if($user == null){
            throw new \Exception('User not found');
        }

        $form = $this->createForm(ResetPasswordType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var User $user */
            $user = $form->getData();
            $user->setPassword($encoder->encodePassword($user, $user->getPassword()));
            $user->setToken(null);

            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();


            return $this->redirect($this->generateUrl('security_login'));
        }

        return $this->render('user/password-reset.html.twig', ['form' => $form->createView()]);
    }

    public function getUserRepository()
    {
        return $this->getDoctrine()->getRepository(User::class);
    }

}
