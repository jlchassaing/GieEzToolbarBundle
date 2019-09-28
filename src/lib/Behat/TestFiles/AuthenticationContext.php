<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Gie\EzToolbar\Behat\TestFiles;

use Behat\Behat\Context\Context;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkContext;
use OutOfBoundsException;

/** Context for authentication actions */
class AuthenticationContext extends MinkContext
{
    /** @var array Dictionary of known user logins and their passwords */
    private $userCredentials = [
        'admin' => 'publish',
        'contrib' => 'publish',
    ];

    /**
     * @Given there is a contrib user whith password publish having rights eztoolbar
     */
    public function thereIsAContribUserWhithPasswordPublishHavingRightsEztoolbar()
    {
        throw new PendingException();
    }

    /**
     * @Given I open Login page
     */
    public function iOpenLoginPage()
    {
        $this->visit('/login');

    }

    /**
     * @Then I should view ez Toolbar
     */
    public function iShouldViewEzToolbar()
    {
        $page = $this->getSession()->getPage();
        $selector = '#toolbar_layout';


        try {
            return $this->waitUntil(5000,
                function () use ($selector, $page) {
                    return $page->has('css', $selector);
                });
        } catch (Exception $e) {
            throw new ElementNotFoundException($this->getSession()->getDriver(), null, 'css', $selector);
        }
    }



    /**
     * @When I login as :username with password :password
     */
    public function iLoginAs(string $username, string $password): void
    {
        $page = $this->getSession()->getPage();
        $page->find('css', '#username')->setValue($username);
        $page->find('css', '#password')->setValue($password);

        $page->find('css', "button[type='submit']")->click();

    }

    /**
     * @Given I am logged as :username
     */
    public function iAmLoggedAs(string $username): void
    {

    }

    /**
     * Waits no longer than specified timeout for the given condition to be true.
     *
     * @param int $timeoutSeconds Timeout
     * @param callable Condition to verify
     * @param bool $throwOnFailure Whether Exception should be thrown when timeout is exceeded
     *
     * @return mixed
     *
     * @throws Exception If $throwOnFailure is true and timeout exceeded
     */
    public function waitUntil(int $timeoutSeconds, callable $callback, bool $throwOnFailure = true)
    {
        if (!is_callable($callback)) {
            throw new \InvalidArgumentException('Given callback is not a valid callable');
        }

        $start = time();
        $end = $start + $timeoutSeconds;

        $lastInternalExceptionMessage = '';

        do {
            try {
                $result = $callback($this);

                if ($result) {
                    return $result;
                }
            } catch (Exception $e) {
                $lastInternalExceptionMessage = $e->getMessage();
            }
            usleep(250 * 1000);
        } while (time() < $end);

        if ($throwOnFailure) {
            throw new Exception('Spin function did not return in time. Last internal exception:' . $lastInternalExceptionMessage);
        }
    }
}
