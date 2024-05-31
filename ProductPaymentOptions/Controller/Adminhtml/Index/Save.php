<?php

namespace Fineweb\ProductPaymentOptions\Controller\Adminhtml\Index;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Fineweb\ProductPaymentOptions\Model\ProductPaymentOptionsFactory;

class Save extends Action
{
    protected $pageFactory;
    protected $paymentOptionsFactory;

    public function __construct(
        Context $context,
        PageFactory $pageFactory,
        ProductPaymentOptionsFactory $paymentOptionsFactory
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->paymentOptionsFactory = $paymentOptionsFactory;
    }

    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        $model = $this->paymentOptionsFactory->create();
        $model->setData($data);
        $model->save();
        $this->messageManager->addSuccessMessage(__('The payment options have been saved.'));
        return $this->_redirect('*/*/index');
    }
}
