<?php

namespace App\Livewire\Frontend\Conponents;

use App\Models\Unit;
use App\Models\UnitOrder;
use Jantinnerezo\LivewireAlert\LivewireAlert;
use Livewire\Component;

class UnitPopup extends Component
{
    use LivewireAlert;
    public $name, $email, $phone;
            // $message;
    public $selectedUnit;
    public $showSideSheet = false;
    public $currentStep = 1;
    public $purchaseType = 'cash'; // Default value
    public $purchasePurpose = 'living'; // Default value

    // Purchase Type Options
    public $purchaseTypes = [
        'cash' => 'كاش',
        'bank' => 'تمويل بنكي'
    ];

    // Purchase Purpose Options
    public $purchasePurposes = [
        'living' => 'سكن',
        'invest' => 'استثمار'
    ];
    // Validation Rules
    protected $rules = [
        'name' => 'required|min:3|max:50',
        'email' => 'required|email',
        'phone' => 'required|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
        // 'message' => 'required|min:10|max:500',
        'purchaseType' => 'required|in:cash,bank',
        'purchasePurpose' => 'required|in:living,invest'
    ];

    // Custom Error Messages
    protected $messages = [
        'name.required' => 'الرجاء إدخال الاسم',
        'name.min' => 'يجب أن يكون الاسم 3 أحرف على الأقل',
        'name.max' => 'يجب أن لا يتجاوز الاسم 50 حرفاً',
        'email.required' => 'الرجاء إدخال البريد الإلكتروني',
        'email.email' => 'الرجاء إدخال بريد إلكتروني صحيح',
        'phone.required' => 'الرجاء إدخال رقم الهاتف',
        'phone.regex' => 'الرجاء إدخال رقم هاتف صحيح',
        'phone.min' => 'يجب أن يكون رقم الهاتف 10 أرقام على الأقل',
        // 'message.required' => 'الرجاء إدخال الرسالة',
        // 'message.min' => 'يجب أن تكون الرسالة 10 أحرف على الأقل',
        // 'message.max' => 'يجب أن لا تتجاوز الرسالة 500 حرف',
        'purchaseType.required' => 'الرجاء اختيار طريقة الشراء',
        'purchaseType.in' => 'طريقة الشراء غير صحيحة',
        'purchasePurpose.required' => 'الرجاء اختيار الغرض من الشراء',
        'purchasePurpose.in' => 'الغرض من الشراء غير صحيح'
    ];

    public function resetForm()
    {
        $this->name = '';
        $this->email = '';
        $this->phone = '';
        // $this->message = '';
        $this->purchaseType = 'cash';
        $this->purchasePurpose = 'living';
        $this->resetErrorBag();
    }

    public function submitInterest()
    {
        $this->validate();

        try {
            // Save the interest to database
            UnitOrder::create([
                'unit_id' => $this->selectedUnit->id,
                'project_id' => $this->selectedUnit->project->id,
                'name' => $this->name,
                'email' => $this->email,
                'phone' => $this->phone,
                // 'message' => $this->message,
                'PurchaseType' => $this->purchaseType,
                'PurchasePurpose' => $this->purchasePurpose,
                'status' => 0
            ]);

            $this->alert('success', 'تم تسجيل اهتمامك بنجاح', [
                'position' => 'bottom',
                'timer' => 5000,
            ]);

            $this->currentStep = 1;
            $this->closeSideSheet();
            $this->resetForm();
        } catch (\Exception $e) {
            $this->alert('error', 'حدث خطأ أثناء تسجيل اهتمامك. الرجاء المحاولة مرة أخرى.', [
                'position' => 'bottom',
                'timer' => 5000,
            ]);
        }
    }

    protected $listeners = [
        'loadUnit' => 'loadUnitFromDispatch'
    ];

    public function loadUnitFromDispatch($data)
    {
        $this->loadUnit($data['unitId']);
    }

    public function loadUnit($unitId)
    {
        $this->selectedUnit = Unit::findOrFail($unitId);
        $this->showSideSheet = true;
    }

    public function closeSideSheet()
    {
        $this->showSideSheet = false;
        $this->selectedUnit = null;
        $this->currentStep = 1;
    }

    public function goToFormStep()
    {
        $this->currentStep = 2;
    }

    public function render()
    {
        return view('livewire.frontend.conponents.unit-popup');
    }
}
