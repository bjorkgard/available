<?php

namespace App\Enums;

enum DeleteScope: string
{
    case ThisOnly = 'this_only';
    case AllFuture = 'all_future';
    case All = 'all';
}
