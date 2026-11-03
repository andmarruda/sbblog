<?php

namespace App\Migration\V1;

use RuntimeException;

/**
 * Thrown at the end of a dry run to roll the whole import back.
 */
final class DryRunFinished extends RuntimeException {}
