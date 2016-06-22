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

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use BackBee\ClassContent\ContentSet;
use BackBee\ClassContent\Revision;
use BackBee\Security\User;

use LpDigital\Bundle\WhosThereBundle\Tests\WhosThereTestCase;

/**
 * Tests suite for LpDigital\Bundle\WhosThereBundle\WhosThere.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 * @covers LpDigital\Bundle\WhosThereBundle\WhosThere
 */
class WhosThereTest extends WhosThereTestCase
{

    /**
     * @covers LpDigital\Bundle\WhosThereBundle\WhosThere::getPendingRevisions()
     */
    public function testGetPendingRevisions()
    {
        $this->assertEquals([], $this->invokeMethod($this->whosThere, 'getPendingRevisions'));

        $this->resetDatabase([
            $this->getEntityManager()->getClassMetadata('BackBee\Site\Site'),
            $this->getEntityManager()->getClassMetadata('BackBee\Site\Layout'),
            $this->getEntityManager()->getClassMetadata('BackBee\NestedNode\Page'),
            $this->getEntityManager()->getClassMetadata('BackBee\NestedNode\Section'),
            $this->getEntityManager()->getClassMetadata('BackBee\ClassContent\AbstractClassContent'),
            $this->getEntityManager()->getClassMetadata('BackBee\ClassContent\Revision'),
            $this->getEntityManager()->getClassMetadata('BackBee\ClassContent\Indexation'),
        ]);

        $this->createAuthenticatedUser();

        $contentset = new ContentSet();
        $this->getEntityManager()->persist($contentset);
        $this->getEntityManager()->flush();

        $revision = $contentset->getDraft();

        $this->assertEquals([$revision], $this->invokeMethod($this->whosThere, 'getPendingRevisions', [$contentset]));
        $this->assertEquals([$revision], $this->invokeMethod($this->whosThere, 'getPendingRevisions', [[$contentset]]));
    }

    /**
     * @covers LpDigital\Bundle\WhosThereBundle\WhosThere::getRevisionOwners()
     */
    public function testGetRevisionOwners()
    {
        $this->assertEquals([], $this->invokeMethod($this->whosThere, 'getRevisionOwners'));
        $this->assertEquals([], $this->invokeMethod($this->whosThere, 'getRevisionOwners', [[]]));
        $this->assertEquals([], $this->invokeMethod($this->whosThere, 'getRevisionOwners', [['NotARevision']]));
        $this->assertEquals([], $this->invokeMethod($this->whosThere, 'getRevisionOwners', [[new Revision()]]));

        $revision = new Revision();
        $revision->setOwner(new User('admin'));

        $this->assertEquals(['admin'], $this->invokeMethod($this->whosThere, 'getRevisionOwners', [[$revision]]));

        $this->createAuthenticatedUser();
        $this->assertEquals([], $this->invokeMethod($this->whosThere, 'getRevisionOwners', [[$revision]]));
        $this->assertEquals(['admin'], $this->invokeMethod($this->whosThere, 'getRevisionOwners', [[$revision], false]));

        $revision2 = new Revision();
        $revision2->setOwner(new User('admin2'));

        $this->assertEquals(['admin2'], $this->invokeMethod($this->whosThere, 'getRevisionOwners', [[$revision, $revision2]]));
        $this->assertEquals(['admin', 'admin2'], $this->invokeMethod($this->whosThere, 'getRevisionOwners', [[$revision, $revision2], false]));
    }

    /**
     * @covers LpDigital\Bundle\WhosThereBundle\WhosThere::getUsernameFromIdentity()
     */
    public function testGetUsernameFromIdentity()
    {
        $this->assertFalse($this->invokeMethod($this->whosThere, 'getUsernameFromIdentity', ['NotAUserSecurityIdentity']));

        $identity = UserSecurityIdentity::fromAccount(new User('admin'));
        $this->assertEquals('admin', $this->invokeMethod($this->whosThere, 'getUsernameFromIdentity', [$identity]));
    }
}
