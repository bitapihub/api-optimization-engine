<?php

/**
 * This file is part of the Monolog package.
 *
 * (c) Jordi Boggiano <j.boggiano@seld.be>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 * 
 * Modified to place every entry on a new line
 */

namespace Monolog\Formatter;

/**
 * Encodes whatever record data is passed to it as json
 *
 * This can be useful to log to databases or remote APIs
 *
 * @author Jordi Boggiano <j.boggiano@seld.be>
 */
class CustomJsonFormatter implements FormatterInterface
{
    /**
     * {@inheritdoc}
     */
    public function format(array $record)
    {
        return json_encode($record).PHP_EOL;
    }

    /**
     * {@inheritdoc}
     */
    public function formatBatch(array $records)
    {
        return json_encode($records).PHP_EOL;
    }
}
