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

namespace LpDigital\Bundle\WhosThereBundle\Listener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

use BackBee\ClassContent\AbstractClassContent;
use BackBee\NestedNode\Page;
use BackBee\Renderer\Event\RendererEvent;

use LpDigital\Bundle\WhosThereBundle\WhosThere;

/**
 * Listener to rendering events to add notifications on pending revisions on current page.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */
class WhosThereListener implements EventSubscriberInterface
{

    /**
     * The WhosThere bundle instance.
     *
     * @var WhosThere
     */
    private $whosThere;

    /**
     * An array of rendered classcontents.
     *
     * @var AbstractClassContent[]
     */
    private $renderedClassContent;

    /**
     * Listener constructor.
     *
     * @param WhosThere $whosThere The WhosThere bundle instance.
     */
    public function __construct(WhosThere $whosThere)
    {
        $this->whosThere = $whosThere;
        $this->renderedClassContent = [];
    }

    /**
     * Call on classcontent.postrender event.
     * Stores rendered classcontents.
     *
     * @param RendererEvent $event
     */
    public function onPostRenderClassContent(RendererEvent $event)
    {
        if (null === $event->getApplication()->getBBUserToken()) {
            return;
        }

        $classcontent = $event->getTarget();
        if (!($classcontent instanceof AbstractClassContent)) {
            return;
        }

        $this->renderedClassContent[] = $classcontent;
    }

    /**
     * Call on nestednode.page.postrender event
     * Adds a bb.notify warning if other users have modified contents on the page.
     *
     * @param RendererEvent $event
     */
    public function onPostRenderPage(RendererEvent $event)
    {
        if (null === $event->getApplication()->getBBUserToken()) {
            return;
        }

        if (empty($this->renderedClassContent)) {
            return;
        }

        $page = $event->getTarget();
        if (!($page instanceof Page)) {
            return;
        }

        $owners = $this->whosThere->getPendingRevisionOwners($this->renderedClassContent);
        if (empty($owners)) {
            return;
        }

        $notification = $event->getRenderer()->partial(
                'WhosThere/notification.twig',
                [
                    'message' => implode(', ', $owners),
                    'i18n' => json_encode($this->whosThere->getConfig()->getSection('i18n'))
                ]
        );

        $event->getRenderer()->setRender(str_replace('</body>', $notification . '</body>', $event->getRenderer()->getRender()));
    }

    /**
     * Returns an array of event names this subscriber wants to listen to.
     *
     * @return array The event names to listen to.
     */
    public static function getSubscribedEvents()
    {
        return array(
            'classcontent.postrender' => 'onPostRenderClassContent',
            'nestednode.page.postrender' => 'onPostRenderPage'
        );
    }
}
