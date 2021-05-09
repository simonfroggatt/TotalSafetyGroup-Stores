<?php
namespace GuzzleHttp\Ring\Future;

use ArrayAccess;
use Countable;
use IteratorAggregate;

/**
 * Future that provides array-like access.
 */
interface FutureArrayInterface extends
    FutureInterface,
    ArrayAccess,
    Countable,
    IteratorAggregate {}
