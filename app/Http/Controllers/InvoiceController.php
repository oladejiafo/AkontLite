<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Invoice;
use App\Models\GuestToken;
use App\Models\Customer;
use App\Models\Payment;
use Illuminate\Support\Facades\Mail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Mail\InvoiceSentMail;
use App\Mail\InvoiceMail;
use App\Mail\InvoiceReminderMail;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Cache; 
use Illuminate\Support\Facades\Log; 

class InvoiceController extends Controller
{
    public function downloadPDF(Invoice $invoice)
    {
        $pdf = Pdf::loadView('invoices.pdf', compact('invoice'));
        return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
    }
    
    public function create()
    {
        $customers = Customer::where('user_id', Auth::id())->get();

        return view('invoices.create', compact('customers'));

        // return view('invoices.create');
    }

    public function store(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('home');
        }
        try{
            $request->validate([
                'business_name' => 'required|string|max:255',
                'business_email' => 'required|email|max:255',
                'business_address' => 'nullable|string',
                'invoice_number' => 'required|string|max:255',
                'invoice_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:invoice_date',
                'customer_id' => 'nullable|exists:customers,id',
                'client_name' => 'required_without:customer_id|string|max:255',
                'client_email' => 'nullable|email|max:255',
                'client_address' => 'nullable|string',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed', $e->errors());

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(), // 👈 Shows exact failed fields
            ], 422);
        } catch (\Exception $e) {
            Log::error('Invoice creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Invoice creation failed'], 500);
        }
    
        // Add unique check for invoice number if provided
        if ($request->has('invoice_number')) {
            $exists = Invoice::where('invoice_number', $request->invoice_number)
                ->when(Auth::check(), fn($q) => $q->where('user_id', Auth::id()))
                ->exists();
                
            if ($exists) {
                return redirect()->back()
                    ->withInput()
                    ->withErrors(['invoice_number' => 'This invoice number already exists']);
            }
        }

        // Handle file upload
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        }
    
        // Calculate total amount
        $totalAmount = collect($request->items)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });
    
        $user = Auth::user();
        
        // Handle customer
        $customerData = $this->resolveCustomer($request);

        // Create invoice
        $invoice = Invoice::create([
            'user_id' => $user?->id,
            'customer_id' => $customerData['id'],
            'invoice_number' => $request->invoice_number,
            'sender_company_name' => $request->business_name,
            'sender_company_email' => $request->business_email,
            'sender_company_address' => $request->business_address,
            'sender_logo_path' => $logoPath,
            'customer_name' => $customerData['name'],
            'customer_email' => $customerData['email'],
            'customer_address' => $customerData['address'],
            'issue_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'footer_note' => $request->notes,
            'total_amount' => $totalAmount,
            'currency'  => 'USD',
            'status' => 'draft',
        ]);
    
        // Add invoice items
        foreach ($request->items as $item) {
            $invoice->items()->create([
                'invoice_id' => $invoice->id,
                'description' => $item['description'],
                'quantity' => $item['quantity'],
                'unit_price' => $item['unit_price'],
                'total' => $item['quantity'] * $item['unit_price'],
            ]);
        }
    
          // Clear any cached draft data
        if (Auth::check()) {
            Cache::forget('draft_invoice_'.Auth::id());
        }

        return redirect()->route('invoices.show', $invoice->id)
            ->with('success', 'Invoice created successfully');

        // return response()->json([
        //     'message' => 'Invoice created successfully',
        //     'invoice_id' => $invoice->id,
        //     'redirect_url' => route('invoices.show', $invoice->id),
        // ], 201);
    }
    
    protected function resolveCustomer(Request $request): array
    {
        $user = Auth::user();
        $defaults = [
            'id' => null,
            'name' => $request->client_name,
            'email' => $request->client_email,
            'address' => $request->client_address
        ];

        if (!$user) {
            return $defaults;
        }

        // If customer_id provided, use that customer
        if ($request->filled('customer_id')) {
            $customer = Customer::find($request->customer_id);
            if ($customer) {
                return [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'email' => $customer->email,
                    'address' => $customer->address
                ];
            }
        }

        // Check for existing customer with same details
        $existingCustomer = Customer::where('user_id', $user->id)
            ->where('name', $request->client_name)
            ->where('email', $request->client_email)
            ->first();

        if ($existingCustomer) {
            return [
                'id' => $existingCustomer->id,
                'name' => $existingCustomer->name,
                'email' => $existingCustomer->email,
                'address' => $existingCustomer->address
            ];
        }

        // Create new customer
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => $request->client_name,
            'email' => $request->client_email,
            'address' => $request->client_address
        ]);

        return [
            'id' => $customer->id,
            'name' => $customer->name,
            'email' => $customer->email,
            'address' => $customer->address
        ];
    }

    public function index()
    {
        if (!Auth::check()) {
            return redirect()->route('home');
        }

        $invoices = Invoice::where('user_id', Auth::id())
                    ->with('customer')
                    ->latest()
                    ->paginate(10);

        return view('invoices.index', compact('invoices'));
    }

    public function show($id)
    {
        if (!Auth::check()) {
            return redirect()->route('home');
        }
        // $invoice = Invoice::with('items', 'customer')->findOrFail($id);
        $invoice = Invoice::with(['items', 'customer' => function($query) {
            $query->select('id', 'name', 'email', 'address'); // Only get needed fields
        }])->findOrFail($id);

        if ($invoice->user_id && Auth::id() !== $invoice->user_id) {
            abort(403);
        }
        // dd($invoice);
        return view('invoices.show', compact('invoice'));
    }
    
    public function edit(Invoice $invoice)
    {
        $user = Auth::user();

        if (!$user || ($invoice->user_id !== $user->id && $invoice->guest_token_id !== session('guest_token_id'))) {
            abort(403);
        }

        $customers = Customer::where('user_id', Auth::id())->get();

        return view('invoices.edit', compact('invoice','customers'));
    }

    public function update(Request $request, Invoice $invoice)
    {
        $user = Auth::user();

        if (!$user || ($invoice->user_id !== $user->id && $invoice->guest_token_id !== session('guest_token_id'))) {
            abort(403);
        }

        if (!Auth::check()) {
            return redirect()->route('home');
        }
        try{
            $request->validate([
                'business_name' => 'required|string|max:255',
                'business_email' => 'required|email|max:255',
                'business_address' => 'nullable|string',
                'invoice_number' => 'required|string|max:255',
                'invoice_date' => 'required|date',
                'due_date' => 'required|date|after_or_equal:invoice_date',
                'customer_id' => 'nullable|exists:customers,id',
                'client_name' => 'required_without:customer_id|string|max:255',
                'client_email' => 'nullable|email|max:255',
                'client_address' => 'nullable|string',
                'notes' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.description' => 'required|string|max:255',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'required|numeric|min:0',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            ]);
        } catch (ValidationException $e) {
            Log::error('Validation failed', $e->errors());

            return response()->json([
                'error' => 'Validation failed',
                'details' => $e->errors(), // 👈 Shows exact failed fields
            ], 422);
        } catch (\Exception $e) {
            Log::error('Invoice creation failed: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Invoice creation failed'], 500);
        }
    
        // Add unique check for invoice number if provided
        if ($request->has('invoice_number')) {
            $exists = Invoice::where('invoice_number', $request->invoice_number)
              ->where('id', '!=', $invoice->id) // 👈 do NOT count the same invoice
              ->when(Auth::check(), fn($q) => $q->where('user_id', Auth::id()))
              ->exists();
          
            if ($exists) {
              return back()->withInput()->withErrors(['invoice_number' => 'This invoice number already exists']);
            }
        }          

        // Handle file upload
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('logos', 'public');
        } else {
        $logoPath = $invoice->sender_logo_path; // keep old
        }
    
        // Calculate total amount
        $totalAmount = collect($request->items)->sum(function ($item) {
            return $item['quantity'] * $item['unit_price'];
        });
    
        $user = Auth::user();
        
        // Handle customer
        $customerData = $this->resolveCustomer($request);

        // Update invoice
        $invoice->update([
            'customer_id' => $customerData['id'],
            'invoice_number' => $request->invoice_number,
            'sender_company_name' => $request->business_name,
            'sender_company_email' => $request->business_email,
            'sender_company_address' => $request->business_address,
            'sender_logo_path' => $logoPath,
            'customer_name' => $customerData['name'],
            'customer_email' => $customerData['email'],
            'customer_address' => $customerData['address'],
            'issue_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'footer_note' => $request->notes,
            'total_amount' => $totalAmount,
          ]);
          
    
        // Add invoice items
        $invoice->items()->delete();

        foreach ($request->items as $item) {
          $invoice->items()->create([
            'description' => $item['description'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total' => $item['quantity'] * $item['unit_price'],
          ]);
        }        
    
          // Clear any cached draft data
        if (Auth::check()) {
            Cache::forget('draft_invoice_'.Auth::id());
        }

        return redirect()->route('invoices.edit', $invoice)->with('success', 'Invoice updated.');
    }
    
    public function destroy($id)
    {
        $invoice = Invoice::findOrFail($id);
        $invoice->delete();
    
        // return response()->json(['message' => 'Invoice deleted']);
        return redirect()->route('invoices.index')->with('success', 'Invoice deleted.');

    }
    
    public function recoverDraft(Request $request)
    {
        return view('invoices.recover-draft');
    }

    public function preview(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'business_email' => 'required|email',
            'business_address' => 'nullable|string',
            'invoice_number' => 'required|string',
            'invoice_date' => 'required|date',
            'due_date' => 'required|date',
            'client_name' => 'required|string',
            'client_email' => 'nullable|email',
            'client_address' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.description' => 'required|string',
            'items.*.quantity' => 'required|integer',
            'items.*.unit_price' => 'required|numeric',
        ]);

        $totalAmount = collect($request->items)->sum(fn($item) => $item['quantity'] * $item['unit_price']);

        $invoice = new Invoice([
            'invoice_number' => $request->invoice_number,
            'sender_company_name' => $request->business_name,
            'sender_company_email' => $request->business_email,
            'sender_company_address' => $request->business_address,
            'customer_name' => $request->client_name,
            'customer_email' => $request->client_email,
            'customer_address' => $request->client_address,
            'issue_date' => $request->invoice_date,
            'due_date' => $request->due_date,
            'footer_note' => $request->notes,
            'total_amount' => $totalAmount,
            'currency' => 'USD',
            'status' => 'draft',
        ]);

        $invoice->items = $request->items;

        return view('invoices.preview', compact('invoice'));
    }

    public function download(Invoice $invoice)
    {
        $user = Auth::user();

        if (!$user || ($invoice->user_id !== $user->id && $invoice->guest_token_id !== session('guest_token_id'))) {
            abort(403);
        }

        $pdf = Pdf::loadView('invoices.pdf', ['invoice' => $invoice->load('items')]);
        return $pdf->download('invoice_' . $invoice->invoice_number . '.pdf');
    } 

    private function getLogoPath($invoice)
    {
        if ($invoice->company && $invoice->company->logo && 
            Storage::disk('public')->exists('logos/' . $invoice->company->logo)) {
            return asset('storage/logos/' . $invoice->company->logo);
        }
        return asset('images/logo_placeholder.png');
    }
  
    public function downloadx(Invoice $invoice)
    {
        // Check authentication and company identification
        if (!auth()->check() || !auth()->user()->company_id) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }
        $invoice->load('customer', 'company');
        // $lineItems = json_decode($invoice->line_items, true);
        $lineItems = is_string($invoice->line_items)
            ? json_decode($invoice->line_items, true)
            : $invoice->line_items;

        // $logoPath = $this->getLogoPath($invoice);
        $logoPath = $invoice->company && $invoice->company->logo && Storage::disk('public')->exists('logos/' . $invoice->company->logo)
            ? storage_path('app/public/logos/' . $invoice->company->logo)
            : public_path('images/logo_placeholder.png');
    
        $template = $invoice->company->invoice_template ?? 'classic';
        $template = is_array($template) ? $template[0] : $template;

        $pdf = Pdf::loadView("invoices.templates.$template", [
            'invoice' => $invoice,
            'lineItems' => $lineItems,
            'logoPath' => $logoPath,
            'components' => $invoice->company->invoice_components 
                        ?? config("invoices.templates.$template.components"),
            'pdf' => true // Flag for PDF-specific adjustments
        ]);

        
        Log::info('1Selected template:', [$template]);
        Log::info('1Components fallback:', config("invoices.templates.$template.components"));
        Log::info('1Company override components:', $invoice->company->invoice_components);
        
        return $pdf->download("Invoice-{$invoice->invoice_number}.pdf");
    }

    public function sendReminder($id)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);
    
        // send email using Laravel Mail
        Mail::to($invoice->customer->email)->send(new InvoiceReminderMail($invoice));
    
        return response()->json(['message' => 'Reminder sent']);
    }

    public function sendByEmail($id)
    {
        $invoice = Invoice::with('customer')->findOrFail($id);
        
        Mail::to($invoice->customer->email)->send(new InvoiceSentEmail($invoice));
    
        $invoice->status = 'sent';
        $invoice->save();
    
        return response()->json(['message' => 'Invoice emailed']);
    }

    public function email(Request $request, Invoice $invoice)
    {
        $user = Auth::user();
        if (!$user || $user->id !== $invoice->user_id) abort(403);

        Mail::to($invoice->customer_email)->send(new InvoiceEmail($invoice));

        return back()->with('success', 'Invoice sent to email.');
    }

    public function sendEmail(Request $request, Invoice $invoice)
    {
        try {
            Log::info($invoice);
            Log::info($invoice->customer);
    
            Mail::to($invoice->customer->email)->send(new InvoiceMail($invoice));
    
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice sent successfully!'
                ]);
            } else {
                return redirect()->route('invoices.index')
                    ->with('success', 'Invoice sent successfully!');
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Failed to send email: ' . $e->getMessage()
            ], 500);
        }
    }
    
    public function markPaid(Request $request, Invoice $invoice)
    {
        if (!Auth::check()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized access.'], 403);
        }
    
        DB::beginTransaction();
    
        try {
            // Update invoice status
            $invoice->update(['status' => 'paid']);
    
            // Check if payment already exists
            $existingPayment = Payment::where('invoice_id', $invoice->id)
                                    ->where('status', 'completed')
                                    ->first();
    
            if (!$existingPayment) {
                // Create payment record
                $payment = Payment::create([
                    'invoice_id' => $invoice->id,
                    'user_id' => auth()->id(),
                    'customer_id' => $invoice->customer_id,
                    'amount' => $invoice->total_amount,
                    'payment_method' => 'manual',
                    'status' => 'completed',
                    'paid_at' => now(),
                    'meta' => [
                        'notes' => 'Payment recorded when marking invoice as paid',
                        'source' => 'invoice_mark_paid'
                    ]
                ]);
    
                // Update invoice with payment reference if needed
                if (!$invoice->payment_id) {
                    $invoice->update(['payment_id' => $payment->id]);
                }
            }
    
            DB::commit();
    
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Invoice marked as paid and payment recorded!'
                ]);
            } else {
                return redirect()->route('invoices.index')
                    ->with('success', 'Invoice marked as paid and payment recorded!');
            }
    
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error marking invoice as paid: ' . $e->getMessage());
    
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Failed to mark invoice as paid'
                ], 500);
            } else {
                return redirect()->back()
                    ->with('error', 'Failed to mark invoice as paid');
            }
        }
    }
    
    public function paymentsIndex(Request $request)
    {
        // Check authentication and company identification
        if (!Auth::check()) {
            return redirect()->route('home');
        }

        $user = Auth::user();

        // $query = $request->input('search');
        $payments = Payment::query();

        // if ($query) {
        //     $payments->where('company_id', $companyId)
        //         ->where(function ($queryBuilder) use ($query) {
        //             $queryBuilder->where('id', 'like', '%' . $query . '%')
        //                 ->orWhere('payment_date', 'like', '%' . $query . '%')
        //                 ->orWhere('bank_reference_number', 'like', '%' . $query . '%')
        //                 ->orWhere('invoice_number', 'like', '%' . $query . '%')
        //                 ->orWhereHas('customer', fn($customerQuery) => $customerQuery->where('name', 'like', '%' . $query . '%'))
        //                 ->orWhereHas('bank', fn($bankQuery) => $bankQuery->where('name', 'like', '%' . $query . '%'));
        //         });
        // } else {
        //     // Continue chaining methods on the $chartOfAccounts query builder instance
        //     $payments->where('company_id', $companyId);
        // }
        $payments->where('user_id', $user->id);
        $perPage = $request->input('per_page', 25);
        $payments = $payments->paginate($perPage);

        return view('payments.index', compact('payments'));
    }

    public function paymentsCreate(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
    
        $user = Auth::user();
        $invoiceId = $request->input('invoice_id');
        
        // Get invoices that are not fully paid
        $invoices = Invoice::where('user_id', $user->id)
            ->where(function($query) {
                $query->where('status', '!=', 'paid')
                      ->orWhereNull('status');
            })
            ->get();

        $customers = Customer::where('user_id', Auth::id())->get();
        $invoice = $invoiceId ? Invoice::find($invoiceId) : null;
    
        return view('payments.create', compact('invoices', 'invoice', 'invoiceId','customers'));
    }
    
    public function paymentsStore(Request $request)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }
    
        $validatedData = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'status' => 'required|string|in:completed,pending,failed',
            'notes' => 'nullable|string',
        ]);
    
        DB::beginTransaction();
    
        try {
            $customer = Customer::findOrFail($validatedData['customer_id']);
            
            // Create or find invoice
            $invoice = $this->handleInvoiceForPayment($validatedData, $customer);
            
            // Create payment record
            $payment = Payment::create([
                'invoice_id' => $invoice->id,
                'user_id' => auth()->id(),
                'customer_id' => $customer->id,
                'amount' => $validatedData['amount'],
                'payment_method' => $validatedData['payment_method'],
                'transaction_id' => $validatedData['transaction_id'],
                'status' => $validatedData['status'],
                'paid_at' => $validatedData['status'] === 'completed' ? $validatedData['payment_date'] : null,
                'meta' => ['notes' => $validatedData['notes']],
            ]);
    
            // Update invoice status
            $this->updateInvoiceStatus($invoice);
    
            DB::commit();
    
            return redirect()->route('payments.index')
                ->with('success', 'Payment recorded successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error recording payment: ' . $e->getMessage());
        }
    }
    
    protected function handleInvoiceForPayment(array $data, Customer $customer)
    {
        // If invoice_id is provided, use that invoice
        if (!empty($data['invoice_id'])) {
            return Invoice::findOrFail($data['invoice_id']);
        }
    
        // Create a new invoice for the payment
        $invoiceNumber = Invoice::generateInvoiceNumber();
        $user = auth()->user();
    
        $invoice = Invoice::create([
            'user_id' => $user->id,
            'customer_id' => $customer->id,
            'invoice_number' => $invoiceNumber,
            'customer_name' => $customer->name,
            'customer_email' => $customer->email,
            'customer_address' => $customer->address,
            'sender_company_name' => $user->company_name ?? 'Your Company',
            'sender_company_email' => $user->email,
            'issue_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'total_amount' => $data['amount'],
            'currency' => 'USD', // Default currency
            'status' => 'draft',
        ]);
    
        // Add a single line item for the payment
        $invoice->items()->create([
            'description' => 'Payment Received',
            'quantity' => 1,
            'unit_price' => $data['amount'],
            'total' => $data['amount'],
        ]);
    
        return $invoice;
    }
    
    protected function updateInvoiceStatus(Invoice $invoice)
    {
        $totalPaid = Payment::where('invoice_id', $invoice->id)
            ->where('status', 'completed')
            ->sum('amount');
        
        $newStatus = match (true) {
            $totalPaid >= $invoice->total_amount => 'paid',
            $totalPaid > 0 => 'partial',
            default => 'unpaid'
        };
    
        $invoice->update(['status' => $newStatus]);
    }

    public function handlePaymentSuccess($payment)
    {
        $paymentId     = $payment->id;
        $paymentAmount = $payment->payable_amount;
        $paidAmount    = $payment->paid_amount;
        $customerId    = $payment->customer_id;
        $salesId       = $payment->sales_id;
        $remaining     = $paymentAmount - $paidAmount;

        // Check if invoice already exists for this payment or sale
        $existingInvoice = null;

        if ($payment->invoice_id) {
            $existingInvoice = Invoice::find($payment->invoice_id);
        } elseif ($salesId) {
            $existingInvoice = Invoice::where('project_id', $salesId)->first();
        }

        // If invoice exists, return it to avoid creating duplicate
        if ($existingInvoice) {
            // Optionally update invoice details here if needed
            return $existingInvoice;
        }

        // No invoice exists, proceed to create one

        // $sales = SaleBill::with('quote')->find($salesId);

        // if ($sales && $sales->quote) {
        //     $quote = $sales->quote;
        //     $lineItems = $this->getLineItemsFromQuote($quote, $paymentAmount);
        // } else {
            $lineItems = [
                [
                    'description'   => 'Payment Received',
                    'quantity'      => 1,
                    'unit_price'    => $paymentAmount,
                    'total_amount'  => $paymentAmount,
                    'paid_amount'   => $paidAmount,
                ]
            ];
        // }

        $invoiceNumber = Invoice::generateInvoiceNumber();

        $invoice = Invoice::create([
            'subscriber_id'   => $payment->company_id,
            'customer_id'     => $customerId,
            'payment_id'      => $paymentId,
            'project_id'      => $salesId,
            'invoice_number'  => $invoiceNumber,
            'amount'          => $paymentAmount,
            'invoice_date'    => now()->toDateString(),
            'due_date'        => now()->addDays(7)->toDateString(),
            'status'          => $remaining > 0 ? 'part paid' : 'paid',
            'line_items'      => json_encode($lineItems),
            'notes'           => null,
            'secure_token'    => Str::uuid(),
        ]);

        $payment->update([
            'invoice_id'     => $invoice->id,
            'invoice_number' => $invoice->invoice_number,
        ]);

        return $invoice;
    }

    public function generateManualInvoice($paymentId)
    {
        // Check authentication and company identification
        if (!auth()->check() || !auth()->user()->company_id) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }
        $payment = Payment::with('customer')->findOrFail($paymentId);

        // If invoice already exists
        if ($payment->invoice_id) {
            return redirect()->route('invoices.show', $payment->invoice_id)
                            ->with('message', 'Invoice already exists.');
        }

        // Otherwise, generate one manually
        $invoice = $this->handlePaymentSuccess($payment);

        return redirect()->route('invoices.show', $invoice->id)
                        ->with('message', 'Invoice generated successfully.');
    }

    public function paymentsShow($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
    
        $payment = Payment::with(['invoice', 'user'])
                    ->findOrFail($id);
    
        // Ensure the payment belongs to the authenticated user or user has permission
        if ($payment->user_id != auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
    
        return view('payments.show', compact('payment'));
    }

    public function paymentsEdit($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }
    
        $payment = Payment::with('invoice')->findOrFail($id);
        
        // Ensure the payment belongs to the authenticated user
        if ($payment->user_id != auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
    
        return view('payments.edit', compact('payment'));
    }
    
    public function paymentsUpdate(Request $request, $id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }
    
        $payment = Payment::findOrFail($id);
        
        // Ensure the payment belongs to the authenticated user
        if ($payment->user_id != auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
    
        $validatedData = $request->validate([
            'invoice_id' => 'required|exists:invoices,id',
            'customer_id' => 'required|exists:customers,id',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'required|string|max:255',
            'transaction_id' => 'nullable|string|max:255',
            'payment_date' => 'required|date',
            'status' => 'required|string|in:completed,pending,failed',
            'notes' => 'nullable|string',
        ]);
    
        DB::beginTransaction();
    
        try {
            // Update payment record
            $payment->update([
                'amount' => $validatedData['amount'],
                'payment_method' => $validatedData['payment_method'],
                'transaction_id' => $validatedData['transaction_id'],
                'status' => $validatedData['status'],
                'paid_at' => $validatedData['status'] === 'completed' ? $validatedData['payment_date'] : null,
                'meta' => ['notes' => $validatedData['notes']],
            ]);
    
            // Update invoice status if payment status changed
            if ($payment->wasChanged('status')) {
                $totalPaid = Payment::where('invoice_id', $payment->invoice_id)
                    ->where('status', 'completed')
                    ->sum('amount');
                
                $payment->invoice->update([
                    'status' => $totalPaid >= $payment->invoice->total_amount ? 'paid' : 'partial'
                ]);
            }
    
            DB::commit();
    
            return redirect()->route('payments.index')
                ->with('success', 'Payment updated successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withInput()->with('error', 'Error updating payment: ' . $e->getMessage());
        }
    }
    
    public function paymentsDestroy($id)
    {
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Unauthorized access.');
        }
    
        $payment = Payment::findOrFail($id);
        
        // Ensure the payment belongs to the authenticated user
        if ($payment->user_id != auth()->id()) {
            abort(403, 'Unauthorized action.');
        }
    
        DB::beginTransaction();
    
        try {
            $invoice = $payment->invoice;
            $payment->delete();
    
            // Recalculate invoice status
            $totalPaid = Payment::where('invoice_id', $invoice->id)
                ->where('status', 'completed')
                ->sum('amount');
            
            $invoice->update([
                'status' => $totalPaid >= $invoice->total_amount ? 'paid' : ($totalPaid > 0 ? 'partial' : 'unpaid')
            ]);
    
            DB::commit();
    
            return redirect()->route('payments.index')
                ->with('success', 'Payment deleted successfully.');
                
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Error deleting payment: ' . $e->getMessage());
        }
    }

    // public function paymentsDestroy(Payment $payment)
    // {
    //     // Check authentication and company identification
    //     if (!auth()->check() || !auth()->user()->company_id) {
    //         return redirect()->route('login')->with('error', 'Unauthorized access.');
    //     }

    //     $companyId = auth()->user()->company_id;
    //     $payment->where('company_id', $companyId)->delete();

    //     return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    // }
}
