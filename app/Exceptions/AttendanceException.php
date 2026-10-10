<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an attendance action breaks a business rule
 * (duplicate check-in, check-out without check-in, etc.).
 */
class AttendanceException extends RuntimeException
{
}
