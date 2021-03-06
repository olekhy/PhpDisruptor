<?php

namespace PhpDisruptor;

use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\WaitStrategy\WaitStrategyInterface;
use Stackable;

final class ProcessingSequenceBarrier extends Stackable implements SequenceBarrierInterface
{
    /**
     * @var WaitStrategyInterface
     */
    public $waitStrategy;

    /**
     * @var Sequence
     */
    public $dependentSequence;

    /**
     * @var bool
     */
    public $alerted = false;

    /**
     * @var Sequence
     */
    public $cursorSequence;

    /**
     * @var SequencerInterface
     */
    public $sequencer;

    /**
     * Constructor
     *
     * @param SequencerInterface $sequencer
     * @param WaitStrategyInterface $waitStrategy
     * @param Sequence $cursorSequence
     * @param Sequence[] $dependentSequences
     */
    public function __construct(
        SequencerInterface $sequencer,
        WaitStrategyInterface $waitStrategy,
        Sequence $cursorSequence,
        StackableArray $dependentSequences
    ) {
        $this->sequencer = $sequencer;
        $this->waitStrategy = $waitStrategy;
        $this->cursorSequence = $cursorSequence;
        if (0 == count($dependentSequences)) {
            $this->dependentSequence = $cursorSequence;
        } else {
            $this->dependentSequence = new FixedSequenceGroup($dependentSequences);
        }
    }

    public function run()
    {
    }

    /**
     * Wait for the given sequence to be available for consumption.
     *
     * @param int $sequence to wait for
     * @return int the sequence up to which is available
     * @throws Exception\AlertException if a status change has occurred for the Disruptor
     * @throws Exception\InterruptedException if the thread needs awaking on a condition variable.
     * @throws Exception\TimeoutException
     */
    public function waitFor($sequence)
    {
        $this->checkAlert();

        $availableSequence = $this->waitStrategy->waitFor(
            $sequence,
            $this->cursorSequence,
            $this->dependentSequence,
            $this
        );

        if ($availableSequence < $sequence) {
            return $availableSequence;
        }
        return $this->sequencer->getHighestPublishedSequence($sequence, $availableSequence);
    }

    /**
     * Get the current cursor value that can be read.
     *
     * @return int value of the cursor for entries that have been published.
     */
    public function getCursor()
    {
        return $this->dependentSequence->get();
    }

    /**
     * The current alert status for the barrier.
     *
     * @return bool true if in alert otherwise false.
     */
    public function isAlerted()
    {
        return $this->alerted;
    }

    /**
     * Alert the EventProcessors of a status change and stay in this status until cleared.
     *
     * @return void
     */
    public function alert()
    {
        $this->alerted = true;
        $this->waitStrategy->signalAllWhenBlocking();
    }

    /**
     * Clear the current alert status.
     *
     * @return void
     */
    public function clearAlert()
    {
        $this->alerted = false;
    }

    /**
     * Check if an alert has been raised and throw an AlertException if it has.
     *
     * @return void
     * @throws Exception\AlertException if alert has been raised.
     */
    public function checkAlert()
    {
        if ($this->alerted) {
            throw new Exception\AlertException('alert');
        }
    }
}
