<?php

namespace PhpDisruptor\EventProcessor;

use PhpDisruptor\RunnableInterface;
use PhpDisruptor\Sequence;

interface EventProcessorInterface extends RunnableInterface
{
    /**
     * Get a reference to the {@link Sequence} being used by this {@link EventProcessor}.
     *
     * @return Sequence reference to the {@link Sequence} for this {@link EventProcessor}
     */
    public function getSequence();

    /**
     * Signal that this EventProcessor should stop when it has finished consuming at the next clean break.
     * It will call {@link SequenceBarrierInterface#alert()} to notify the thread to check status.
     *
     * @return void
     */
    public function halt();

    /**
     * @return bool
     */
    public function isRunning();
}