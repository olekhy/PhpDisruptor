<?php

namespace PhpDisruptor\Util;

use PhpDisruptor\EventProcessor\AbstractEventProcessor;
use PhpDisruptor\Exception;
use PhpDisruptor\Pthreads\StackableArray;
use PhpDisruptor\Sequence;
use PhpDisruptor\SequenceAggregateInterface;

final class Util
{
    /**
     * Calculate the next power of 2, greater than or equal to x.<p>
     * From Hacker's Delight, Chapter 3, Harry S. Warren Jr.
     *
     * @param int $x Value to round up
     * @return int The next power of 2 from x inclusive
     * @throws Exception\InvalidArgumentException
     */
    public static function ceilingNextPowerOfTwo($x)
    {
        $size = PHP_INT_SIZE * 8;
        $binary = str_pad(decbin($x -1), $size, 0, STR_PAD_LEFT);
        $numberOfLeadingZeros = strpos($binary, '1');

        return 1 << ($size - $numberOfLeadingZeros);
    }

    /**
     * Get the minimum sequence from an array of {@link com.lmax.disruptor.Sequence}s.
     *
     * @param Sequence[] $sequences to compare with StackableArray as container instead of a php array
     * @param int|null  $minimum
     * @return int the minimum sequence found or PHP_INT_MAX if the array is empty
     * @throws Exception\InvalidArgumentException
     */
    public static function getMinimumSequence(StackableArray $sequences, $minimum = PHP_INT_MAX)
    {
        foreach ($sequences as $sequence) {
            $value = $sequence->get();
            $minimum = min($minimum, $value);
        }
        return $minimum;
    }

    /**
     * Get an array of Sequences for the passed EventProcessors
     *
     * @param AbstractEventProcessor[] $processors for which to get the sequences with StackableArray as container instead of a php array
     * @return Sequence[] the array of Sequences
     * @throws Exception\InvalidArgumentException
     */
    public static function getSequencesFor(StackableArray $processors)
    {
        $sequences = new StackableArray();
        foreach ($processors as $eventProcessor) {
            if (!$eventProcessor instanceof AbstractEventProcessor) {
                throw new Exception\InvalidArgumentException(
                    '$processor must be an instance of PhpDisruptor\AbstractEventProcessor'
                );
            }
            $sequences[] = $eventProcessor->getSequence();
        }
        return $sequences;
    }

    /**
     * @param SequenceAggregateInterface $sequenceAggregate
     * @param Sequence[] $oldSequences
     * @param Sequence[] $newSequences
     * @return bool
     */
    public static function casSequences(
        SequenceAggregateInterface $sequenceAggregate,
        StackableArray $oldSequences,
        StackableArray $newSequences
    ) {
        $set = false;
        $sequenceAggregate->lock();
        if ($sequenceAggregate->getSequences() == $oldSequences) {
            $sequenceAggregate->setSequences($newSequences);
            $set = true;
        }
        $sequenceAggregate->unlock();
        return $set;
    }

    /**
     * Calculate the log base 2 of the supplied integer, essentially reports the location
     * of the highest bit.
     *
     * @param int $i Value to calculate log2 for.
     * @return int The log2 value
     * @throws Exception\InvalidArgumentException
     */
    public static function log2($i)
    {
        $r = 0;
        while (($i >>= 1) != 0) {
            ++$r;
        }
        return $r;
    }
}
