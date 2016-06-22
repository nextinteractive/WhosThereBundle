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

namespace LpDigital\Bundle\WhosThereBundle\Tests\Listener;

use BackBee\ClassContent\ContentSet;
use BackBee\ClassContent\Revision;
use BackBee\NestedNode\Page;
use BackBee\Security\User;

use LpDigital\Bundle\WhosThereBundle\Listener\WhosThereListener;
use LpDigital\Bundle\WhosThereBundle\Tests\WhosThereTestCase;

/**
 * Tests suite for LpDigital\Bundle\WhosThereBundle\Listener\WhosThereListener.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers LpDigital\Bundle\WhosThereBundle\Listener\WhosThereListener
 */
class WhosThereListenerTest extends WhosThereTestCase
{

    /**
     * @var WhosThereListener
     */
    private $listener;

    /**
     * @var \ReflectionProperty
     */
    private $property;

    /**
     * Sets up the required fixtures.
     */
    public function setUp()
    {
        parent::setUp();
        $this->listener = $this->whosThere->getApplication()->getContainer()->get('whosthere.bundle.listener');

        $reflection = new \ReflectionClass($this->listener);
        $this->property = $reflection->getProperty('renderedClassContent');
        $this->property->setAccessible(true);
    }

    /**
     * @covers LpDigital\Bundle\WhosThereBundle\Listener\WhosThereListener::onPostRenderClassContent()
     */
    public function testOnPostRenderClassContent()
    {
        $contentset = new ContentSet();
        $event = $this->createRendererEvent($contentset);

        // No Token
        $this->listener->onPostRenderClassContent($event);
        $this->assertEquals([], $this->property->getValue($this->listener));

        $this->createAuthenticatedUser();

        // Not an AbstractClassContent
        $this->listener->onPostRenderClassContent($this->createRendererEvent(new Page()));
        $this->assertEquals([], $this->property->getValue($this->listener));

        // Everything's ok
        $this->listener->onPostRenderClassContent($event);
        $this->assertEquals([$contentset], $this->property->getValue($this->listener));
    }

    /**
     * @covers LpDigital\Bundle\WhosThereBundle\Listener\WhosThereListener::onPostRenderPage()
     */
    public function testOnPostRenderPage()
    {
        $event = $this->createRendererEvent(new Page());

        // No Token
        $this->listener->onPostRenderPage($event);
        $this->assertEquals('<body></body>', $event->getRenderer()->getRender());

        $this->createAuthenticatedUser();

        // Empty ClassContent array
        $this->listener->onPostRenderPage($event);
        $this->assertEquals('<body></body>', $event->getRenderer()->getRender());

        $contentset = new ContentSet();
        $this->listener->onPostRenderClassContent($this->createRendererEvent($contentset));

        // Not a Page
        $this->listener->onPostRenderPage($this->createRendererEvent(new ContentSet()));
        $this->assertEquals('<body></body>', $event->getRenderer()->getRender());

        $this->resetDatabase([
            $this->getEntityManager()->getClassMetadata('BackBee\Site\Site'),
            $this->getEntityManager()->getClassMetadata('BackBee\Site\Layout'),
            $this->getEntityManager()->getClassMetadata('BackBee\NestedNode\Page'),
            $this->getEntityManager()->getClassMetadata('BackBee\NestedNode\Section'),
            $this->getEntityManager()->getClassMetadata('BackBee\ClassContent\AbstractClassContent'),
            $this->getEntityManager()->getClassMetadata('BackBee\ClassContent\Revision'),
            $this->getEntityManager()->getClassMetadata('BackBee\ClassContent\Indexation'),
        ]);

        // No owners
        $this->listener->onPostRenderPage($event);
        $this->assertEquals('<body></body>', $event->getRenderer()->getRender());

        $revision = new Revision();
        $revision->setContent($contentset);
        $revision->setOwner(new User('admin2'));

        $this->getEntityManager()->persist($contentset);
        $this->getEntityManager()->persist($revision);
        $this->getEntityManager()->flush();

        // Everything's ok
        $this->listener->onPostRenderPage($event);
        $this->assertContains('hookQueue.register', $event->getRenderer()->getRender());
    }

    /**
     * @covers LpDigital\Bundle\WhosThereBundle\Listener\WhosThereListener::getSubscribedEvents()
     */
    public function testGetSubscribedEvents()
    {
        $expected = [
            'classcontent.postrender' => 'onPostRenderClassContent',
            'nestednode.page.postrender' => 'onPostRenderPage'
        ];

        $this->assertEquals($expected, $this->listener->getSubscribedEvents());
    }
}
