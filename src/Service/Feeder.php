<?php

namespace App\Service;

class Feeder
{
	// TODO: last modified; errhandling
	public static function get(): string {
		$client = \Symfony\Component\HttpClient\HttpClient::create();
		$resp = $client->request('GET', 'http://theregister.co.uk/software/headlines.atom');
		return $resp->getContent();
	}
}
