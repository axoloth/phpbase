<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class LocaleControllerTest extends WebTestCase
{
    public function testUpdate()
    {
        $client = static::createClient();
        $client->request('GET', '/locale/update?lang=fr&amp;route=front_home');
        $client->getResponse()->isRedirect();
        $client->followRedirect();
        $crawler = $client->getCrawler();

        $classes = $crawler->selectLink('Français')->parents()->attr('class');
        $this->assertContains('active', $classes);

        $client->request('GET', '/locale/update?lang=en&amp;route=front_home');
        $client->getResponse()->isRedirect();
        $client->followRedirect();
        $crawler = $client->getCrawler();
        $classes = $crawler->selectLink('English')->parents()->attr('class');
        $this->assertContains('active', $classes);
    }
}