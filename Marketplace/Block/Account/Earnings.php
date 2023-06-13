<?php
/**
 * Webkul Software.
 *
 * @category  Webkul
 * @package   Webkul_Marketplace
 * @author    Webkul
 * @copyright Copyright (c) Webkul Software Private Limited (https://webkul.com)
 * @license   https://store.webkul.com/license.html
 */

namespace Webkul\Marketplace\Block\Account;

use Webkul\Marketplace\Model\SaleslistFactory;

class Earnings extends \Magento\Framework\View\Element\Template
{
    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        SaleslistFactory $saleslistFactory,
        \Webkul\Marketplace\Helper\Data $mpHelper,
        array $data = []
    ) {
        $this->mpHelper = $mpHelper;
        $this->saleslistFactory = $saleslistFactory;
        parent::__construct($context, $data);
    }
    /**
     * getParmasDetail function is used to get request parameters
     *
     * @return mixed[]
     */
    public function getParmasDetail()
    {
        return $this->getRequest()->getParams();
    }
    
    /**
     * getPeriodValues function is return the list of filter periods
     *
     * @return mixed[string]
     */
    public function getPeriodValues()
    {
        return [
                    [
                        'value' => 'day',
                        'label' => __('Day')
                    ],
                    [
                        'value' => 'month',
                        'label' => __('Month')
                    ],
                    [
                        'value' => 'year',
                        'label' => __('Year')
                    ]
                ];
    }

    /**
     * Get data set
     *
     * @return array
     */
    public function getDatasets()
    {
        $post = $this->getRequest()->getParams();
        $limit = $this->getRequest()->getParam('period');
        $dataSet = [];
        $dataLabel = [];
        switch ($limit) {
            case 'month':
                list($dataSet, $dataLabel) = $this->getMonthlyData();
                break;
            case 'year':
                list($dataSet, $dataLabel) = $this->getYearlyData();
                break;
            default:
                list($dataSet, $dataLabel) = $this->getDailyData();
                break;
        }
        return [$this->prepareDataSet(json_encode($dataSet)), json_encode($dataLabel)];
    }
    /**
     * getDailyData function is used to get data according to days
     *
     * @return mixed[string]
     */
    protected function getDailyData()
    {
        $dataSet = [];
        $dataLabel = [];
        try {
            list($from, $to) = $this->getDateData();
            if ($to) {
                $todate = date_create($to);
                $to = date_format($todate, 'Y-m-d 23:59:59');
            }
            if (!$to) {
                $to = date('Y-m-d 23:59:59');
            }
            if ($from) {
                $fromdate = date_create($from);
                $from = date_format($fromdate, 'Y-m-d H:i:s');
            }
            if (!$from) {
                $from = date('Y-m-d 23:59:59', strtotime($from));
            }
            $sellerId = $this->mpHelper->getCustomerId();
            $fromYear = $from ? date('Y', strtotime($from)) : date('Y');
            $fromMonth = $from ? (int)date('m', strtotime($from)) : 1;
            $fromDay = $from ? (int)date('d', strtotime($from)) : 1;
            $curryear = $to ? date('Y', strtotime($to)) : date('Y');
            $currMonth = $to ? (int)date('m', strtotime($to)) : date('m');
            $currDay = $to ? (int)date('d', strtotime($to)) : date('d');
            for ($startYear = $fromYear; $startYear <= $curryear; ++$startYear) {
                $months = 12;
                if ($startYear == $curryear) {
                    $months = $currMonth;
                }
                $monthStart = ($startYear == $fromYear && $from) ? $fromMonth : 1;
                for ($monthValue = $monthStart; $monthValue <= $months; ++$monthValue) {
                    $dayStart = ($startYear == $fromYear && $monthValue == $fromMonth && $from) ? $fromDay : 1;
                    $days = $this->getMonthDays($monthValue, $startYear);
                    if ($startYear == $curryear && $monthValue == $currMonth) {
                        $days = $currDay;
                    }
                    for ($dayValue = $dayStart; $dayValue <= $days; ++$dayValue) {
                        $date1 = $startYear.'-'.$monthValue.'-'.$dayValue.' 00:00:00';
                        $date2 = $startYear.'-'.$monthValue.'-'.$dayValue.' 23:59:59';
                        $collection = $this->saleslistFactory->create()
                                    ->getCollection()
                                    ->addFieldToFilter(
                                        'main_table.seller_id',
                                        ['eq' => $sellerId]
                                    )
                                    ->addFieldToFilter(
                                        'main_table.order_id',
                                        ['neq' => 0]
                                    )->addFieldToFilter(
                                        'main_table.created_at',
                                        ['datetime' => true, 'from' => $date1, 'to' => $date2]
                                    )->getPricebyorderData();
                        $temp = 0;
                        foreach ($collection as $record) {
                            // calculate order actual_seller_amount in base currency
                            $appliedCouponAmount = $record['applied_coupon_amount']*1;
                            $shippingAmount = $record['shipping_charges']*1;
                            $refundedShippingAmount = $record['refunded_shipping_charges']*1;
                            $totalshipping = $shippingAmount - $refundedShippingAmount;
                            if ($record['actual_seller_amount'] * 1) {
                                $taxShippingTotal = $totalshipping - $appliedCouponAmount;
                                $temp += $record['actual_seller_amount'] + $taxShippingTotal;
                            } else {
                                if ($totalshipping * 1) {
                                    $temp += $totalshipping - $appliedCouponAmount;
                                }
                            }
                        }
                        if ($temp) {
                            $dataSet[] = $temp;
                            $dataLabel[] = $dayValue."/".$monthValue."/".$startYear;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger("Block_Account_Earnings getDailyData : ".$e->getMessage());
        }
        return [$dataSet, $dataLabel];
    }
    /**
     * getMonthlyData function is used to get sale according to months
     *
     * @return mixed[string]
     */
    protected function getMonthlyData()
    {
        $dataSet = [];
        $dataLabel = [];
        try {
            list($from, $to) = $this->getDateData();
            if ($to) {
                $todate = date_create($to);
                $to = date_format($todate, 'Y-m-d 23:59:59');
            }
            if (!$to) {
                $to = date('Y-m-d 23:59:59');
            }
            if ($from) {
                $fromdate = date_create($from);
                $from = date_format($fromdate, 'Y-m-d H:i:s');
            }
            if (!$from) {
                $from = date('Y-m-d 23:59:59', strtotime($from));
            }
            $sellerId = $this->mpHelper->getCustomerId();
            $fromYear = $from ? date('Y', strtotime($from)) : date('Y');
            $fromDay = $from ? (int)date('d', strtotime($from)) : 1;
            $fromMonth = $from ? (int)date('m', strtotime($from)) : 1;
            $curryear = $to ? date('Y', strtotime($to)) : date('Y');
            $currMonth = $to ? (int)date('m', strtotime($to)) : date('m');
            $currDay = $to ? (int)date('d', strtotime($to)) : date('d');
            for ($startYear = $fromYear; $startYear <= $curryear; ++$startYear) {
                $months = 12;
                if ($startYear == $curryear) {
                    $months = $currMonth;
                }
                $monthStart = ($startYear == $fromYear && $from) ? $fromMonth : 1;
                for ($monthValue = $monthStart; $monthValue <= $months; ++$monthValue) {
                    $days = $this->getMonthDays($monthValue, $startYear);
                    $dayStart = ($startYear == $fromYear && ($fromMonth == $monthValue) && $from) ? $fromDay : '01';
                    $dayEnd = ($startYear == $curryear && ($currMonth == $monthValue) && $from) ? $currDay : $days;
                    $date1 = $startYear.'-'.$monthValue.'-'.$dayStart.' 00:00:00';
                    $date2 = $startYear.'-'.$monthValue.'-'.$dayEnd.' 23:59:59';
                    $collection = $this->saleslistFactory->create()
                                ->getCollection()
                                ->addFieldToFilter(
                                    'main_table.seller_id',
                                    ['eq' => $sellerId]
                                )
                                ->addFieldToFilter(
                                    'main_table.order_id',
                                    ['neq' => 0]
                                )->addFieldToFilter(
                                    'main_table.created_at',
                                    ['datetime' => true, 'from' => $date1, 'to' => $date2]
                                )->getPricebyorderData();
                    $temp = 0;
                    foreach ($collection as $record) {
                        // calculate order actual_seller_amount in base currency
                        $appliedCouponAmount = $record['applied_coupon_amount']*1;
                        $shippingAmount = $record['shipping_charges']*1;
                        $refundedShippingAmount = $record['refunded_shipping_charges']*1;
                        $totalshipping = $shippingAmount - $refundedShippingAmount;
                        if ($record['actual_seller_amount'] * 1) {
                            $taxShippingTotal = $totalshipping - $appliedCouponAmount;
                            $temp += $record['actual_seller_amount'] + $taxShippingTotal;
                        } else {
                            if ($totalshipping * 1) {
                                $temp += $totalshipping - $appliedCouponAmount;
                            }
                        }
                    }
                    if ($temp) {
                        $dataLabel[] = $monthValue."/".$startYear;
                        $dataSet[] = $temp;
                    }
                }
            }
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger("Block_Account_Earnings getMonthlyData : ".$e->getMessage());
        }
        return [$dataSet, $dataLabel];
    }
    /**
     * getYearlyDataMonthlyData function is used to get sale according to months
     *
     * @return mixed[string]
     */
    protected function getYearlyData()
    {
        $dataSet = [];
        $dataLabel = [];
        try {
            list($from, $to) = $this->getDateData();
            $sellerId = $this->mpHelper->getCustomerId();
            if ($to) {
                $todate = date_create($to);
                $to = date_format($todate, 'Y-m-d 23:59:59');
            }
            if (!$to) {
                $to = date('Y-m-d 23:59:59');
            }
            if ($from) {
                $fromdate = date_create($from);
                $from = date_format($fromdate, 'Y-m-d H:i:s');
            }
            if (!$from) {
                $from = date('Y-m-d 23:59:59', strtotime($from));
            }
            $fromYear = $from ? date('Y', strtotime($from)) : date('Y');
            $fromMonth = $from ? (int)date('m', strtotime($from)) : 1;
            $fromDay = $from ? (int)date('d', strtotime($from)) : 1;
            $curryear = $to ? date('Y', strtotime($to)) : date('Y');
            $currMonth = $to ? (int)date('m', strtotime($to)) : date('m');
            $currDay = $to ? (int)date('d', strtotime($to)) : date('d');
            for ($start = $fromYear; $start <= $curryear; ++$start) {
                $monthStart = ($start == $fromYear) ? $fromMonth : '01';
                $monthEnd = ($start == $curryear) ? $currMonth : '12';
                $days = $this->getMonthDays($monthEnd, $start);
                $dayStart = ($start == $fromYear && $from) ? $fromDay : '01';
                $dayEnd = ($start == $curryear && $from) ? $currDay : $days;
                $date1 = $start.'-'.$monthStart.'-'.$dayStart.' 00:00:00';
                $date2 = $start.'-'.$monthEnd.'-'.$dayEnd.' 23:59:59';
                $collection = $this->saleslistFactory->create()
                            ->getCollection()
                            ->addFieldToFilter(
                                'main_table.seller_id',
                                ['eq' => $sellerId]
                            )
                            ->addFieldToFilter(
                                'main_table.order_id',
                                ['neq' => 0]
                            )->addFieldToFilter(
                                'main_table.created_at',
                                ['datetime' => true, 'from' => $date1, 'to' => $date2]
                            )->getPricebyorderData();
                $temp = 0;
                foreach ($collection as $record) {
                    // calculate order actual_seller_amount in base currency
                    $appliedCouponAmount = $record['applied_coupon_amount']*1;
                    $shippingAmount = $record['shipping_charges']*1;
                    $refundedShippingAmount = $record['refunded_shipping_charges']*1;
                    $totalshipping = $shippingAmount - $refundedShippingAmount;
                    if ($record['actual_seller_amount'] * 1) {
                        $taxShippingTotal = $totalshipping - $appliedCouponAmount;
                        $temp += $record['actual_seller_amount'] + $taxShippingTotal;
                    } else {
                        if ($totalshipping * 1) {
                            $temp += $totalshipping - $appliedCouponAmount;
                        }
                    }
                }
                if ($temp) {
                    $dataLabel[] = $start;
                    $dataSet[] = $temp;
                }
            }
        } catch (\Exception $e) {
            $this->mpHelper->logDataInLogger("Block_Account_Earnings getYearlyData : ".$e->getMessage());
        }
        return [$dataSet, $dataLabel];
    }
    
    /**
     * @param $data
     * @return array
     */
    public function prepareDataSet($data)
    {
        return $data = "[{
                label: '".__('Sale')."',
                backgroundColor: color(window.chartColors.green).alpha(0.5).rgbString(),
                borderColor: window.chartColors.green,
                borderWidth: 1,
                data: $data
            }
        ]";
    }

    public function getDateData()
    {
        $sellerId = $this->mpHelper->getCustomerId();
        $params = $this->getRequest()->getParams();
        $from = $params['from'] ?? '';
        $to = $params['to'] ?? '';
        if (!$from && !$to) {
            $collection = $this->saleslistFactory->create()
            ->getCollection()
            ->addFieldToFilter(
                'main_table.seller_id',
                ['eq' => $sellerId]
            )
            ->addFieldToFilter(
                'main_table.order_id',
                ['neq' => 0]
            )->setOrder('created_at', 'ASC')->getFirstItem();
            $from = $collection->getCreatedAt();
            $to = date("Y-m-d");
        }
        return [$from, $to];
    }

    public function getMonthDays($month, $year)
    {
        $days = 28;
        if ((0 == $year % 4) and (0 != $year % 100) or (0 == $year % 400)) {
            $days = 29;
        }
        $monthsWithThirty = [4,6,9,11];
        $monthsWithThirtyOne = [1,3,5,7,8,10,12];
        if (in_array($month, $monthsWithThirty)) {
            $days = 30;
        } elseif (in_array($month, $monthsWithThirtyOne)) {
            $days = 31;
        }
        return $days;
    }
}
