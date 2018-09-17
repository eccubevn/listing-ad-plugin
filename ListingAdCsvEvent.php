<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) LOCKON CO.,LTD. All Rights Reserved.
 *
 * http://www.lockon.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\ListingAdCsv;

use Eccube\Event\TemplateEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ListingAdCsvEvent implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            '@admin/Product/index.twig' => 'onRenderAdminProduct',
        ];
    }

    /**
     * @param TemplateEvent $event
     */
    public function onRenderAdminProduct(TemplateEvent $event)
    {
        $event->addSnippet('@ListingAdCsv/Admin/dropdown_parts.twig');
    }
}
