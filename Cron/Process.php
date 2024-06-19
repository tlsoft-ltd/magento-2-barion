<?php
/*
 * TLSoft
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the TLSoft license that is
 * available through the world-wide-web at this URL:
 * https://tlsoft.hu/license
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    TLSoft
 * @package     TLSoft_BarionGateway
 * @copyright   Copyright (c) TLSoft (https://tlsoft.hu/)
 * @license     https://tlsoft.hu/license
 */

namespace TLSoft\BarionGateway\Cron;

use Magento\Framework\Api\Filter;
use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\FilterGroupBuilder;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use TLSoft\BarionGateway\Gateway\Helper\Communication;

/**
 * @property OrderRepositoryInterface $orderRepository
 * @property Filter $filter
 * @property FilterBuilder $filterBuilder
 * @property FilterGroupBuilder $filterGroup
 * @property SearchCriteriaBuilder $searchCriteria
 * @property TimezoneInterface $timezone
 * @property TransactionRepositoryInterface $transactionRepository
 * @property Communication $helper
 */
class Process
{


    private Communication $helper;
    private TransactionRepositoryInterface $transactionRepository;
    private TimezoneInterface $timezone;
    private SearchCriteriaBuilder $searchCriteria;
    private FilterGroupBuilder $filterGroup;
    private FilterBuilder $filterBuilder;
    private Filter $filter;
    private OrderRepositoryInterface $orderRepository;

    public function __construct(OrderRepositoryInterface $orderRepository, Filter $filter, FilterBuilder $filterBuilder, FilterGroupBuilder $filterGroup, SearchCriteriaBuilder $searchCriteria, TimezoneInterface $timezone, TransactionRepositoryInterface $transactionRepository, Communication $helper)
    {
        $this->orderRepository = $orderRepository;
        $this->filter = $filter;
        $this->filterBuilder = $filterBuilder;
        $this->filterGroup = $filterGroup;
        $this->searchCriteria = $searchCriteria;
        $this->timezone = $timezone;
        $this->transactionRepository = $transactionRepository;
        $this->helper = $helper;
    }

    public function execute()
    {

        $orderRepository = $this->orderRepository;
        $date = $this->timezone->date('-20 minute', null, true, false);
        $date = $date->format('Y-m-d H:i');
        $filter = [['field' => 'created_at', 'value' => $date, 'condition' => 'lt'], ['field' => 'state', 'value' => 'new', 'condition' => 'eq'], ['field' => 'payment_method', 'value' => 'bariongateway', 'condition' => 'eq']];
        $criteria = $this->getSearchCriteria($filter);

        if (is_object($criteria)) {
            $result = $orderRepository->getList($criteria);
            $orders = $result->getItems();
            foreach ($orders as $order) {
                $increment_id = $order->getId();
                $filter = [['field' => 'order_id', 'value' => $increment_id, 'condition' => 'eq']];
                $criteria = $this->getSearchCriteria($filter);

                if (is_object($criteria)) {
                    $transactionRepository = $this->transactionRepository;
                    $result = $transactionRepository->getList($criteria);
                    $transactions = $result->getItems();
                    $i = 0;
                    if($result->getTotalCount()>0) {
                        foreach ($transactions as $transaction) {
                            if ($i > 0 || $transaction->getTxnType()==TransactionInterface::TYPE_CAPTURE)
                                continue;
                            $trid = $transaction->getTxnId();
                            if(empty($trid))
                                continue;
                            $params["paymentId"] = $trid;
                            $this->helper->processTransaction($params, false, $order);
                            $i++;
                        }
                    }else{
                        $this->helper->processTransaction([], false, $order);
                    }
                }else{
                    $this->helper->processTransaction([], false, $order);
                }

            }
        }
    }

    /**
     * Get search criteria for collection filtering
     * @param array $criteria
     * @return SearchCriteria|null
     */
    private function getSearchCriteria($criteria = array())
    {
        $groups = array();
        foreach ($criteria as $crit) {
            $filter = $this->getFilter($crit);
            if (is_object($filter)) {
                $group = $this->getFilterGroup($filter);
                $groups[] = $group;
            }
        }
        if (count($groups) > 0) {
            $search = $this->searchCriteria;
            $search->setFilterGroups($groups);
            $search = $search->create();
        }
        return $search;
    }

    /**
     * Get filter for search criteria
     * @param array $criteria
     * @return null|Filter
     */
    private function getFilter($criteria = array())
    {
        $filter = "";
        if (count($criteria) > 0) {
            $filter = clone $this->filterBuilder;
            $filter->setField($criteria["field"]);
            $filter->setValue($criteria["value"]);
            $filter->setConditionType($criteria["condition"]);
            $filter = $filter->create();
        }
        return $filter;
    }

    /**
     * Get Filter grouo for the search criteria builder
     * @param Filter $filter
     * @return FilterGroup
     */
    private function getFilterGroup(Filter $filter): FilterGroup
    {
        $group = clone $this->filterGroup;
        $group->addFilter($filter);
        return $group->create();
    }
}