<?php

namespace App\Service;

use App\Entity\Text;
use Symfony\Component\HttpClient\Exception\ClientException;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Validator\Constraints\Json;
use Symfony\Component\Validator\Validation;

class Recaptcha3Service
{
    /**
     *
     * @var HttpClientInterface
     */
    private $httpClient;
    
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $secretKey;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $parameterBag)
    {
        $this->httpClient = $httpClient;
        
        $this->url = $parameterBag->get('app.recaptcha3.url');
        $this->secretKey = $parameterBag->get('app.recaptcha3.secret');
    }
    
    public function verifyResponse($token)
    {
        $response = $this->httpClient->request('POST', $this->url, [
            'body' => [
                'secret' => $this->secretKey,
                'response' => $token,
            ],
        ]);
        $statusCode = $response->getStatusCode();
        
        $content = $response->getContent(false);
        if (200 != $statusCode) {
            return null;
        }
        
        return  json_decode($content);
    }
}
