<?php
namespace Axitech\Repair\Controller\Ticket;

use Magento\Framework\App\Action\Context;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Data\Form\FormKey\Validator;
use Magento\Framework\Stdlib\DateTime;
use Axitech\Repair\Model\TicketFactory;

class Save extends \Axitech\Repair\Controller\Ticket {
    protected $transportBuilder;
    protected $inlineTranslation;
    protected $scopeConfig;
    protected $storeManager;
    protected $formKeyValidator;
    protected $dateTime;
    protected $ticketFactory;

    public function __construct(
        Context $context, 
        CustomerSession $customerSession, 
        TransportBuilder $transportBuilder, 
        StateInterface $inlineTranslation, 
        ScopeConfigInterface $scopeConfig, 
        StoreManagerInterface $storeManager, 
        Validator $formKeyValidator, 
        DateTime $dateTime, 
        TicketFactory $ticketFactory
    ) {
        
        $this->transportBuilder = $transportBuilder;
        $this->inlineTranslation = $inlineTranslation;
        $this->scopeConfig = $scopeConfig;
        $this->storeManager = $storeManager;
        $this->formKeyValidator = $formKeyValidator;
        $this->dateTime = $dateTime;
        $this->ticketFactory = $ticketFactory;
        $this->messageManager = $context->getMessageManager(); //get Message block
        parent::__construct($context, $customerSession);
    }

    public function execute() {
        $resultRedirect = $this->resultRedirectFactory->create();

        //var_dump($this->getRequest()->getParam('title'));
        //var_dump($this->getRequest()->getParam('severity'));

        if(!$this->formKeyValidator->validate($this->getRequest())) {
            return $resultRedirect->setRefererUrl();
        }
        $title = $this->getRequest()->getParam('title');

        try{
            $ticket = $this->ticketFactory->create();
            $ticket->setCustomerId($this->customerSession->getCustomerId());
            $ticket->setTitle($title);
            $ticket->setCreatedAt($this->dateTime->formatDate(true));
            $ticket->setStatus(\Axitech\Repair\Model\Ticket::STATUS_OPENED);
            $ticket->save();

            $customer = $this->customerSession->getCustomerData();
            /*
            //Send email to store owner
            $storeScope = \Magento\Store\Model\ScopeInterface::SCOPE_STORE;
            $transport = $this->transportBuilder->setTemplateIdentifier($this->scopeConfig->getValue('axitech_repair/email_template/store_owner', $storeScope))
                ->setTemplateOptions(
                    [
                        'area' => \Magento\Framework\App\Area::AREA_FRONTEND,
                        'store' => $this->storeManager->getStore()->getId(),
                    ]
                )
                ->setTemplateVars(['ticket' => $ticket])
                ->setFrom([
                    'name' => $customer->getFirstName. ' ' . $customer->getLastname(), 
                    'email' => $customer->getEmail()
                ])
                ->addTo($this->scopeConfig->getValue('trans_email/ident_general/email', $storeScope))
                ->getTransport();
            $transport->sendMessage();
            $this->inlineTranslation->resume();
            */

            $this->messageManager->addSuccess(__('Ticket successfully created.'));
        }
        catch(Exception $e) {
            $this->messageManager->addError(__('Error occurred during ticket creation.'));
        }

        return $resultRedirect->setRefererUrl();
    }
}
