<?php

namespace App\Tests\Back\Panther;

use Symfony\Component\Panther\PantherTestCase;

class UserTest extends PantherTestCase
{
    public function testSomething(): void
    {
        $client = static::createPantherClient();
        $crawler = $client->request('GET', '/login');
        
        $this->assertSelectorTextContains('h1', 'Veuillez vous connecter');
        
        $buttonCrawlerNode = $crawler->selectButton('button_submit');
        $form = $buttonCrawlerNode->form();
        
        $client->submit($form, [
            "email" => 'martin3129@gmail.com',
            "password" => '12345',
            "_remember_me" => false,
        ]);
        
        $client->clickLink('Compte');
        $client->clickLink('Back-office');
        $crawler = $client->getCrawler();
        $this->assertSelectorTextContains('#content p', "Page d'accueil back");
        
        
        $client->waitForVisibility('#btn_sidebar_hide');
        $this->assertSelectorIsVisible('#btn_sidebar_hide');
        $this->assertSelectorIsVisible('#sidebar');
        
        $client->getCrawler()->filter('#btn_sidebar_hide')->click();
        $client->waitForInvisibility('#sidebar');
        $this->assertSelectorIsNotVisible('#sidebar');
        
        
        $client->waitForVisibility('#btn_sidebar_show');
        $this->assertSelectorIsVisible('#btn_sidebar_show');
        $client->getCrawler()->filter('#btn_sidebar_show')->click();

        $client->waitForVisibility('#sidebar > ul > li:nth-child(2) > a');
        $this->assertSelectorIsVisible('#sidebar > ul > li:nth-child(2) > a');
        $client->getCrawler()->filter('#sidebar > ul > li:nth-child(2) > a')->click();
        
        $client->waitForVisibility('#user_sub_menu > li:nth-child(1) > a');
        $this->assertSelectorIsVisible('#user_sub_menu > li:nth-child(1) > a');
        $client->getCrawler()->filter('#user_sub_menu > li:nth-child(1) > a')->click();
        
        $client->waitFor('#sidebar');
        $this->assertSelectorTextContains('h1', 'Rechercher - Utilisateur');
        $client->getCrawler()->filter('th input[type="checkbox"]')->click();
        
        $client->waitFor('td input[type="checkbox"]:checked');
        $this->assertCount(1, $client->getCrawler()->filter('td input[type="checkbox"]:checked'));
        
        $client->getCrawler()->filter('a[href="/back/user/create"]')->last()->click();
        $client->waitFor('#sidebar');
        $this->assertSelectorTextContains('h1', 'Invitation');

        $buttonCrawlerNode = $client->getCrawler()->selectButton('button_submit');
        $form = $buttonCrawlerNode->form();
        
        $client->submit($form, [
            "user[firstname]" => 'Bob',
            "user[lastname]" => 'Dupont',
            "user[email]" => 'test@gmail.com',
            "user[roles]" => 'ROLE_ADMIN',
        ]);
        
        $client->waitFor('#sidebar');
        $this->assertCount(2, $client->getCrawler()->filter('td input[type="checkbox"]'));
        $this->assertSelectorIsEnabled('tr:nth-child(1) > td:nth-child(7) > a');
        $this->assertSelectorAttributeContains('tr:nth-child(1) > td:nth-child(7) > a', 'aria-pressed', 'false');
        $client->getCrawler()->filter('tr:nth-child(1) > td:nth-child(7) > a')->click();
        $client->waitFor('#sidebar');
        $this->assertSelectorAttributeContains('tr:nth-child(1) > td:nth-child(7) > a', 'role', 'button');
        $this->assertSelectorAttributeContains('tr:nth-child(1) > td:nth-child(7) > a', 'aria-pressed', 'true');
        $this->assertSelectorAttributeContains('tr:nth-child(1) > td:nth-child(7) > a', 'class', 'active');

        
        $client->getCrawler()->filter('tr:nth-child(1) > td:nth-child(2) > a:nth-child(1)')->click();
        $client->waitFor('#sidebar');
        $this->assertSelectorTextContains('h1', 'Détails - Utilisateur');
        
        $client->getCrawler()->filter('div.col-sm-6.text-right > p > a:nth-child(1)')->click();
        $client->waitFor('#sidebar');
        $this->assertSelectorTextContains('h1', 'Rechercher - Utilisateur');
        
        $client->getCrawler()->filter('tr:nth-child(1) > td:nth-child(2) > a:nth-child(2)')->click();
        $client->waitFor('#sidebar');
        $this->assertSelectorTextContains('h1', 'Modifier - Utilisateur');

        $client->getCrawler()->filter('div.col-sm-6.text-right > p > a:nth-child(1)')->click();
        $client->waitFor('#sidebar');
        $this->assertSelectorTextContains('h1', 'Rechercher - Utilisateur');

        $client->getCrawler()->filter('td a[title="Supprimer"]')->click();
        $client->waitFor('#form_back_user_delete button[type="submit"]');
        $client->getCrawler()->filter('#form_back_user_delete button[type="submit"]')->click();
        $client->waitFor('#sidebar');
        $this->assertSelectorTextContains('#flash_message', "Utilisateur(s) supprimé.");

        //print_r($client->getCrawler()->text());
        //die();
        
    }
}
