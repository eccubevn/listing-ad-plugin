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

namespace Plugin\ListingAdCsv\Service\ListingAdDataCreator\AdData;

use Eccube\Entity\Product;
use Eccube\Repository\BaseInfoRepository;
use Plugin\ListingAdCsv\Util\CsvContentsUtil;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class AdContents
{
    /**
     * @var string
     */
    private $headline = '';

    /**
     * @var string
     */
    private $description1 = '';

    /**
     * @var string
     */
    private $description2 = '';

    /**
     * @var string
     */
    private $display_url = '';

    /**
     * @var string
     */
    private $link_url = '';

    /**
     * @var string
     */
    private $ad_inner_name = '';

    /**
     * @var BaseInfoRepository
     */
    private $baseInfoRepository;

    /**
     * @var UrlGeneratorInterface
     */
    private $urlGenerator;

    /**
     * AdContents constructor.
     *
     * @param BaseInfoRepository $baseInfoRepository
     * @param UrlGeneratorInterface $urlGenerator
     */
    public function __construct(BaseInfoRepository $baseInfoRepository, UrlGeneratorInterface $urlGenerator)
    {
        $this->baseInfoRepository = $baseInfoRepository;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * @param Product $product
     *
     * @throws \Exception
     */
    public function buildProduct(Product $product)
    {
        $shop_name = $this->baseInfoRepository->get()->getShopName();
        $homepage_url = $this->urlGenerator->generate('homepage', [], UrlGeneratorInterface::ABSOLUTE_PATH);
        $product_url = $this->urlGenerator->generate('product_detail', ['id' => $product->getId()], UrlGeneratorInterface::ABSOLUTE_PATH);

        $this->headline = CsvContentsUtil::clipText($product->getName(), 15);
        $this->description1 = CsvContentsUtil::clipText($product->getDescriptionDetail(), 19);
        $this->description2 = CsvContentsUtil::clipText($shop_name, 19);
        $this->display_url = CsvContentsUtil::removeHttpText(CsvContentsUtil::clipText($homepage_url, 29));
        $this->link_url = CsvContentsUtil::clipText($product_url, 1024);

        $now = new \DateTime();
        $ad_name = $now->format('Ymd').'_'.str_pad($product->getId(), 4, 0, STR_PAD_LEFT);
        $this->ad_inner_name = CsvContentsUtil::clipText($ad_name, 50);
    }

    /**
     * @return string
     */
    public function getHeadline()
    {
        return $this->headline;
    }

    /**
     * @return string
     */
    public function getDescription1()
    {
        return $this->description1;
    }

    /**
     * @return string
     */
    public function getDescription2()
    {
        return $this->description2;
    }

    /**
     * @return string
     */
    public function getDisplayUrl()
    {
        return $this->display_url;
    }

    /**
     * @return string
     */
    public function getLinkUrl()
    {
        return $this->link_url;
    }

    /**
     * @return string
     */
    public function getAdInnerName()
    {
        return $this->ad_inner_name;
    }
}
