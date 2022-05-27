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

do {
  $option = readline('Select 1 if you would like to download all invoices. Select 2 if you would like to download all invoices STARTING AFTER a given invoice. Select 3 if you would like to download all invoices BEFORE a given invoice: ');
} while ($option !== '1' && $option !== '2' && $option !== '3' );

switch ($option) {
  case "1":
    $invoices = $stripe->invoices->all(['limit' => 100]);
    break;
  case "2":
    $invoice_index = readline('Enter an invoice ID. Only invoices after this one will be retrieved: ');
    $invoices = $stripe->invoices->all(['limit' => 100, 'starting_after' => $invoice_index]);
    break;
  case "3":
    $invoice_index = readline('Enter an invoice ID. Only invoices before this one will be retrieved: ');
    $invoices = $stripe->invoices->all(['limit' => 100, 'ending_before' => $invoice_index]);
    break;
}

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
