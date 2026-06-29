<x-app-layout>
<x-header />
<meta name="csrf-token" content="{{ csrf_token() }}">

<div class="container py-5">
    <div class="d-flex justify-content-between pt-4">
        <h2>Invoice Preview</h2>
        <a href="{{ route('invoices.index') }}" class="btn btn-outline-primary"><i class="fa fa-arrow-left me-1"></i> Back</a>
    </div>
    <span class="badge my-3 bg-{{ $invoice->status === 'paid' ? 'success' : ($invoice->status === 'overdue' ? 'danger' : 'secondary') }}">
        {{ ucfirst($invoice->status) }}
    </span>
    <div class="card shadow-sm p-4" id="invoicePreviewBody"  data-invoice-id="{{ $invoice->id ?? '' }}" tabindex="-1" aria-hidden="true">
        @if($invoice->sender_logo_path)
            <div class="text-end mb-4">
                <img src="{{ asset('storage/' . $invoice->sender_logo_path) }}" alt="Logo" style="max-height: 80px;">
            </div>
        @endif 

        <div class="row mb-4">
            <div class="col-md-6">
                <h5>From:</h5>
                <p class="mb-1"><strong>{{ $invoice->sender_company_name }}</strong></p>
                <p class="mb-1">{{ $invoice->sender_company_email }}</p>
                <p>{{ $invoice->sender_company_address }}</p>
            </div>

            <div class="col-md-6 text-md-end">
                <h5>To:</h5>
                <p class="mb-1"><strong>{{ $invoice->customer->name }}</strong></p>
                @if($invoice->customer->email)
                    <p class="mb-1">{{ $invoice->customer->email }}</p>
                @endif
                <p>{{ $invoice->customer->address }}</p>
            </div>
        </div>

        <hr>

        <div class="row mb-4">
            <div class="col-md-6">
                <p><strong>Invoice Number:</strong> {{ $invoice->invoice_number }}</p>
                <p><strong>Issue Date:</strong> {{ $invoice->issue_date->format('M d, Y') }}</p>
                <p><strong>Due Date:</strong> {{ $invoice->due_date->format('M d, Y') }}</p>
            </div>
            <div class="col-md-6 text-md-end">
                <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>
                <p><strong>Currency:</strong> {{ $invoice->currency }}</p>
            </div>
        </div>

        <table class="table table-bordered mb-4">
            <thead class="table-light">
                <tr>
                    <th>Description</th>
                    <th class="text-end">Qty</th>
                    <th class="text-end">Unit Price</th>
                    <th class="text-end">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>{{ $item->description }}</td>
                        <td class="text-end">{{ $item->quantity }}</td>
                        <td class="text-end">{{ number_format($item->unit_price, 2) }}</td>
                        <td class="text-end">{{ number_format($item->total, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="text-end">
            <h4>Total: {{ number_format($invoice->total_amount, 2) }} {{ $invoice->currency }}</h4>
        </div>

        @if($invoice->footer_note)
            <div class="mt-4">
                <h5>Notes</h5>
                <p>{{ $invoice->footer_note }}</p>
            </div>
        @endif
    </div>

    <div class="mt-3 d-flex justify-content-between">
        <div>
            <button class="btn btn-secondary me-2" id="printInvoiceBtn">
                <i class="bi bi-printer-fill"></i> Print
            </button>
            <button class="btn btn-info text-white me-2" id="emailInvoiceBtn">
                <i class="bi bi-envelope-fill"></i> Email
            </button>
        </div>
        <div>
            <button class="btn btn-success me-2" id="markAsPaidBtn">
                <i class="bi bi-check-circle-fill"></i> Mark as Paid
            </button>
            <button class="btn btn-primary" id="downloadInvoiceBtn">
                <i class="bi bi-download"></i> Download
            </button>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        document.getElementById('downloadInvoiceBtn').addEventListener('click', function () {
            requireLoginThen(downloadInvoicePdf);
        });

        function downloadInvoicePdf() {
            const element = document.getElementById('invoicePreviewBody');
            if (!element) {
                alert("Missing content.");
                return;
            }

            // Get invoice number - multiple fallback options
            let invoiceNumber = 'invoice';
            
            // Option 1: Try to find in preview body
            const previewNumber = element.querySelector('.invoice-number');
            if (previewNumber) {
                invoiceNumber = previewNumber.textContent.trim();
            }
            // Option 2: Try form input (if available)
            else {
                const formInput = document.querySelector('[name="invoice_number"]');
                if (formInput) {
                    invoiceNumber = formInput.value;
                }
            }

            // Clean and format the invoice number
            invoiceNumber = invoiceNumber
                .replace(/\s+/g, '-')  // Replace spaces with dashes
                .replace(/[^a-zA-Z0-9-]/g, '') // Remove special chars
                .toLowerCase();

            // Generate timestamp
            const now = new Date();
            const timestamp = `${now.getFullYear()}${String(now.getMonth()+1).padStart(2,'0')}${String(now.getDate()).padStart(2,'0')}-${String(now.getHours()).padStart(2,'0')}${String(now.getMinutes()).padStart(2,'0')}`;

            const opt = {
                margin: 0.5,
                filename: `${invoiceNumber}-${timestamp}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { 
                    scale: 2,
                    scrollX: 0,
                    scrollY: 0
                },
                jsPDF: { 
                    unit: 'in', 
                    format: 'letter', 
                    orientation: 'portrait' 
                }
            };

            const originalDisplay = element.style.display;
            element.style.display = 'block';

            // Add loading state
            const btn = document.getElementById('downloadInvoiceBtn');
            const originalBtnText = btn.innerHTML;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> Generating...';

            html2pdf().from(element).set(opt).save().then(() => {
                element.style.display = originalDisplay;
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
            }).catch(error => {
                console.error('PDF generation failed:', error);
                element.style.display = originalDisplay;
                btn.disabled = false;
                btn.innerHTML = originalBtnText;
                alert('Failed to generate PDF. Please try again.');
            });
        }

        // Print button
        document.getElementById('printInvoiceBtn').addEventListener('click', function() {
            const element = document.getElementById('invoicePreviewBody');
            if (!element) {
                alert("Missing content.");
                return;
            }
            
            const originalDisplay = element.style.display;
            element.style.display = 'block';
            
            const printWindow = window.open('', '', 'width=800,height=600');
            printWindow.document.write(`
                <html>
                    <head>
                        <title>Print Invoice</title>
                        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
                        <style>
                            @media print {
                                body { padding: 20px; }
                                .no-print { display: none !important; }
                            }
                        </style>
                    </head>
                    <body>
                        ${element.innerHTML}
                        <script>
                            window.onload = function() {
                                setTimeout(function() {
                                    window.print();
                                    window.close();
                                }, 200);
                            };
                        <\/script>
                    </body>
                </html>
            `);
            printWindow.document.close();
            
            element.style.display = originalDisplay;
        });

        // EMAIL BUTTON - UPDATED
        document.getElementById('emailInvoiceBtn').addEventListener('click', function() {
            const invoiceId = document.getElementById('invoicePreviewBody')?.dataset.invoiceId;
            
            if (!invoiceId) return alert("Invoice ID missing");

            const btn = this;
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Sending...';

            fetch(`/invoices/${invoiceId}/email`, {
                method: 'POST',
                headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                alert('Invoice sent successfully!');
                } else {
                alert(data.message || 'Failed to send email');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Failed to send email');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = 'Email';
            });
        });


        // Mark as Paid button
        document.getElementById('markAsPaidBtn').addEventListener('click', function() {
            const container = document.getElementById('invoicePreviewBody');
            const invoiceId = container ? container.dataset.invoiceId : null;
            console.log('Invoice ID:', invoiceId);

            if (!invoiceId) {
                // alert('Invoice ID is missing!');
                return;
            }

            if (confirm('Are you sure you want to mark this invoice as paid?')) {
                fetch(`/invoices/${invoiceId}/mark-as-paid`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // alert('Invoice marked as paid successfully!');
                        location.reload();
                    } else {
                        alert('Error: ' + (data.message || 'Failed to mark as paid'));
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Failed to mark as paid. Please try again.');
                });
            }
        });


    });
</script>
<x-footer />
</x-app-layout>
