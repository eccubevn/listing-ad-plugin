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

namespace Plugin\ListingAdCsv\Service\ListingAdDataCreator\Rows\Yahoo;

use Plugin\ListingAdCsv\Service\ListingAdDataCreator\Campaign\CampaignInterface;

class RowCampaign
{
    private $containers = [];

    /**
     * RowCampaign constructor.
     *
     * @param CampaignInterface $campaign
     */
    public function __construct(CampaignInterface $campaign)
    {
        // 広告行：追加
        $ad_group = new RowAdGroup($campaign);
        foreach ($ad_group->getContainers() as $container) {
            array_push($this->containers, $container);
        }

        // キャンペーン行：固有のパラメータを設定して追加
        $container = new ColumnContainer();
        $container->setComponentType('キャンペーン');
        $container->setIsDelivery($this->getCampaignStatusText($campaign->isCampaignStatus()));
        $container->setBidAdjustment('0');
        $container->setDailyBudget($campaign->getDailyBudget());
        $container->setCampaignName($campaign->getCampaignName());
        array_push($this->containers, $container);
    }

    /**
     * @return ColumnContainer[]
     */
    public function getContainers()
    {
        return $this->containers;
    }

    /**
     * @param boolean $campaign_status
     *
     * @return string
     */
    private function getCampaignStatusText($campaign_status)
    {
        return $campaign_status ? 'オン' : 'オフ';
    }
}
