<?php

declare(strict_types=1);

namespace App\Auth;

/** What a HomeCare sign-up does about the client of the house it is for. */
enum HouseClientOutcome
{
    /** Nobody holds the house yet: make a client for it. */
    case Create;

    /** The house already has a client with no user (the run-sheet import makes one per house): join it. */
    case Link;

    /** The house cannot be given to this account: it needs staff. */
    case Taken;
}
