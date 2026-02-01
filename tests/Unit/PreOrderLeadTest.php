<?php

use App\Models\PreOrderLead;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreOrderLeadTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_pre_order_lead()
    {
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'product_interest' => 'iPhone 15',
            'message' => 'I am interested in pre-ordering this product.',
            'status' => 'pending',
        ];

        $lead = PreOrderLead::create($data);

        $this->assertInstanceOf(PreOrderLead::class, $lead);
        $this->assertEquals('John Doe', $lead->name);
        $this->assertEquals('john@example.com', $lead->email);
        $this->assertEquals('pending', $lead->status);
    }

    /** @test */
    public function it_has_correct_fillable_attributes()
    {
        $lead = new PreOrderLead();

        $expectedFillable = [
            'name',
            'email',
            'phone',
            'product_interest',
            'message',
            'image_path',
            'status',
        ];

        $this->assertEquals($expectedFillable, $lead->getFillable());
    }

    /** @test */
    public function it_has_correct_enum_values_for_status()
    {
        $lead = PreOrderLead::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'product_interest' => 'Test Product',
            'status' => 'pending',
        ]);

        $this->assertEquals('pending', $lead->status);

        $lead->update(['status' => 'contacted']);
        $this->assertEquals('contacted', $lead->status);
    }
}