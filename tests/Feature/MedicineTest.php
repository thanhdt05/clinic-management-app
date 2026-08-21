<?php

use App\Models\Medicine;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RolePermissionSeeder;
use Database\Seeders\RoleSeeder;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Laravel\assertSoftDeleted;
use function Pest\Laravel\deleteJson;
use function Pest\Laravel\getJson;
use function Pest\Laravel\patchJson;
use function Pest\Laravel\postJson;
use function Pest\Laravel\putJson;
use function Pest\Laravel\seed;

beforeEach(function () {
    seed([
        RoleSeeder::class,
        PermissionSeeder::class,
        RolePermissionSeeder::class,
    ]);
});

it('pharmacist can list medicines with stock status filter', function () {
    actingAsRole('PHARMACIST');

    Medicine::factory()->create(['name' => 'Paracetamol', 'stock' => 50]);
    Medicine::factory()->create(['name' => 'Amoxicillin', 'stock' => 0]);

    // All medicines
    $response = getJson('/api/medicines');
    $response->assertOk()
        ->assertJsonCount(2, 'data');

    // Filter in_stock
    getJson('/api/medicines?stock_status=in_stock')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Paracetamol');

    // Filter out_of_stock
    getJson('/api/medicines?stock_status=out_of_stock')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Amoxicillin');
});

it('pharmacist can create a new medicine with validation', function () {
    actingAsRole('PHARMACIST');

    $payload = [
        'code' => 'MED-001',
        'name' => 'Ibuprofen 400mg',
        'unit' => 'box',
        'price' => 45000,
        'stock' => 100,
        'description' => 'Anti-inflammatory pain reliever',
        'is_active' => true,
    ];

    $response = postJson('/api/medicines', $payload);

    $response->assertCreated()
        ->assertJsonPath('data.code', 'MED-001')
        ->assertJsonPath('data.name', 'Ibuprofen 400mg')
        ->assertJsonPath('data.stock', 100);

    assertDatabaseHas('medicines', [
        'code' => 'MED-001',
        'name' => 'Ibuprofen 400mg',
        'unit' => 'box',
        'stock' => 100,
    ]);
});

it('rejects duplicate medicine code and invalid price or stock on creation', function () {
    actingAsRole('PHARMACIST');

    Medicine::factory()->create(['code' => 'MED-DUP-01']);

    postJson('/api/medicines', [
        'code' => 'MED-DUP-01',
        'name' => 'Aspirin 100mg',
        'unit' => 'strip',
        'price' => -5000,
        'stock' => -10,
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['code', 'price', 'stock']);
});

it('pharmacist can view medicine detail', function () {
    actingAsRole('PHARMACIST');

    $medicine = Medicine::factory()->create([
        'name' => 'Panadol Extra',
        'price' => 30000,
    ]);

    getJson("/api/medicines/{$medicine->id}")
        ->assertOk()
        ->assertJsonPath('data.id', $medicine->id)
        ->assertJsonPath('data.name', 'Panadol Extra');
});

it('pharmacist can update medicine info', function () {
    actingAsRole('PHARMACIST');

    $medicine = Medicine::factory()->create([
        'name' => 'Old Name',
        'price' => 20000,
    ]);

    $payload = [
        'name' => 'Updated Name',
        'unit' => 'bottle',
        'price' => 25000,
        'stock' => 80,
        'is_active' => true,
    ];

    putJson("/api/medicines/{$medicine->id}", $payload)
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.price', '25000.00');

    expect($medicine->fresh()->name)->toBe('Updated Name');
});

it('pharmacist can soft delete a medicine', function () {
    actingAsRole('PHARMACIST');

    $medicine = Medicine::factory()->create(['name' => 'To Delete']);

    deleteJson("/api/medicines/{$medicine->id}")
        ->assertOk();

    assertSoftDeleted('medicines', ['id' => $medicine->id]);
});

it('pharmacist can adjust medicine stock positively and negatively', function () {
    actingAsRole('PHARMACIST');

    $medicine = Medicine::factory()->create(['stock' => 50]);

    // Increase stock
    patchJson("/api/medicines/{$medicine->id}/stock", ['quantity' => 20])
        ->assertOk()
        ->assertJsonPath('data.stock', 70);

    expect($medicine->fresh()->stock)->toBe(70);

    // Decrease stock
    patchJson("/api/medicines/{$medicine->id}/stock", ['quantity' => -30])
        ->assertOk()
        ->assertJsonPath('data.stock', 40);

    expect($medicine->fresh()->stock)->toBe(40);
});

it('rejects stock adjustment when resulting stock becomes negative', function () {
    actingAsRole('PHARMACIST');

    $medicine = Medicine::factory()->create(['stock' => 10]);

    patchJson("/api/medicines/{$medicine->id}/stock", ['quantity' => -15])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('quantity');

    expect($medicine->fresh()->stock)->toBe(10);
});

it('unauthorized user cannot manage medicines', function () {
    actingAsRole('RECEPTIONIST');

    getJson('/api/medicines')->assertForbidden();
    postJson('/api/medicines', [])->assertForbidden();
});
