<?php

/**
 * @copyright Copyright (C) eZ Systems AS. All rights reserved.
 * @license For full copyright and license information view LICENSE file distributed with this source code.
 */
namespace Gie\EzToolbar\Behat\Context;

use Behat\Behat\Context\Context;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Mink\Exception\ElementNotFoundException;
use Behat\MinkExtension\Context\MinkContext;
use eZ\Publish\API\Repository\Exceptions\NotFoundException;
use eZ\Publish\API\Repository\Repository;
use eZ\Publish\API\Repository\Values\Content\Location;
use Gie\EzToolbar\Behat\Context\YamlConfigurationContext;
use EzSystems\PlatformBehatBundle\Context\RepositoryContext;
use OutOfBoundsException;
use Symfony\Component\Yaml\Yaml;

/** Context for authentication actions */
class AuthenticationContext extends MinkContext
{
    use RepositoryContext;

    /** @var YamlConfigurationContext */
    private $configurationContext;

    /**
     * Content item matched by the view configuration.
     * @var Location
     */
    private $matchedLocation;


    /** @var array Dictionary of known user logins and their passwords */
    private $userCredentials = [
        'admin' => 'publish',
        'contrib' => 'publish',
    ];

    /**
     * QueryControllerContext constructor.
     * @injectService $repository @ezpublish.api.repository
     */
    public function __construct(Repository $repository)
    {
        $this->repository = $repository;
        $this->matchedLocation = $this->repository->getLocationService()->loadLocation(2);

    }

    /** @BeforeScenario */
    public function gatherContexts(BeforeScenarioScope $scope)
    {
        $environment = $scope->getEnvironment();

        $this->configurationContext = $environment->getContext(
            'Gie\EzToolbar\Behat\Context\YamlConfigurationContext'
        );
    }

    /**
     * @Given /^There is a template "([^"]*)" that loads the code :$/
     */
    public function thereIsATemplateTemplateThatLoadsTheCode(string $template, PyStringNode $code)
    {
        $this->configurationContext->addTemplateToImortCode($template, $code);
    }


    /**
     * @Given /^the following content view configuration block:$/
     */
    public function addContentViewConfigurationBlock(PyStringNode $string)
    {
        $configurationBlock = array_merge(
            Yaml::parse($string),
            [
                'match' => [
                    'Id\Location' => $this->matchedLocation->id,
                ],
            ]
        );

        $configurationBlockName = 'toolbar_view_' . $this->matchedLocation->id;

        $configuration = [
            'ezpublish' => [
                'system' => [
                    'default' => [
                        'content_view' => [
                            'full' => [
                                $configurationBlockName => $configurationBlock,
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->configurationContext->addConfiguration($configuration);
    }

    /**
     * @Given there is a user :login whith password :password having rights eztoolbar
     */
    public function thereIsAContribUserWhithPasswordPublishHavingRightsEztoolbar(string $login,string $password)
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();

        if (!$this->userExists($login) )
        {
            $this->createUser($login, $password);
        }
    }

    private function createUser($login,$password){
        $repository = $this->getRepository();
        $contentTypeService = $repository->getContentTypeService();
        $contentService = $repository->getContentService();
        $locationService = $repository->getLocationService();
        $userService = $repository->getUserService();
        $roleService = $repository->getRoleService();

        // create Role
        $role = $this->createRole();
        // load user group "editor" : 14
        $group = $userService->loadUserGroup(14);

        // create User in group
        $contentType = $contentTypeService->loadContentTypeByIdentifier('user');
        $userCreateStruct = $userService->newUserCreateStruct($login,$login.'@test.com',$password,'eng-GB', $contentType);

        $userCreateStruct->setField('first_name', 'First'. $login);
        $userCreateStruct->setField('last_name', 'Last'. $login);

        $createdUser = $userService->createUser($userCreateStruct,[$group]);
        dump($createdUser,$group,$role);
        $roleService->assignRoleToUser($role, $createdUser);
    }

    /**
     * @return mixed
     */
    private function createRole()
    {
        $repository = $this->getRepository();
        $roleService = $repository->getRoleService();
        try{
            return $repository->getRoleService()->loadRoleByIdentifier('view_toolbar');

        }catch(NotFoundException $e)
        {
            $roleCreateStruct = $roleService->newRoleCreateStruct('contributor');
            $roleCreateStruct->identifier = 'view_toolbar';
            $roleCreateStruct->addPolicy($roleService->newPolicyCreateStruct('toolbar','use'));
            $roleDraft = $roleService->createRole($roleCreateStruct);
            $roleService->publishRoleDraft($roleDraft);
            return $roleService->loadRoleByIdentifier('view_toolbar');
        }
    }

    private function userExists($login)
    {
        $repository = $this->getRepository();
        $userService = $repository->getUserService();
        try {
            $user = $userService->loadUserByLogin($login);
            return is_object($user);
        }catch (NotFoundException $e) {
            return false;
        }
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
