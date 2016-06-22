<?php

/*
 * Copyright (c) 2016 Lp digital system
 *
 * This file is part of WhosThereBundle.
 *
 * WhosThereBundle is free bundle: you can redistribute it and/or
 * modify it under the terms of the GNU General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * WhosThereBundle is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with WhosThereBundle. If not, see <http://www.gnu.org/licenses/>.
 */

namespace LpDigital\Bundle\WhosThereBundle\Tests;

use Doctrine\ORM\Tools\SchemaTool;
use org\bovigo\vfs\vfsStream;

use BackBee\Installer\EntityFinder;
use BackBee\Renderer\Event\RendererEvent;
use BackBee\Security\Token\BBUserToken;
use BackBee\Security\User;
use BackBee\Tests\Mock\MockBBApplication;

use LpDigital\Bundle\WhosThereBundle\WhosThere;

/**
 * Test case for WhosThere bundle.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class WhosThereTestCase extends \PHPUnit_Framework_TestCase
{

    /**
     * @var WhosThere
     */
    protected $whosThere;

    /**
     * Sets up the required fixtures.
     */
    public function setUp()
    {
        $mockConfig = [
            'ClassContent' => [],
            'Config' => [
                'bootstrap.yml' => file_get_contents(__DIR__ . '/Config/bootstrap.yml'),
                'bundles.yml' => file_get_contents(__DIR__ . '/Config/bundles.yml'),
                'config.yml' => file_get_contents(__DIR__ . '/Config/config.yml'),
                'doctrine.yml' => file_get_contents(__DIR__ . '/Config/doctrine.yml'),
                'logging.yml' => file_get_contents(__DIR__ . '/Config/logging.yml'),
                'security.yml' => file_get_contents(__DIR__ . '/Config/security.yml'),
                'services.yml' => file_get_contents(__DIR__ . '/Config/services.yml'),
            ],
            'Ressources' => [],
            'cache' => [
                'Proxies' => [],
                'twig' => []
            ],
        ];
        vfsStream::umask(0000);
        vfsStream::setup('repositorydir', 0777, $mockConfig);

        $mockApp = new MockBBApplication(null, null, false, $mockConfig, __DIR__ . '/../vendor');
        $this->whosThere = $mockApp->getBundle('whosthere');
    }

    /**
     * Returns a valid RendererEvent.
     *
     * @param  mixed $target
     *
     * @return RendererEvent
     */
    protected function createRendererEvent($target)
    {
        $event = new RendererEvent($target, $this->whosThere->getApplication()->getRenderer());
        $event->setDispatcher($this->whosThere->getApplication()->getEventDispatcher());
        $event->getRenderer()->setRender('<body></body>');

        return $event;
    }

    /**
     * Returns TestKernel unique application's EntityManager instance.
     *
     * @return \Doctrine\ORM\EntityManager
     */
    public function getEntityManager()
    {
        return $this->whosThere->getEntityManager();
    }

    /**
     * Reset partially or completely the database.
     *
     * @param  array|null $entityMetadata The array that contains only metadata of entities we want to create
     * @param  boolean    $hardReset      This option force hard reset of the entire database
     * @return self
     */
    public function resetDatabase(array $entityMetadata = null, $hardReset = false)
    {
        $schemaTool = new SchemaTool($this->getEntityManager());

        if (null === $entityMetadata || true === $hardReset) {
            $schemaTool->dropDatabase();
        } else {
            $schemaTool->dropSchema($entityMetadata);
        }

        if (null === $entityMetadata) {
            $entityFinder = new EntityFinder($this->getApplication()->getBBDir());

            $metadataDriver = $this->getEntityManager()->getConfiguration()->getMetadataDriverImpl();
            foreach ($this->getEntityPaths() as $path) {
                $metadataDriver->addPaths([$path]);
                $metadataDriver->addExcludePaths($entityFinder->getExcludePaths($path));
            }
        }

        $schemaTool->createSchema($entityMetadata);

        return $this;
    }

    /**
     * Creates a user for the specified group and authenticates a BBUserToken with the newly created user.
     * Note that the token is setted into application security context.
     */
    protected function createAuthenticatedUser($login = 'admin', array $roles = ['ROLE_API_USER'])
    {
        $user = new User();
        $user
                ->setEmail($login . '@backbee.com')
                ->setLogin($login)
                ->setPassword('pass')
                ->setApiKeyPrivate(uniqid('PRIVATE', true))
                ->setApiKeyPublic(uniqid('PUBLIC', true))
                ->setApiKeyEnabled(true)
        ;

        $token = new BBUserToken($roles);
        $token->setAuthenticated(true);
        $token
                ->setUser($user)
                ->setCreated(new \DateTime())
                ->setLifetime(300)
        ;

        $this->whosThere->getApplication()->getSecurityContext()->setToken($token);
    }

    /**
     * Call protected/private method of a class.
     *
     * @param object &$object    Instantiated object that we will run method on.
     * @param string $methodName Method name to call
     * @param array  $parameters Array of parameters to pass into method.
     *
     * @return mixed Method return.
     * @link https://jtreminio.com/2013/03/unit-testing-tutorial-part-3-testing-protected-private-methods-coverage-reports-and-crap/
     */
    public function invokeMethod(&$object, $methodName, array $parameters = array())
    {
        $reflection = new \ReflectionClass(get_class($object));
        $method = $reflection->getMethod($methodName);
        $method->setAccessible(true);
        return $method->invokeArgs($object, $parameters);
    }
}
