<?php

use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PayPalService;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\getJson;
use function Pest\Laravel\mock;
use function Pest\Laravel\postJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('cashier can list payments for an invoice', function () {
    actingAsRole('CASHIER');

    $invoice = Invoice::factory()->create();
    Payment::factory()->count(3)->create(['invoice_id' => $invoice->id]);

    $response = getJson("/api/invoices/{$invoice->id}/payments");

    $response->assertOk()
        ->assertJsonCount(3, 'data');
});

it('cashier can create paypal payment for unpaid invoice', function () {
    actingAsRole('CASHIER');

    $mockPayPal = mock(PayPalService::class);
    $mockPayPal->shouldReceive('createOrder')
        ->once()
        ->andReturn([
            'order_id' => 'ORDER-TEST-123',
            'approval_url' => 'https://sandbox.paypal.com/checkout?token=ORDER-TEST-123',
        ]);

    $invoice = Invoice::factory()->create([
        'subtotal' => 500000,
        'discount' => 50000,
        'total' => 450000,
        'status' => 'unpaid',
    ]);

    $payload = [
        'amount' => 450000,
        'method' => 'paypal',
        'note' => 'Payment for consultation and medicines',
    ];

    $response = postJson("/api/invoices/{$invoice->id}/payments", $payload);

    $response->assertCreated()
        ->assertJsonPath('data.order_id', 'ORDER-TEST-123')
        ->assertJsonPath('data.approval_url', 'https://sandbox.paypal.com/checkout?token=ORDER-TEST-123')
        ->assertJsonPath('data.payment.amount', '450000.00')
        ->assertJsonPath('data.payment.status', 'pending');

    assertDatabaseHas('payments', [
        'invoice_id' => $invoice->id,
        'provider_order_id' => 'ORDER-TEST-123',
        'status' => 'pending',
    ]);
});

it('rejects payment creation if amount exceeds invoice remaining amount or invoice is already paid', function () {
    actingAsRole('CASHIER');

    $invoice = Invoice::factory()->create([
        'total' => 200000,
        'status' => 'unpaid',
    ]);

    // Amount exceeds total
    postJson("/api/invoices/{$invoice->id}/payments", [
        'amount' => 300000,
        'method' => 'paypal',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('amount');

    // Invoice already paid
    $paidInvoice = Invoice::factory()->create(['status' => 'paid']);

    postJson("/api/invoices/{$paidInvoice->id}/payments", [
        'amount' => 50000,
        'method' => 'paypal',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('invoice');
});

it('cashier can capture payment and invoice status updates to paid when fully paid', function () {
    actingAsRole('CASHIER');

    $mockPayPal = mock(PayPalService::class);
    $mockPayPal->shouldReceive('captureOrder')
        ->with('ORDER-TEST-123')
        ->once()
        ->andReturn([
            'capture_id' => 'CAPTURE-999',
            'status' => 'COMPLETED',
        ]);

    $invoice = Invoice::factory()->create([
        'total' => 250000,
        'status' => 'unpaid',
    ]);

    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'amount' => 250000,
        'provider_order_id' => 'ORDER-TEST-123',
        'status' => 'pending',
    ]);

    $response = postJson("/api/payments/{$payment->id}/capture");

    $response->assertOk()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.provider_capture_id', 'CAPTURE-999');

    expect($payment->fresh()->status)->toBe('completed');
    expect($invoice->fresh()->status)->toBe('paid');
});

it('handles paypal capture failure gracefully by setting payment status to failed', function () {
    actingAsRole('CASHIER');

    $mockPayPal = mock(PayPalService::class);
    $mockPayPal->shouldReceive('captureOrder')
        ->once()
        ->andThrow(new RuntimeException('PayPal capture declined'));

    $invoice = Invoice::factory()->create(['status' => 'unpaid']);
    $payment = Payment::factory()->create([
        'invoice_id' => $invoice->id,
        'provider_order_id' => 'ORDER-FAIL-456',
        'status' => 'pending',
    ]);

    postJson("/api/payments/{$payment->id}/capture")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('payment');

    expect($payment->fresh()->status)->toBe('failed');
});
