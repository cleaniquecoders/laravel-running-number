<?php

use CleaniqueCoders\RunningNumber\Presenters\CompactDatePresenter;
use CleaniqueCoders\RunningNumber\Presenters\DatePrefixPresenter;
use CleaniqueCoders\RunningNumber\Presenters\YearMonthPresenter;

it('formats with date prefix presenter', function () {
    $presenter = new DatePrefixPresenter('Y-m-d', '-');
    $formatted = $presenter->format('INVOICE', 1);

    $expected = 'INVOICE-'.date('Y-m-d').'-001';
    expect($formatted)->toBe($expected);
});

it('formats with compact date presenter', function () {
    $presenter = new CompactDatePresenter('Ymd', '-');
    $formatted = $presenter->format('INVOICE', 1);

    $expected = 'INVOICE-'.date('Ymd').'001';
    expect($formatted)->toBe($expected);
});

it('formats with year month presenter', function () {
    $presenter = new YearMonthPresenter('-');
    $formatted = $presenter->format('INVOICE', 1);

    $expected = 'INVOICE-'.date('Y').'-'.date('m').'-001';
    expect($formatted)->toBe($expected);
});

it('date prefix presenter supports custom date format', function () {
    $presenter = new DatePrefixPresenter('Y/m', '/');
    $formatted = $presenter->format('ORDER', 42);

    $expected = 'ORDER/'.date('Y/m').'/042';
    expect($formatted)->toBe($expected);
});

it('compact date presenter supports custom separator', function () {
    $presenter = new CompactDatePresenter('Ym', '_');
    $formatted = $presenter->format('TICKET', 5);

    $expected = 'TICKET_'.date('Ym').'005';
    expect($formatted)->toBe($expected);
});

it('year month presenter with slash separator', function () {
    $presenter = new YearMonthPresenter('/');
    $formatted = $presenter->format('RECEIPT', 123);

    $expected = 'RECEIPT/'.date('Y').'/'.date('m').'/123';
    expect($formatted)->toBe($expected);
});

it('respects padding configuration', function () {
    config(['running-number.padding' => 5]);

    $presenter = new DatePrefixPresenter;
    $formatted = $presenter->format('INV', 7);

    $expected = 'INV-'.date('Y-m-d').'-00007';
    expect($formatted)->toBe($expected);

    // Reset for other tests
    config(['running-number.padding' => 3]);
});
