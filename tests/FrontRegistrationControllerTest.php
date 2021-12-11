<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\InputFormField;

class FrontRegistrationControllerTest extends WebTestCase
{
    public function testRegisterAndConfirmEmail()
    {
        $client = static::createClient();
        /**
         * @var Crawler $crawler
         */
        $crawler = $client->request('GET', '/register');
        
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Inscription');

        // Fill registration form and submit
        $buttonCrawlerNode = $crawler->selectButton('button_submit');
        $form = $buttonCrawlerNode->form();
        $client->enableProfiler();
        $client->submit($form, [
            "registration_form[email]" => 'martin3129@gmail.com',
            "registration_form[firstname]" => 'Martin',
            "registration_form[lastname]" => 'GILBERT',
            "registration_form[plainPassword][first]" => 'password',
            "registration_form[plainPassword][second]" => 'password',
            "registration_form[agreeTerms]" => true,
        ]);
        $this->assertTrue($client->getResponse()->isRedirect());
        
        // Checks that an email was sent
        $this->assertEmailCount(1);

        
        $email = $this->getMailerMessage();
        $confirmationUrl = $email->getContext()['action_url'] ?? '';
        $this->assertEmailHeaderSame($email, 'to', 'martin3129@gmail.com');
        $this->assertEmailHeaderSame($email, 'subject', '🔔 Bienvenue Martin GILBERT');
        
        // Go to login page after redirect
        $client->followRedirect();
        $crawler = $client->getCrawler();
        $this->assertSelectorTextContains('#flash_message', "Alerte info : Un e-mail a été envoyé à l'adresse martin3129@gmail.com. Il contient un lien d'activation sur lequel il vous faudra cliquer afin d'activer votre compte.");

        // Activate account and login
        $client->request('GET', $confirmationUrl);
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $crawler = $client->getCrawler();
        $this->assertContains("Alerte succès : Félicitations Martin GILBERT, votre compte est maintenant activé.", $crawler->filter('#flash_message')->children('div')->first()->text());
        
        // Logout and redirect to homepage
        $client->request('GET', '/logout');
        $this->assertTrue($client->getResponse()->isRedirect());
        $client->followRedirect();
        $this->assertSame("/", $client->getRequest()->getPathInfo());
    }
}