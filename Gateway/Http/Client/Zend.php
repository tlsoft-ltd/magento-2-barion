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

namespace TLSoft\BarionGateway\Gateway\Http\Client;

use LogicException;
use Laminas\Http\Exception\RuntimeException;
use Magento\Framework\HTTP\LaminasClientFactory;
use Magento\Payment\Gateway\Http\ClientException;
use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\ConverterException;
use Magento\Payment\Gateway\Http\ConverterInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Magento\Payment\Model\Method\Logger;
use Laminas\Http\Request;

/**
 * Class Zend
 * @property ZendClientFactory $clientFactory
 * @property ConverterInterface|null $converter
 * @property Logger $logger
 * @package Magento\Payment\Gateway\Http\Client
 * @api
 * @since 100.0.2
 */
class Zend extends \Magento\Payment\Gateway\Http\Client\Zend implements ClientInterface
{

    private $clientFactory;

    private $converter;

    private $logger;
    /**
     * @param ZendClientFactory $clientFactory
     * @param Logger $logger
     * @param ConverterInterface | null $converter
     */
    public function __construct(
        LaminasClientFactory  $clientFactory,
        Logger             $logger,
        ConverterInterface $converter = null
    )
    {
        $this->clientFactory = $clientFactory;
        $this->converter = $converter;
        $this->logger = $logger;
    }

    /**
     * {inheritdoc}
     */
    public function placeRequest(TransferInterface $transferObject): array
    {
        $log = [
            'request' => $transferObject->getBody(),
            'request_uri' => $transferObject->getUri()
        ];
        $result = [];

        $client = $this->clientFactory->create();

        $client->setConfig($transferObject->getClientConfig());
        $client->setMethod($transferObject->getMethod());

        switch ($transferObject->getMethod()) {
            case Request::METHOD_GET:
                $client->setParameterGet($transferObject->getBody());
                break;
            case Request::METHOD_POST:
                $client->setRawData($transferObject->getBody(), "application/json");
                break;
            default:
                throw new LogicException(
                    sprintf(
                        'Unsupported HTTP method %s',
                        $transferObject->getMethod()
                    )
                );
        }

        $client->setHeaders($transferObject->getHeaders());
        $client->setUrlEncodeBody($transferObject->shouldEncode());
        $client->setUri($transferObject->getUri());

        try {
            $response = $client->request();

            $result = $this->converter
                ? $this->converter->convert($response->getBody())
                : [$response->getBody()];
            $log['response'] = $result;
        } catch (RuntimeException $e) {
            throw new ClientException(
                __($e->getMessage())
            );
        } catch (ConverterException $e) {
            throw $e;
        } finally {
            $this->logger->debug($log);
        }

        return $result;
    }
}
