<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\SaleReturn;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaleReturnStockTest extends TestCase
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

    private function physicalProduct(int $stock = 7): Product
    {
        $category = Category::create(['name' => 'Aksesoris', 'slug' => 'aksesoris']);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Tempered Glass',
            'barcode' => '101000001',
            'product_type' => 'physical',
            'cost_price' => 5000,
            'retail_price' => 10000,
            'wholesale_price' => 0,
            'min_wholesale_qty' => 0,
            'stock' => $stock,
            'min_stock_alert' => 2,
            'is_active' => true,
        ]);
    }

    private function completedSale(User $user, Product $product, int $qty = 3): Transaction
    {
        $trx = Transaction::create([
            'invoice_number' => 'TRX-TEST-001',
            'user_id' => $user->id,
            'customer_id' => null,
            'payment_method' => 'cash',
            'total_amount' => $qty * $product->retail_price,
            'discount_amount' => 0,
            'cash_amount' => $qty * $product->retail_price,
            'non_cash_amount' => 0,
            'change_amount' => 0,
            'status' => 'success',
        ]);

        $trx->details()->create([
            'product_id' => $product->id,
            'qty' => $qty,
            'cost_price' => $product->cost_price,
            'selling_price' => $product->retail_price,
            'is_wholesale' => false,
            'discount_amount' => 0,
            'subtotal' => $qty * $product->retail_price,
        ]);

        return $trx;
    }

    public function test_refund_restores_physical_stock(): void
    {
        $user = $this->owner();
        $product = $this->physicalProduct(7);
        $trx = $this->completedSale($user, $product, 3);

        $response = $this->actingAs($user)->post(route('returns.sale.store'), [
            'transaction_id' => $trx->id,
            'action_type' => 'refund',
            'reason' => 'Barang tidak jadi dipakai',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 2,
                    'price_per_item' => 10000,
                ],
            ],
        ]);

        $response->assertRedirect(route('returns.index'));
        $this->assertSame(9, $product->fresh()->stock);
        $this->assertSame(1, SaleReturn::count());
        $this->assertSame(20000, SaleReturn::first()->total_refund_amount);
    }

    public function test_replace_still_decrements_replacement_stock(): void
    {
        $user = $this->owner();
        $product = $this->physicalProduct(7);
        $trx = $this->completedSale($user, $product, 3);

        $this->actingAs($user)->post(route('returns.sale.store'), [
            'transaction_id' => $trx->id,
            'action_type' => 'replace',
            'reason' => 'Unit retak, tukar sejenis',
            'items' => [
                [
                    'product_id' => $product->id,
                    'qty' => 1,
                    'price_per_item' => 10000,
                ],
            ],
        ])->assertRedirect(route('returns.index'));

        $this->assertSame(6, $product->fresh()->stock);
    }
}
