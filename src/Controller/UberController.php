<?php

namespace App\Controller;

use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\HttpFoundation\Response;
use App\Entity\User;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;

class UberController extends AbstractController
{
    /**
     * @Route("/", name="main")
     */
    public function main(AuthenticationUtils $authenticationUtils): Response
    {
        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('main.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/login", name="login", methods={"POST"})
     */
    public function login(AuthenticationUtils $authenticationUtils): Response
    {
        // if ($this->getUser()) {
        //     return $this->redirectToRoute('target_path');
        // }

        // get the login error if there is one
        $error = $authenticationUtils->getLastAuthenticationError();
        // last username entered by the user
        $lastUsername = $authenticationUtils->getLastUsername();

        return $this->render('main.html.twig', ['last_username' => $lastUsername, 'error' => $error]);
    }

    /**
     * @Route("/logout", name="logout")
     */
    public function logout()
    {
        throw new \Exception('This method can be blank - it will be intercepted by the logout key on your firewall');
    }

    /**
     * @Route("/register", name="register", methods={"POST"})
     */
    public function register(Request $request, ObjectManager $om, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();
        $email = $request->request->get("email");
        $password = $request->request->get("password");

        $errors = [];

        $encodedPassword = $passwordEncoder->encodePassword($user, $password);
        $user->setEmail($email);
        $user->setPassword($encodedPassword);
        try
        {
            $om->persist($user);
            $om->flush();
            $token = new UsernamePasswordToken($user, null, 'main', $user->getRoles());
            $this->container->get('security.token_storage')->setToken($token);
            $this->container->get('session')->set('_security_main', serialize($token));
        }
        catch(UniqueConstraintViolationException $e)
        {
            $errors[] = "The email provided already has an account!";
        }
        catch(\Exception $e)
        {
            $errors[] = "Unable to save new user at this time.";
        }
        return $this->render('main.html.twig', ['error' => implode(PHP_EOL, $errors)]);
    }

    /**
     * @Route("/feed", name="feed")
     */
    public function feed(): \Symfony\Component\HttpFoundation\Response
	{
		$raw = \App\Service\Feeder::get();
        return $this->json($this->getFrequency($raw));
//        return $this->json(['xml' => $raw, 'frequency' => $this->getFrequency($raw)]);
    }

    private function getFrequency(string $text): array {
		// TODO: static?
		$ignored = array_flip(["the", "be", "to", "of", "and", "a", "in", "that", "have", "I", "it", "for", "not", "on", "with", "he", "as",
			"you", "do", "at", "this", "but", "his", "by", "from", "they", "we", "say", "her", "she", "or", "an", "will", "my",
			"one", "all", "would", "there", "their", "what", "so", "up", "out", "if", "about", "who", "get", "which", "go", "me"]);

		$crawler = new Crawler($text);
		$crawler = $crawler->filter('title, summary');

		$frequency = [];
		foreach ($crawler->getIterator() as $node) {
			$text = strip_tags($node->nodeValue);
			$text = strtolower($text);
			$words = explode(' ', $text);
			$words = preg_replace('/[^A-Z\-\'a-z]/m', '', $words);
			$words = array_filter($words, function($x) {return mb_strlen($x) > 1;});

			foreach ($words as $word) {
				if (array_key_exists($word, $ignored)) {
					continue;
				}
				if (array_key_exists($word, $frequency)) {
					$frequency[$word]++;
				} else {
					$frequency[$word] = 1;
				}
			}
		}

		arsort($frequency, SORT_NUMERIC);
		$frequency = array_slice($frequency, 0, 20);

		return $frequency;
	}
}
