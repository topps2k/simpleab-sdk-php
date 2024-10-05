<?php

namespace SimpleAB\SDK\Tests;

use PHPUnit\Framework\TestCase;
use SimpleAB\SDK\SimpleABSDK;
use SimpleAB\SDK\AggregationTypes;
use SimpleAB\SDK\Stages;
use SimpleAB\SDK\Treatments;
use SimpleAB\SDK\Segment;

class SimpleABSDKTest extends TestCase
{
    private $apiURL = 'https://api.example.com';
    private $apiKey = 'test_api_key';

    protected function setUp(): void
    {
        parent::setUp();
    }

    public function testConstructor()
    {
        $sdk = new SimpleABSDK($this->apiURL, $this->apiKey);
        $this->assertInstanceOf(SimpleABSDK::class, $sdk);
    }

    public function testGetTreatment()
    {
        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $experimentData = [
            'id' => 'exp1',
            'allocationRandomizationToken' => 'alloc_token',
            'exposureRandomizationToken' => 'expo_token',
            'stages' => [
                [
                    'stage' => Stages::BETA,
                    'stageDimensions' => [
                        [
                            'dimension' => 'default',
                            'enabled' => true,
                            'exposure' => 100,
                            'treatmentAllocations' => [
                                ['id' => Treatments::CONTROL, 'allocation' => 50],
                                ['id' => 'T1', 'allocation' => 50]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $sdk->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['success' => [$experimentData]]);

        $treatment = $sdk->getTreatment('exp1', Stages::BETA, 'default', 'user123');
        $this->assertContains($treatment, [Treatments::CONTROL, 'T1', Treatments::NONE]);
    }

    public function testTrackMetric()
    {
        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $experimentData = [
            'id' => 'exp1',
            'stages' => [
                [
                    'stage' => Stages::BETA,
                    'stageDimensions' => [
                        [
                            'dimension' => 'default',
                            'enabled' => true,
                            'treatmentAllocations' => [
                                ['id' => 'T1', 'allocation' => 100]
                            ]
                        ]
                    ]
                ]
            ],
            'treatments' => [
                ['id' => 'T1']
            ]
        ];

        $sdk->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['success' => [$experimentData]]);

        $sdk->trackMetric([
            'experimentID' => 'exp1',
            'stage' => Stages::BETA,
            'dimension' => 'default',
            'treatment' => 'T1',
            'metricName' => 'clicks',
            'metricValue' => 1
        ]);

        $buffer = $sdk->getBuffer();
        $this->assertArrayHasKey('exp1-Beta-default-T1-clicks-sum', $buffer);
        $this->assertEquals(1, $buffer['exp1-Beta-default-T1-clicks-sum']['sum']);
        $this->assertEquals(1, $buffer['exp1-Beta-default-T1-clicks-sum']['count']);
    }

    public function testFlushMetrics()
    {
        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $experimentData = [
            'id' => 'exp1',
            'stages' => [
                [
                    'stage' => Stages::BETA,
                    'stageDimensions' => [
                        [
                            'dimension' => 'default',
                            'enabled' => true,
                            'treatmentAllocations' => [
                                ['id' => 'T1', 'allocation' => 100]
                            ]
                        ]
                    ]
                ]
            ],
            'treatments' => [
                ['id' => 'T1']
            ]
        ];

        $sdk->expects($this->exactly(2))
            ->method('makeApiRequest')
            ->willReturnOnConsecutiveCalls(
                ['success' => [$experimentData]],
                ['success' => true]
            );

        $sdk->trackMetric([
            'experimentID' => 'exp1',
            'stage' => Stages::BETA,
            'dimension' => 'default',
            'treatment' => 'T1',
            'metricName' => 'clicks',
            'metricValue' => 1
        ]);

        $sdk->flushMetrics();

        $buffer = $sdk->getBuffer();
        $this->assertEmpty($buffer);
    }

    public function testGetCache()
    {
        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $experimentData = [
            'id' => 'exp1',
            'allocationRandomizationToken' => 'alloc_token',
            'exposureRandomizationToken' => 'expo_token',
            'stages' => [
                [
                    'stage' => Stages::BETA,
                    'stageDimensions' => [
                        [
                            'dimension' => 'default',
                            'enabled' => true,
                            'exposure' => 100,
                            'treatmentAllocations' => [
                                ['id' => Treatments::CONTROL, 'allocation' => 50],
                                ['id' => 'T1', 'allocation' => 50]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $sdk->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['success' => [$experimentData]]);

        $sdk->getTreatment('exp1', Stages::BETA, 'default', 'user123');

        $cache = $sdk->getCache();
        $this->assertArrayHasKey('exp1', $cache);
        $this->assertEquals($experimentData, $cache['exp1']);
    }

    public function testTrackMetricWithInvalidTreatment()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid treatment string');

        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $sdk->expects($this->never())
            ->method('makeApiRequest');

        $sdk->trackMetric([
            'experimentID' => 'exp1',
            'stage' => Stages::BETA,
            'dimension' => 'default',
            'treatment' => 'InvalidTreatment',
            'metricName' => 'clicks',
            'metricValue' => 1
        ]);
    }

    public function testTrackMetricWithInvalidStage()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid stage string');

        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $sdk->expects($this->never())
            ->method('makeApiRequest');

        $sdk->trackMetric([
            'experimentID' => 'exp1',
            'stage' => 'InvalidStage',
            'dimension' => 'default',
            'treatment' => 'T1',
            'metricName' => 'clicks',
            'metricValue' => 1
        ]);
    }

    public function testTrackMetricWithInvalidAggregationType()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid aggregation type: invalid');

        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $sdk->expects($this->never())
            ->method('makeApiRequest');

        $sdk->trackMetric([
            'experimentID' => 'exp1',
            'stage' => Stages::BETA,
            'dimension' => 'default',
            'treatment' => 'T1',
            'metricName' => 'clicks',
            'metricValue' => 1,
            'aggregationType' => 'invalid'
        ]);
    }

    public function testTrackMetricWithNegativeValueForSum()
    {
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Metric clicks cannot be negative for AggregationTypes::SUM');

        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $experimentData = [
            'id' => 'exp1',
            'stages' => [
                [
                    'stage' => Stages::BETA,
                    'stageDimensions' => [
                        [
                            'dimension' => 'default',
                            'enabled' => true,
                            'treatmentAllocations' => [
                                ['id' => 'T1', 'allocation' => 100]
                            ]
                        ]
                    ]
                ]
            ],
            'treatments' => [
                ['id' => 'T1']
            ]
        ];

        $sdk->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['success' => [$experimentData]]);

        $sdk->trackMetric([
            'experimentID' => 'exp1',
            'stage' => Stages::BETA,
            'dimension' => 'default',
            'treatment' => 'T1',
            'metricName' => 'clicks',
            'metricValue' => -1,
            'aggregationType' => AggregationTypes::SUM
        ]);
    }

    public function testGetSegment()
    {
        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest'])
            ->getMock();

        $segmentData = [
            'countryCode' => 'US',
            'region' => 'CA',
            'deviceType' => 'mobile'
        ];

        $sdk->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn($segmentData);

        $segment = $sdk->getSegment(['ip' => '1.2.3.4', 'userAgent' => 'Test User Agent']);

        $this->assertInstanceOf(Segment::class, $segment);
        $this->assertEquals('US', $segment->countryCode);
        $this->assertEquals('CA', $segment->region);
        $this->assertEquals('mobile', $segment->deviceType);
    }

    public function testGetTreatmentWithSegment()
    {
        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest', 'getTreatment'])
            ->getMock();

        $experimentData = [
            'id' => 'exp1',
            'stages' => [
                [
                    'stage' => Stages::BETA,
                    'stageDimensions' => [
                        [
                            'dimension' => 'US-mobile',
                            'enabled' => true,
                            'treatmentAllocations' => [
                                ['id' => 'T1', 'allocation' => 100]
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $sdk->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['success' => [$experimentData]]);

        $sdk->expects($this->once())
            ->method('getTreatment')
            ->with('exp1', Stages::BETA, 'US-mobile', 'user123')
            ->willReturn('T1');

        $segment = new Segment('US', 'CA', 'mobile');
        $treatment = $sdk->getTreatmentWithSegment('exp1', Stages::BETA, $segment, 'user123');

        $this->assertEquals('T1', $treatment);
    }

    public function testTrackMetricWithSegment()
    {
        $sdk = $this->getMockBuilder(SimpleABSDK::class)
            ->setConstructorArgs([$this->apiURL, $this->apiKey])
            ->onlyMethods(['makeApiRequest', 'trackMetric'])
            ->getMock();

        $experimentData = [
            'id' => 'exp1',
            'stages' => [
                [
                    'stage' => Stages::BETA,
                    'stageDimensions' => [
                        [
                            'dimension' => 'US-mobile',
                            'enabled' => true,
                            'treatmentAllocations' => [
                                ['id' => 'T1', 'allocation' => 100]
                            ]
                        ]
                    ]
                ]
            ],
            'treatments' => [
                ['id' => 'T1']
            ]
        ];

        $sdk->expects($this->once())
            ->method('makeApiRequest')
            ->willReturn(['success' => [$experimentData]]);

        $sdk->expects($this->once())
            ->method('trackMetric')
            ->with([
                'experimentID' => 'exp1',
                'stage' => Stages::BETA,
                'dimension' => 'US-mobile',
                'treatment' => 'T1',
                'metricName' => 'clicks',
                'metricValue' => 1,
                'aggregationType' => AggregationTypes::SUM
            ]);

        $segment = new Segment('US', 'CA', 'mobile');
        $sdk->trackMetricWithSegment([
            'experimentID' => 'exp1',
            'stage' => Stages::BETA,
            'segment' => $segment,
            'treatment' => 'T1',
            'metricName' => 'clicks',
            'metricValue' => 1,
            'aggregationType' => AggregationTypes::SUM
        ]);
    }
}