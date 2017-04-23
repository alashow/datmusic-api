<?php
/**
 * Copyright (c) 2017  Alashov Berkeli
 * It is licensed under GNU GPL v. 2 or later. For full terms see the file LICENSE.
 */

namespace App\Datmusic;

use PHPHtmlParser\Dom;
use Illuminate\Support\Str;
use GuzzleHttp\Cookie\FileCookieJar;
use Psr\Http\Message\ResponseInterface;

trait AuthenticatorTrait
{
    /**
     * @var string selected account phone number
     */
    protected $authPhone;
    /**
     * @var string selected account password
     */
    protected $authPassword;
    /**
     * @var bool is exists
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
     * Checks whether response page has authenticated user data.
     *
     * @param ResponseInterface $response
     *
     * @return bool
     */
    private function checkIsAuthenticated($response)
    {
        $body = (string) $response->getBody();

        return str_contains($body, 'https://login.vk.com/?act=logout');
    }

    /**
     * Checks whether response page has security check form.
     *
     * @param ResponseInterface $response
     *
     * @return array
     */
    private function checkIsSecurityCheck($response)
    {
        $body = (string) $response->getBody();

        return str_contains($body, 'login.php?act=security_check');
    }

    /**
     * Login to the site.
     */
    private function auth()
    {
        $this->authRetries++;
        $loginResponse = httpClient()->get('login', ['cookies' => $this->jar]);

        $authUrl = $this->getFormUrl($loginResponse);

        logger()->log('Auth', $this->authPhone, $this->authRetries);

        httpClient()->post($authUrl, [
            'cookies'     => $this->jar,
            'form_params' => [
                'email' => $this->authPhone,
                'pass'  => $this->authPassword,
            ],
        ]);
    }

    /**
     * Completes VK security check with current credentials if response has security check form
     * Has side effects.
     *
     * @param ResponseInterface $response
     */
    private function authSecurityCheck($response)
    {
        if (! $this->checkIsSecurityCheck($response)) {
            return;
        }

        $body = $response->getBody();
        $dom = new Dom();
        $dom->load($body);
        $prefixes = $dom->find('.field_prefix');

        // for now we can handle only phone number security checks

        // check is prefixes looks like phone numbers
        $isPhoneCheck = Str::startsWith($this->authPhone, getIntegers($prefixes[0]->text))
            && Str::endsWith($this->authPhone, getIntegers($prefixes[1]->text));

        if ($isPhoneCheck) {
            $leftPrefixCount = strlen($prefixes[0]->text) - 1; // length country code without plus: +7(1), +33(2), +993(3).
            $rightPrefixCount = strlen(getIntegers($prefixes[1]->text));

            // code is 'middle' of the phone number
            $securityCode = substr($this->authPhone, $leftPrefixCount, -$rightPrefixCount);
        }

        logger()->log('Auth.SecurityCheck', $isPhoneCheck);

        if (isset($securityCode)) {
            $formUrl = $this->getFormUrl($response);

            httpClient()->post($formUrl, [
                'cookies'     => $this->jar,
                'form_params' => [
                    'code' => $securityCode,
                ],
            ]);
        } else {
            abort(403);
        }
    }

    /**
     * Get form action url from response.
     *
     * @param ResponseInterface $response
     *
     * @return string
     */
    private function getFormUrl($response)
    {
        if (preg_match('/<form method="post" action="([^"]+)"/Us', $response->getBody(),
            $match)) {
            return $match[1];
        } else {
            return;
        }
    }
}
