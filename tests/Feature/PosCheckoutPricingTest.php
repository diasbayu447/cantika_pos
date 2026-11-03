<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Discount;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosCheckoutPricingTest extends TestCase
{
    use RefreshDatabase;

    private function owner(): User
    {
        return User::create([
            'name' => 'Owner',
            'username' => 'owner1',
            'password' => 'password',
            'role' => 'owner',
            'is_active' => true,
        ]);
    }

    private function physicalProduct(array $overrides = []): Product
    {
        $category = Category::create(['name' => 'Aksesoris', 'slug' => 'aksesoris']);

        return Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Tempered Glass',
            'barcode' => '101000001',
            'product_type' => 'physical',
            'cost_price' => 5000,
            'retail_price' => 10000,
            'wholesale_price' => 8000,
            'min_wholesale_qty' => 3,
            'stock' => 10,
            'min_stock_alert' => 2,
            'is_active' => true,
        ], $overrides));
    }

    private function cartItem(Product $product, int $qty): array
    {
        return [
            $product->id => [
                'id' => $product->id,
                'barcode' => $product->barcode,
                'name' => $product->name,
                'cost_price' => $product->cost_price,
                'retail_price' => $product->retail_price,
                'wholesale_price' => $product->wholesale_price,
                'min_wholesale_qty' => $product->min_wholesale_qty,
                'qty' => $qty,
                'product_type' => $product->product_type,
                'target_number' => '',
                'bill_amount' => 0,
                'admin_fee' => 3000,
            ],
        ];
    }

    public function test_checkout_records_wholesale_price_for_registered_member(): void
    {
        $user = $this->owner();
        $product = $this->physicalProduct();
        $customer = Customer::create([
            'name' => 'Member Grosir',
            'status' => 'registered',
        ]);

        $response = $this->actingAs($user)
            ->withSession(['pos_cart' => $this->cartItem($product, 3)])
            ->postJson('/pos/checkout', [
                'customer_id' => $customer->id,
                'payment_method' => 'cash',
                'grand_total' => 30000,
                'cash_received' => 24000,
            ]);

        $response->assertOk();

        $trx = Transaction::with('details')->first();
        $this->assertNotNull($trx);
        $this->assertSame(24000, $trx->total_amount);
        $this->assertTrue((bool) $trx->details->first()->is_wholesale);
        $this->assertSame(8000, $trx->details->first()->selling_price);
        $this->assertSame(24000, $trx->details->first()->subtotal);
        $this->assertSame(7, $product->fresh()->stock);
    }

    public function test_checkout_applies_auto_period_discount_to_payable_total(): void
    {
        $user = $this->owner();
        $product = $this->physicalProduct([
            'wholesale_price' => 0,
            'min_wholesale_qty' => 0,
        ]);

        Discount::create([
            'name' => 'Promo Oktober',
            'type' => 'period',
            'scope' => 'cart',
            'discount_type' => 'nominal',
            'amount' => 5000,
            'min_purchase' => 10000,
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)
            ->withSession(['pos_cart' => $this->cartItem($product, 1)])
            ->postJson('/pos/checkout', [
                'payment_method' => 'cash',
                'grand_total' => 10000,
                'cash_received' => 5000,
            ]);

        $response->assertOk();

        $trx = Transaction::first();
        $this->assertSame(5000, $trx->total_amount);
        $this->assertSame(5000, $trx->discount_amount);
        $this->assertSame('Promo Oktober', $trx->discount_note);
    }

    public function test_update_qty_rejects_amount_above_stock(): void
    {
        $user = $this->owner();
        $product = $this->physicalProduct(['stock' => 2]);

        $response = $this->actingAs($user)
            ->withSession(['pos_cart' => $this->cartItem($product, 1)])
            ->postJson('/pos/update-qty', [
                'product_id' => $product->id,
                'qty' => 5,
            ]);

        $response->assertStatus(400);
        $this->assertSame(1, session('pos_cart')[$product->id]['qty']);
    }
}
