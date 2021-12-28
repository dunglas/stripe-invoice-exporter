#!/usr/bin/env php
<?php

use Stripe\Invoice;
use Stripe\StripeClient;

require __DIR__ . '/vendor/autoload.php';

if (!isset($_SERVER['STRIPE_KEY'])) {
    echo 'Set a Stripe API Key in the "STRIPE_KEY" environment variable' . PHP_EOL;
    exit(1);
}

$stripe = new StripeClient($_SERVER['STRIPE_KEY']);

// TODO: use "starting_after" or "end_before" to not re-fetch existing invoices
$invoices = $stripe->invoices->all(['limit' => 100]);
foreach ($invoices->autoPagingIterator() as $invoice) {
    /** @var Invoice $invoice */
    if (!$invoice->invoice_pdf) {
        continue;
    }

    $path = sprintf('invoices/%s_%s.pdf', date(DATE_ATOM, $invoice->created), $invoice->id);
    if (file_exists($path)) {
        continue;
    }

    echo sprintf("Downloading %s..." . PHP_EOL, $invoice->invoice_pdf);
    $fp = fopen($path, 'w');

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $invoice->invoice_pdf);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_FILE, $fp);

    curl_exec($ch);
    fclose($fp);
}

echo "Done!" . PHP_EOL;
