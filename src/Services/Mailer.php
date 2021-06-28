<?php


namespace App\Services;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mailer\MailerInterface;


class Mailer
{
    private $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }


    public function testMailerService(): string
    {
        return "Hello from testMailerService";
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendMail(string $renderedView, string $adresse, string $subject, string $env)
    {
        /*if ('dev' !== $env) {
            $email = (new Email())
                ->from(your@email.com)
                ->to($adresse)
                ->subject($subject)
                ->html($renderedView);

            $this->mailer->send($email);
        }*/
    }

    /**
     * @throws TransportExceptionInterface
     */
    public function sendWelcomeMessage(User $user)
    {

        /**
         *
        // email body
        $body = "<!DOCTYPE>
        <html lang='es'>
        <body>
        <h2>Hello from Symfony.</h2>
        <h3>Nice to meet you {$user->getName()}! ❤</h3>
        <p>Your authentication code is : <strong>{$user->getEmailToken()}</strong></p>
        <p>Thank you for subscribing. Please confirm your email by clicking on the following link and insert your authentication code</p>
        <a href=https://localhost:8000/auth/activate> Click here</a>
        <p>If link doesnt work use this link href=https://localhost:8000/auth/activate</p>
        <p>If you have not requested this code please do not reply to this email</p>
        </body>
        </html>";


        $email = (new Email())
            ->from('backend-symfony@example.com')
            ->to($user->getEmail())
            ->subject('Welcome to the Space Bar!')
            //->text('Sending emails is fun again!')
            ->html($body);

        $mailer->send($email);


        $email = (new TemplatedEmail())
            ->from(new Address('alienmailcarrier@example.com', 'The Space Bar'))
            ->to(new Address($user->getEmail(), $user->getName()))
            ->subject('Welcome to the Space Bar!')
            ->htmlTemplate('email/welcome.html.twig')
            ->context([
                // You can pass whatever data you want
                //'user' => $user,
            ]);
        $this->mailer->send($email);
         */
    }

}