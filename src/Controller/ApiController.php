<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class ApiController extends AbstractController
{
    /**
     * @Route("/feed", name="feeder")
     */
    public function feed(\Symfony\Component\HttpFoundation\Request $request, \App\Service\Feeder $feeder): \Symfony\Component\HttpFoundation\Response
	{
		$raw = $feeder::get();
        return $this->json(['xml' => $raw, 'frequency' => $this->getFrequency($raw)]);
    }

    private function getFrequency(string $text): array {
		// TODO: static?
		$ignored = array_flip(["the", "be", "to", "of", "and", "a", "in", "that", "have", "I", "it", "for", "not", "on", "with", "he", "as",
			"you", "do", "at", "this", "but", "his", "by", "from", "they", "we", "say", "her", "she", "or", "an", "will", "my",
			"one", "all", "would", "there", "their", "what", "so", "up", "out", "if", "about", "who", "get", "which", "go", "me"]);

		$crawler = new \Symfony\Component\DomCrawler\Crawler($text);
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
