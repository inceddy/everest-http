<?php

declare(strict_types=1);

/*
 * This file is part of Everest.
 *
 * (c) 2017 Philipp Steingrebe <development@steingrebe.de>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Everest\Http;

/**
 * @author Philipp Steingrebe <philipp@steingrebe.de>
 */

class HttpException extends \Exception
{
    public function __construct($code)
    {
        if (! array_key_exists($code, Responses\Response::STATUS_CODE_MAP)) {
            trigger_error(sprintf('Http error code \'%s\' is unknown. Error code was set to 500.', $code), E_USER_ERROR);
            $code = 500;
        }
        parent::__construct(Responses\Response::STATUS_CODE_MAP[$code], $code);
    }
}
