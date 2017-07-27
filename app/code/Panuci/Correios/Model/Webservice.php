<?php

namespace Panuci\Correios\Model;

use Magento\Cron\Exception;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\HTTP\Client\Curl;

/**
 * Webservice Model
 *
 * @author      Vitor Panuci
 */
class Webservice extends AbstractModel 
{

    protected $_ratesUrl = 'http://ws.correios.com.br/calculador/CalcPrecoPrazo.aspx';
    protected $_params;
    protected $_curl;
    protected $_logger;

    public function __construct(
        \Magento\Framework\HTTP\Client\Curl $curl,
        \Panuci\Correios\Helper\Laff $helperLaff,
        \Panuci\Correios\Helper\Data $helperData,
        \Psr\Log\LoggerInterface $logger
    ){
        $this->_curl = $curl;
        $this->_objectManager = \Magento\Framework\App\ObjectManager::getInstance();
        $this->_helperLaff = $helperLaff;
        $this->_helper = $helperData;
        $this->_logger = $logger;
    }

    public function getWebserviceRates($request){

        if(!$this->_prepareCollectRatesData($request)){
            return false;
        }

        if(!$xml = $this->_getWebserviceReturn($this->_ratesUrl, $this->_params)){
            return false;
        }

        try{
            $return = array();
            foreach ($xml['cServico'] as $key => $servico) {
                if(!empty($servico['Erro']) || !empty($servico['MsgErro'])){
                    $error['codigo'] = $servico['Codigo'];
                    $error['msgErro'] = $servico['MsgErro'] . ' Erro(' . $servico['Erro'] . ')';
                    $return[] = $error;
                    unset($error);
                    continue;
                }else{
                    $method['codigo'] = $servico['Codigo'];
                    $method['preco'] = $this->_helper->priceFormat($servico['Valor']);
                    $method['prazo'] = $this->_helper->prazoFormat($servico['PrazoEntrega']);
                    $return[] = $method;
                    unset($method);
                }
            }
        }catch(Exception $e){
            $this->_logger->debug('Ocorreu um erro ao carregar as informações do método. ' . $e->getMessage());
            return false;
        }

        return $return;
    }

    protected function _getWebserviceReturn($_url, $params, $type = 'GET')
    {
        try{
            if($type == 'GET'){
                $this->_curl->get($_url . '?' . http_build_query($params));        
            }

            return $this->_helper->xmlarray($this->_curl->getBody());
        }catch(Exception $e){
            $this->_logger->debug('Ocorreu um erro ao coletar informações para envio. ' . $e->getMessage());

            return false;
        }
    }


    protected function _prepareCollectRatesData($request){
        try{
            $this->_params = array();

            // nCdEmpresa
            $nCdEmpresa = $this->_helper->getConfig('carriers/correios/cdempresa');
            $this->_params['nCdEmpresa'] = $nCdEmpresa ? $nCdEmpresa : '';

            // sDsSenha
            $sDsSenha = $this->_helper->getConfig('carriers/correios/dssenha');
            $this->_params['sDsSenha'] = $sDsSenha ? $sDsSenha : '';

            // nCdServico
            $nCdServico = $this->_helper->getConfig('carriers/correios/cdservico');
            $this->_params['nCdServico'] = $nCdServico;

            // sCepOrigem
            $sCepOrigem = trim(str_replace('-','',$this->_helper->getConfig('general/store_information/postcode')));
            $this->_params['sCepOrigem'] = $sCepOrigem;

            // sCepDestino
            $sCepDestino = trim(str_replace('-','',$request->getDestPostcode()));
            $this->_params['sCepDestino'] = $sCepDestino;

            // nVlPeso - Check weight and convert to an sensate unit
            $lsWeight = $request->getPackageWeight();
            $lsWeight = $this->_helper->getConfig('general/locale/weight_unit') == 'lbs' ? $lsWeight * 0.453592 : $lsWeight;       
            if($lsWeight <= 30){
                $this->_params['nVlPeso'] = $lsWeight;
            }

            // nCdFormato
            /* 1 => Caixa/pacote  2 => Rolo/Prisma  3 => Envelope */
            $this->_params['nCdFormato'] = 1;

            $_boxes = $this->_helper->getBoxes($request);

            // An awesome work by Maarten de Boer - Check README for more info
            $this->_helperLaff->pack($_boxes);
            $laDimensoes = $this->_helperLaff->get_container_dimensions();

            // nVlComprimento
            $this->_params['nVlComprimento'] = $laDimensoes['width'];

            // nVlAltura
            $this->_params['nVlAltura'] = $laDimensoes['height'];

            // nVlLargura
            $this->_params['nVlLargura'] = $laDimensoes['length'];

            // Validar limite mínimo de dimensões
            $this->_validarDimensoes();

            // sCdMaoPropria
            $sCdMaoPropria = $this->_helper->getConfig('carriers/correios/cdmaopropria');
            $this->_params['sCdMaoPropria'] = $sCdMaoPropria ? 'S' : 'N';

            // nVlValorDeclarado
            if($this->_helper->getConfig('carriers/correios/nvlvalordeclarado')){
                $this->_params['nVlValorDeclarado'] = $request->getPackageValue();
            }else{
                $this->_params['nVlValorDeclarado'] = 0;
            }

            //sCdAvisoRecebimento
            $sCdAvisoRecebimento = $this->_helper->getConfig('carriers/correios/cdavisorecebimento');
            $this->_params['sCdAvisoRecebimento'] = $sCdAvisoRecebimento ? 'S' : 'N';

            $this->_params['StrRetorno'] = 'xml';

            return true;
        }catch(Exception $e){
            $this->_logger->debug('Ocorreu um erro ao montar as informações para envio. ' . $e->getMessage());
            return false;
        }
    }

    private function _validarDimensoes(){
        if($this->_helper->getConfig('carriers/correios/limiteminimo')){
            $this->_params['nVlComprimento'] = $this->_params['nVlComprimento'] < 16 ? $this->_params['nVlComprimento'] : 16;
            $this->_params['nVlLargura'] = $this->_params['nVlLargura'] < 11 ? $this->_params['nVlLargura'] : 11;
            $this->_params['nVlAltura'] = $this->_params['nVlAltura'] < 2 ? $this->_params['nVlAltura'] : 2;
        }
    }
}