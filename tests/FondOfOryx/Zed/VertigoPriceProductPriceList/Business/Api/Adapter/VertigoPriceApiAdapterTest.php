<?php

namespace FondOfOryx\Zed\VertigoPriceProductPriceList\Business\Api\Adapter;

use Codeception\Test\Unit;
use Exception;
use FondOfOryx\Zed\VertigoPriceProductPriceList\Business\Api\Mapper\VertigoPriceApiResponseMapperInterface;
use FondOfOryx\Zed\VertigoPriceProductPriceList\Dependency\Service\VertigoPriceProductPriceListToUtilEncodingServiceInterface;
use Generated\Shared\Transfer\VertigoPriceApiRequestTransfer;
use Generated\Shared\Transfer\VertigoPriceApiResponseTransfer;
use GuzzleHttp\ClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

class VertigoPriceApiAdapterTest extends Unit
{
    /**
     * @var \GuzzleHttp\ClientInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $clientMock;

    /**
     * @var \FondOfOryx\Zed\VertigoPriceProductPriceList\Business\Api\Mapper\VertigoPriceApiResponseMapperInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $vertigoPriceApiResponseMapperMock;

    /**
     * @var \FondOfOryx\Zed\VertigoPriceProductPriceList\Dependency\Service\VertigoPriceProductPriceListToUtilEncodingServiceInterface|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $utilEncodingServiceMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Log\LoggerInterface
     */
    protected $loggerMock;

    /**
     * @var \Generated\Shared\Transfer\VertigoPriceApiRequestTransfer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $vertigoPriceApiRequestTransferMock;

    /**
     * @var \Generated\Shared\Transfer\VertigoPriceApiResponseTransfer|\PHPUnit\Framework\MockObject\MockObject
     */
    protected $vertigoPriceApiResponseTransferMock;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|\Psr\Http\Message\ResponseInterface
     */
    protected $responseMock;

    /**
     * @var \FondOfOryx\Zed\VertigoPriceProductPriceList\Business\Api\Adapter\VertigoPriceApiAdapter
     */
    protected $vertigoPriceApiAdapter;

    /**
     * @Override
     *
     * @return void
     */
    protected function _before(): void
    {
        parent::_before();

        $this->clientMock = $this->getMockBuilder(ClientInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->vertigoPriceApiResponseMapperMock = $this->getMockBuilder(VertigoPriceApiResponseMapperInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->utilEncodingServiceMock = $this->getMockBuilder(VertigoPriceProductPriceListToUtilEncodingServiceInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->loggerMock = $this->getMockBuilder(LoggerInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->vertigoPriceApiRequestTransferMock = $this->getMockBuilder(VertigoPriceApiRequestTransfer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->vertigoPriceApiResponseTransferMock = $this->getMockBuilder(VertigoPriceApiResponseTransfer::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->responseMock = $this->getMockBuilder(ResponseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->vertigoPriceApiAdapter = new VertigoPriceApiAdapter(
            $this->clientMock,
            $this->vertigoPriceApiResponseMapperMock,
            $this->utilEncodingServiceMock,
            $this->loggerMock,
        );
    }

    /**
     * @return void
     */
    public function testSendRequest(): void
    {
        $body = [
            'skus' => ['foo-bar-1', 'foo-bar-2'],
        ];

        $bodyAsJson = json_encode($body, JSON_THROW_ON_ERROR);

        $this->vertigoPriceApiRequestTransferMock->expects(static::atLeastOnce())
            ->method('getBody')
            ->willReturn($body);

        $this->utilEncodingServiceMock->expects(static::atLeastOnce())
            ->method('encodeJson')
            ->with($body)
            ->willReturn($bodyAsJson);

        $this->clientMock->expects(static::atLeastOnce())
            ->method('request')
            ->with('POST', '/prices-to-shop/cache/prices', static::callback(
                static function (array $options) use ($bodyAsJson) {
                    return $options['body'] === $bodyAsJson;
                },
            ))->willReturn($this->responseMock);

        $this->vertigoPriceApiResponseMapperMock->expects(static::atLeastOnce())
            ->method('fromResponse')
            ->with($this->responseMock)
            ->willReturn($this->vertigoPriceApiResponseTransferMock);

        $this->loggerMock->expects(static::never())
            ->method('error');

        static::assertEquals(
            $this->vertigoPriceApiResponseTransferMock,
            $this->vertigoPriceApiAdapter->sendRequest($this->vertigoPriceApiRequestTransferMock),
        );
    }

    /**
     * @return void
     */
    public function testSendRequestWithError(): void
    {
        $body = [
            'skus' => ['foo-bar-1', 'foo-bar-2'],
        ];

        $bodyAsJson = json_encode($body, JSON_THROW_ON_ERROR);

        $exception = new Exception('foo');

        $this->vertigoPriceApiRequestTransferMock->expects(static::atLeastOnce())
            ->method('getBody')
            ->willReturn($body);

        $this->utilEncodingServiceMock->expects(static::atLeastOnce())
            ->method('encodeJson')
            ->with($body)
            ->willReturn($bodyAsJson);

        $this->clientMock->expects(static::atLeastOnce())
            ->method('request')
            ->with('POST', '/prices-to-shop/cache/prices', static::callback(
                static function (array $options) use ($bodyAsJson) {
                    return $options['body'] === $bodyAsJson;
                },
            ))->willThrowException($exception);

        $this->vertigoPriceApiResponseMapperMock->expects(static::never())
            ->method('fromResponse')
            ->with($this->responseMock);

        $this->loggerMock->expects(static::atLeastOnce())
            ->method('error')
            ->with('foo', ['trace' => $exception->getTraceAsString()]);

        static::assertFalse(
            $this->vertigoPriceApiAdapter->sendRequest($this->vertigoPriceApiRequestTransferMock)
                ->getIsSuccessful(),
        );
    }
}
