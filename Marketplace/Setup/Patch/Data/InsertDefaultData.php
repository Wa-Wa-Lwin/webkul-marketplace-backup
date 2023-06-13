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
namespace Webkul\Marketplace\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;
use Webkul\Marketplace\Model\ControllersRepository;

class InsertDefaultData implements DataPatchInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @var ControllersRepository
     */
    private $controllersRepository;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     * @param ControllersRepository $controllersRepository
     */
    public function __construct(
        ModuleDataSetupInterface $moduleDataSetup,
        ControllersRepository $controllersRepository
    ) {
        $this->moduleDataSetup = $moduleDataSetup;
        $this->controllersRepository = $controllersRepository;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $data = [];
        $this->moduleDataSetup->getConnection()->startSetup();
        $connection = $this->moduleDataSetup->getConnection();
        if (!count($this->controllersRepository->getByPath('marketplace/account/dashboard'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/account/dashboard',
                'label' => 'Marketplace Dashboard',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/account/editprofile'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/account/editprofile',
                'label' => 'Seller Profile',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/product_attribute/new'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/product_attribute/new',
                'label' => 'Create Attribute',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/product/add'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/product/add',
                'label' => 'New Products',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/product/productlist'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/product/productlist',
                'label' => 'My Products List',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/account/customer'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/account/customer',
                'label' => 'My Customer List',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/account/review'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/account/review',
                'label' => 'My Review List',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/transaction/history'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/transaction/history',
                'label' => 'My Transaction List',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/order/shipping'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/order/shipping',
                'label' => 'Manage Print PDF Header Info',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }
        if (!count($this->controllersRepository->getByPath('marketplace/order/history'))) {
            $data[] = [
                'module_name' => 'Webkul_Marketplace',
                'controller_path' => 'marketplace/order/history',
                'label' => 'My Order History',
                'is_child' => '0',
                'parent_id' => '0',
            ];
        }

        $connection->insertMultiple($this->moduleDataSetup->getTable('marketplace_controller_list'), $data);
        $this->moduleDataSetup->getConnection()->endSetup();
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [];
    }
}
