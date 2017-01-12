<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use GuzzleHttp\Client;
use GuzzleHttp\Cookie\FileCookieJar;
use Psr\Http\Message\ResponseInterface;

trait AuthenticatorTrait
{
    /**
     * @var Client Guzzle client
     */
    protected $httpClient;
    /**
     * @var string selected account phone number
     */
    protected $authPhone;
    /**
     * @var string selected account password
     */
    protected $authPassword;
    /**
     * @var bool is $cookieFiles exists
     */
    protected $authenticated = false;
    /**
     * @var int authentication retries
     */
    protected $authRetries = 0;
    /**
     * @var string full path to cookie file
     */
    protected $cookieFile;
    /**
     * @var FileCookieJar cookie object
     */
    protected $jar;

    /**
     * AuthenticatorTrait constructor.
     */
    public function bootAuthenticator()
    {
        $account = config('app.accounts')[array_rand(config('app.accounts'))];

        $this->authPhone = $account[0];
        $this->authPassword = $account[1];

        $this->cookieFile = sprintf(config('app.paths.cookie'), md5($this->authPhone));
        $this->authenticated = file_exists($this->cookieFile);
        $this->jar = new FileCookieJar($this->cookieFile);
    }

    /**
     * Checks whether response page has authenticated user data
     * @param ResponseInterface $response
     * @return boolean
     */
    private function checkIsAuthenticated($response)
    {
        $body = (string)$response->getBody();

        return str_contains($body, 'https://login.vk.com/?act=logout');
    }

    /**
     * Checks whether response page has security check form
     * @param ResponseInterface $response
     * @return array
     */
    private function checkIsSecurityCheck($response)
    {
        $body = (string)$response->getBody();

        return str_contains($body, 'login.php?act=security_check');
    }

    /**
     * Login to the site
     */
    private function auth()
    {
        $this->authRetries++;
        $loginResponse = $this->httpClient->get('login', ['cookies' => $this->jar]);

        $authUrl = $this->getFormUrl($loginResponse);

        $this->httpClient->post($authUrl, [
            'cookies' => $this->jar,
            'form_params' => [
                'email' => $this->authPhone,
                'pass' => $this->authPassword
            ]
        ]);
    }

    /**
     * Completes VK security check with current credentials if response has security check form
     * Has side effects
     * @param ResponseInterface $response
     */
    private function authSecurityCheck($response)
    {
        if (!$this->checkIsSecurityCheck($response)) {
            return;
        }
        $body = $response->getBody();

        // for now we can handle only phone number security checks
        if (str_contains($body, "все недостающие цифры номера")) {
            $dom = new Dom;
            $dom->load($body);
            $prefixes = $dom->find('.field_prefix');

            $leftPrefixCount = strlen($prefixes[0]->text) - 1; // length country code without plus: +7(1), +33(2), +993(3).
            $rightPrefixCount = strlen(filter_var($prefixes[1]->text,
                FILTER_SANITIZE_NUMBER_INT)); // just filter out numbers and count

            // code is 'middle' of the phone number
            $securityCode = substr($this->authPhone, $leftPrefixCount, -$rightPrefixCount);
        }

        if (isset($securityCode)) {
            $formUrl = $this->getFormUrl($response);

            $this->httpClient->post($formUrl, [
                'cookies' => $this->jar,
                'form_params' => [
                    'code' => $securityCode,
                ]
            ]);
        } else {
            abort(403);
        }
    }

    /**
     * Get form action url from response
     * @param ResponseInterface $response
     * @return string
     */
    private function getFormUrl($response)
    {
        if (preg_match('/<form method="post" action="([^"]+)"/Us', $response->getBody(),
            $match)) {
            return $match[1];
        } else {
            return null;
        }
    }
}