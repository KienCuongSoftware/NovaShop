<?php

namespace App\Livewire\Admin\Product;

use Livewire\Component;

class AttributeValue extends Component
{
    public $product;

    public function mount($product = null)
    {
        $this->product = $product;
    }

    public function render()
    {
        return view('livewire.admin.product.attribute-value');
    }
}
