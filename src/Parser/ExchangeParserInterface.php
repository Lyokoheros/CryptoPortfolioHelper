<?php

namespace App\Parser;

use App\Entity\User;

interface ExchangeParserInterface
{
    public function parseTransactionsCSVData(string $csvData, User $user): void;
}