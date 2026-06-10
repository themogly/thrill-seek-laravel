<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a customer tries to book a slot or course that has no places
 * left (or is no longer open) — usually a race with another customer.
 */
class BookingUnavailableException extends RuntimeException {}
