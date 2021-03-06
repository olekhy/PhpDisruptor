<?php

namespace PhpDisruptorTest\TestAsset;

use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\RingBuffer;

class RingBufferThread extends \Thread
{
    public $ringBuffer;

    public $workers;

    public function __construct(RingBuffer $ringBuffer, StackableArray $workers)
    {
        $this->ringBuffer = $ringBuffer;
        $this->workers = $workers;
    }

    public function run()
    {
        $sequence = $this->ringBuffer->next();
        $event = $this->ringBuffer->get($sequence);
        $event->setValue($sequence);
        $this->ringBuffer->publish($sequence);
        foreach ($this->workers as $worker) {
            $worker->setSequence($sequence);
        }
        $this->notify();
    }
}
