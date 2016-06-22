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

namespace LpDigital\Bundle\WhosThereBundle;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use BackBee\Bundle\AbstractBundle;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Revision;

/**
 * Bundle notifying contributors that the page they currently working on has
 * some revisions yet from other ones.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class WhosThere extends AbstractBundle
{

    /**
     * Start method of the bundle
     *
     * @codeCoverageIgnore
     */
    public function start()
    {

    }

    /**
     * Stop method of the bundle
     *
     * @codeCoverageIgnore
     */
    public function stop()
    {

    }

    /**
     * Returns an array of BB user usernames owning pending revisions for provided
     * classcontents.
     *
     * @param  AbstractClassContent[] $classcontents      An array of classcontents.
     * @param  boolean                $excludeCurrentUser If TRUE, the current BB user
     *                                                    is exclude from result.
     *
     * @return string[]                                   An array of BB user usernames.
     *
     * @codeCoverageIgnore
     */
    public function getPendingRevisionOwners($classcontents = array(), $excludeCurrentUser = true)
    {
        return $this->getRevisionOwners($this->getPendingRevisions($classcontents), $excludeCurrentUser);
    }

    /**
     * Returns an array of pending revisions for provided classcontents.
     *
     * @param  AbstractClassContent[] $classcontents An array of classcontents.
     *
     * @return Revision[]                            An array of pending revisions.
     */
    private function getPendingRevisions($classcontents = [])
    {
        if (empty($classcontents)) {
            return [];
        }

        return $this->getApplication()
                        ->getEntityManager()
                        ->getRepository('BackBee\ClassContent\Revision')
                        ->findBy(['_content' => $classcontents, '_state' => [Revision::STATE_ADDED, Revision::STATE_MODIFIED]]);
    }

    /**
     * Returns an array of BB user usernames owning provided revisions.
     *
     * @param  Revision[] $revisions          An array of revisions.
     * @param  boolean    $excludeCurrentUser If TRUE, the current BB user is
     *                                        excluded from result.
     *
     * @return string[]                       An array of BB user usernames.
     */
    private function getRevisionOwners(array $revisions = [], $excludeCurrentUser = true)
    {
        if (empty($revisions)) {
            return [];
        }

        $securityIdentity = '';
        if (true === $excludeCurrentUser && (null !== $token = $this->getApplication()->getBBUserToken())) {
            $securityIdentity = UserSecurityIdentity::fromToken($token) . '';
        }

        $owners = [];
        foreach ($revisions as $revision) {
            if (!($revision instanceof Revision)) {
                continue;
            }

            if (false === $username = $this->getUsernameFromIdentity($revision->getOwner())) {
                continue;
            }

            if (true === $excludeCurrentUser && '' . $revision->getOwner() === $securityIdentity) {
                continue;
            }

            $owners[] = $username;
        }

        return $owners;
    }

    /**
     * Extract username from a UserSecurityIdentity string.
     *
     * @param  string       $identity The UserSecurityIdentity string.
     *
     * @return string|false           The username if found, false otherwise.
     */
    private function getUsernameFromIdentity($identity)
    {
        $matches = [];
        if (preg_match('/UserSecurityIdentity\(([^,]+),.*\)/i', $identity, $matches)) {
            return $matches[1];
        }

        return false;
    }
}
