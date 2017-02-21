<?php

namespace Charcoal\Admin\User;

use DateTime;
use DateTimeInterface;
use InvalidArgumentException;

// From 'charcoal-core'
use Charcoal\Model\AbstractModel;

// From 'charcoal-user'
use Charcoal\User\AuthToken as BaseAuthToken;

// From 'charcoal-admin'
use Charcoal\Admin\User\AuthTokenMetadata;

/**
 * Authorization token; to keep a user logged in
 */
class AuthToken extends BaseAuthToken
{
}