<?php

namespace App\Exceptions;

use RuntimeException;

/** Thrown by LabRunner implementations when a lab instance cannot be started or stopped. */
class LabRunnerException extends RuntimeException {}
