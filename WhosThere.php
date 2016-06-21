<?php

namespace LpDigital\Bundle\WhosThereBundle;

use BackBee\Bundle\AbstractBundle;
use BackBee\ClassContent\AbstractClassContent;
use BackBee\ClassContent\Revision;

use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

/**
 * Bundle notifying contributors that the page they currently working on has
 * some revisions yet from other ones.
 *
 * @author Charles Rouillon <charles.rouilon@lp-digital.fr>
 */
class WhosThere extends AbstractBundle
{

    /**
     * Start method of the bundle
     */
    public function start()
    {

    }

    /**
     * Stop method of the bundle
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
    private function getRevisionOwners($revisions = [], $excludeCurrentUser = true)
    {
        if (empty($revisions)) {
            return [];
        }

        $securityIdentity = '';
        if ($excludeCurrentUser && (null !== $token = $this->getApplication()->getBBUserToken())) {
            $securityIdentity = UserSecurityIdentity::fromToken($token) . '';
        }

        $owners = [];
        foreach ($revisions as $revision) {
            if (false === $username = $this->getUsernameFromIdentity($revision->getOwner())) {
                continue;
            }

            if ($excludeCurrentUser && $revision->getOwner() === $securityIdentity) {
                continue;
            }

            $owners[$revision->getOwner()] = $username;
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
