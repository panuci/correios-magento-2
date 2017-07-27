<?php
namespace Panuci\Correios\Model\Carrier;
 
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Rate\Result;
 
class Correios extends \Magento\Shipping\Model\Carrier\AbstractCarrier implements
    \Magento\Shipping\Model\Carrier\CarrierInterface
{
    /**
     * @var string
     */
    protected $_code = 'correios';
 
    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
        \Psr\Log\LoggerInterface $logger,
        \Magento\Shipping\Model\Rate\ResultFactory $rateResultFactory,
        \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
        \Panuci\Correios\Model\Webservice $_wsApi,
        \Panuci\Correios\Helper\Data $helperData,
        array $data = []
    ) {
        $this->_rateResultFactory = $rateResultFactory;
        $this->_rateMethodFactory = $rateMethodFactory;
        $this->_rateErrorFactory = $rateErrorFactory;
        $this->_scopeConfig = $scopeConfig;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_api = $_wsApi;
        $this->_helper = $helperData;
        parent::__construct($scopeConfig, $rateErrorFactory, $logger, $data);
    }
 
    /**
     * @return array
     */
    public function getAllowedMethods()
    {
        return ['correios' => $this->getConfigData('name')];
    }
 
    /**
     * @param RateRequest $request
     * @return bool|Result
     */
    public function collectRates(RateRequest $request)
    {
        if (!$this->getConfigFlag('active')) {
            return false;
        }

        $result = $this->_rateResultFactory->create();
        $apiResponse = $this->_api->getWebserviceRates($request);

        if($apiResponse == false){
            return $result->append($this->_getErrorMethod());
        }

        foreach ($apiResponse as $key => $carrier) {
            if(isset($carrier['msgErro'])){
                continue;
            }

            $method = $this->_rateMethodFactory->create();
            $method->setCarrier($this->_code);
            $method->setCarrierTitle('Teste de titulo do carrier');

            $method->setMethod('correios-' . $carrier['codigo']);
            $method->setMethodTitle($this->_objectManager->get('Panuci\Correios\Helper\Data')->getMethodTitle($carrier['codigo'], $carrier['prazo']));
            $method->setPrice($carrier['preco']);
            $result->append($method);
        }
 
        return $result;
    }

    private function _getErrorMethod(){
        $error = $this->_rateErrorFactory->create();
        $error->setCarrier($this->_code);
        $error->setMethodTitle($this->_helper->getConfig('carriers/correios/specificerrmsg'));
    }
}