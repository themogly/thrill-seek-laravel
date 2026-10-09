<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when something tries to delete a record that money or other records
 * hang off. The message is the record's own plain-English deletionBlocker() —
 * the same reason the admin's disabled Delete button shows.
 */
class DeletionBlockedException extends RuntimeException {}
