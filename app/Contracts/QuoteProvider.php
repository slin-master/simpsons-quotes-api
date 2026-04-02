<?php

namespace App\Contracts;

use App\Data\QuoteData;

interface QuoteProvider
{
    public function randomQuote(): QuoteData;
}
