<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Migration\Step\Map;

use Migration\Handler;
use Migration\Logger\Logger;
use Migration\MapReader;
use Migration\Resource;

class RunTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Logger|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $logger;

    /**
     * @var Resource\Source|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $source;

    /**
     * @var Resource\Destination|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $destination;

    /**
     * @var Resource\RecordFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $recordFactory;

    /**
     * @var MapReader
     */
    protected $mapReader;

    /**
     * @var \Migration\RecordTransformerFactory|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $recordTransformerFactory;

    /**
     * @var Run|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $run;

    /**
     * @var \Migration\Config|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $config;

    public function setUp()
    {
        $this->logger = $this->getMock('Migration\Logger\Logger', [], [], '', false);
        $this->source = $this->getMock(
            'Migration\Resource\Source',
            ['getDocument', 'getDocumentList', 'getRecords'],
            [],
            '',
            false
        );
        $this->destination = $this->getMock(
            'Migration\Resource\Destination',
            ['getDocument', 'saveRecords', 'clearDocument'],
            [],
            '',
            false
        );
        $this->recordFactory = $this->getMock('Migration\Resource\RecordFactory', ['create'], [], '', false);
        $this->recordTransformerFactory = $this->getMock(
            'Migration\RecordTransformerFactory',
            ['create'],
            [],
            '',
            false
        );
        $this->config = $this->getMockBuilder('\Migration\Config')->disableOriginalConstructor()
            ->setMethods([])->getMock();
        $this->mapReader = $this->getMock('Migration\MapReader', ['getDocumentMap', 'init'], [], '', false);
        $this->mapReader->expects($this->once())->method('init');
        $this->run = new Run(
            $this->logger,
            $this->source,
            $this->destination,
            $this->recordFactory,
            $this->recordTransformerFactory,
            $this->mapReader,
            $this->config
        );
    }

    public function testGetMapEmptyDestinationDocumentName()
    {
        $sourceDocName = 'core_config_data';
        $this->source->expects($this->once())->method('getDocumentList')->will($this->returnValue([$sourceDocName]));
        $this->run->perform();
    }

    public function testGetMap()
    {
        $sourceDocName = 'core_config_data';
        $this->source->expects($this->once())->method('getDocumentList')->will($this->returnValue([$sourceDocName]));
        $dstDocName = 'config_data';
        $this->mapReader->expects($this->once())->method('getDocumentMap')->will($this->returnValue($dstDocName));
        $sourceDocument = $this->getMock('\Migration\Resource\Document', ['getRecords'], [], '', false);
        $this->source->expects($this->once())->method('getDocument')->will(
            $this->returnValue($sourceDocument)
        );
        $destinationDocument = $this->getMock('\Migration\Resource\Document', [], [], '', false);
        $this->destination->expects($this->once())->method('getDocument')->will(
            $this->returnValue($destinationDocument)
        );
        $recordTransformer = $this->getMock(
            'Migration\RecordTransformer',
            ['init', 'transform'],
            [],
            '',
            false
        );
        $this->recordTransformerFactory->expects($this->once())->method('create')->will(
            $this->returnValue($recordTransformer)
        );
        $recordTransformer->expects($this->once())->method('init');

        $bulk = [['id' => 4, 'name' => 'john']];
        $this->source->expects($this->at(2))->method('getRecords')->will($this->returnValue($bulk));
        $this->source->expects($this->at(3))->method('getRecords')->will($this->returnValue([]));
        $destinationRecords =  $this->getMock('\Migration\Resource\Record\Collection', [], [], '', false);
        $destinationDocument->expects($this->once())->method('getRecords')->will(
            $this->returnValue($destinationRecords)
        );

        $srcRecord = $this->getMock('\Migration\Resource\Record', [], [], '', false);
        $dstRecord = $this->getMock('\Migration\Resource\Record', [], [], '', false);
        $this->recordFactory->expects($this->at(0))->method('create')->will($this->returnValue($srcRecord));
        $this->recordFactory->expects($this->at(1))->method('create')->will($this->returnValue($dstRecord));
        $recordTransformer->expects($this->once())->method('transform')->with($srcRecord, $dstRecord);

        $this->destination->expects($this->once())->method('saveRecords')->with($dstDocName, $destinationRecords);
        $this->destination->expects($this->once())->method('clearDocument')->with($dstDocName);
        $this->run->perform();
    }
}
