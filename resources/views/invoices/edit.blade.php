<x-app-layout>
    <x-header />
    <div class="container py-5 w-75 mx-auto">
        <!-- Page Title -->
        <h1 class="mb-4 text-center">Modify Invoice</h1>
        <p class="mb-6 text-muted text-center">Modify your existing invoice.</p>

        <form action="{{ route('invoices.update', $invoice->id) }}" method="POST" enctype="multipart/form-data" id="invoiceForm">
            @method('PUT')
            @csrf

            <!-- Business Information Card -->
            <div class="card mb-5 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Business Information</h3>

                    <div class="mb-3">
                        <label for="logo" class="form-label fw-semibold">Logo (Optional)</label>

                        @if($invoice->sender_logo_path)
                            <div class="mb-3 border rounded p-2 d-inline-block text-center">
                            <img src="{{ asset('storage/'.$invoice->sender_logo_path) }}" alt="Current Logo" class="img-thumbnail" style="max-height: 80px; display: block; margin: 0 auto;">
                            <div class="small text-muted mt-1">Current Logo</div>
                            </div>
                        @endif

                        <input type="file" class="form-control mt-2" id="logo" name="logo" accept="image/*">
                    </div>

                    <div class="mb-3">
                        <label for="business_name" class="form-label">Business Name *</label>
                        <input type="text" class="form-control" id="business_name" name="business_name" required placeholder="Your Business Name" value="{{ old('business_name', $invoice->sender_company_name ?? '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="business_email" class="form-label">Business Email *</label>
                        <input type="email" class="form-control" id="business_email" name="business_email" required placeholder="business@example.com" value="{{ old('business_email', $invoice->sender_company_email ?? '') }}">
                    </div>

                    <div class="mb-3">
                        <label for="business_address" class="form-label">Business Address</label>
                        <textarea class="form-control" id="business_address" name="business_address" rows="2" placeholder="Street Address, City, State, ZIP">{{ old('business_address', $invoice->sender_company_address ?? '') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Invoice Details Card -->
            <div class="card mb-5 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Invoice Details</h3>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="invoice_number" class="form-label">Invoice Number</label>
                            <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="{{ old('invoice_number', $invoice->invoice_number) }}" readonly>
                        </div>
                        <div class="col-md-3">
                            <label for="issue_date" class="form-label">Date</label>
                            <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="{{ old('invoice_date', $invoice->issue_date->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3">
                            <label for="due_date" class="form-label">Due Date</label>
                            <input type="date" class="form-control" id="due_date" name="due_date" value="{{ old('due_date', $invoice->due_date->format('Y-m-d')) }}">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Client Information Card -->
            <div class="card mb-5 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Client Information</h3>

                    <div class="mb-3">
                        <label for="customer_id" class="form-label">Select Existing Client</label>
                        <select class="form-select" id="customer_id" name="customer_id">
                            <option value="">-- Add New Client --</option>
                            
                            @foreach ($customers as $customer)
                                <option value="{{ $customer->id }}"
                                data-name="{{ $customer->name }}"
                                data-email="{{ $customer->email }}"
                                data-address="{{ $customer->address }}"
                                @if(old('customer_id', $invoice->customer_id) == $customer->id) selected @endif
                                >
                                {{ $customer->name }} ({{ $customer->email }})
                                </option>
                            @endforeach
                        </select>

                    </div>

                    <div class="mb-3">
                        <label for="client_name" class="form-label">Client Name *</label>
                        <input type="text" class="form-control" id="client_name" name="client_name"
                            value="{{ old('client_name', $invoice->customer_name) }}" placeholder="Client Name">
                    </div>

                    <div class="mb-3">
                        <label for="client_email" class="form-label">Client Email</label>
                        <input type="email" class="form-control" id="client_email" name="client_email"
                            value="{{ old('client_email', $invoice->customer_email) }}" placeholder="client@example.com">
                    </div>

                    <div class="mb-3">
                        <label for="client_address" class="form-label">Client Address</label>
                        <textarea class="form-control" id="client_address" name="client_address"
                            placeholder="Client Address">{{ old('client_address', $invoice->customer_address) }}</textarea>
                    </div>

                </div>
            </div>

            <!-- Invoice Items Card -->
            <div class="card mb-5 shadow-sm">
                <div class="card-body">
                    <h3 class="card-title mb-4">Invoice Items</h3>
                    <table class="table table-bordered" id="invoiceItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Description</th>
                                <th style="width: 100px;">Quantity</th>
                                <th style="width: 120px;">Rate ($)</th>
                                <th style="width: 120px;">Amount ($)</th>
                                <th style="width: 50px;"></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($invoice->items as $index => $item)
                                <tr class="invoice-item-row">
                                    <td><input type="text" name="items[{{ $index }}][description]" 
                                        value="{{ old("items.$index.description", $item->description) }}"
                                        class="form-control" placeholder="Item description"></td>

                                    <td><input type="number" name="items[{{ $index }}][quantity]" 
                                        value="{{ old("items.$index.quantity", $item->quantity) }}"
                                        class="form-control quantity" min="1"></td>

                                    <td><input type="number" name="items[{{ $index }}][unit_price]" 
                                        value="{{ old("items.$index.unit_price", $item->unit_price) }}"
                                        class="form-control rate" min="0" step="0.01"></td>

                                    <td class="amount">{{ number_format($item->quantity * $item->unit_price, 2) }}</td>

                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" title="Remove">
                                        <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <!-- fallback row if no items -->
                                <tr class="invoice-item-row">
                                    <td><input type="text" name="items[0][description]" class="form-control" placeholder="Item description"></td>
                                    <td><input type="number" name="items[0][quantity]" class="form-control quantity" min="1" value="1"></td>
                                    <td><input type="number" name="items[0][unit_price]" class="form-control rate" min="0" step="0.01" value="0"></td>
                                    <td class="amount">0.00</td>
                                    <td>
                                        <button type="button" class="btn btn-outline-danger btn-sm remove-item-btn" title="Remove">
                                        <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>

                        <tfoot>
                            <tr>
                                <th colspan="3" class="text-end">Total:</th>
                                <th id="totalAmount">0.00</th>
                                <th></th>
                            </tr>
                        </tfoot>
                    </table>

                    <button type="button" id="addItemBtn" class="btn btn-outline-primary gap-1"><i class="fa fa-plus"></i> Add Item</button>
                </div>
            </div>

            <!-- Additional Notes Card -->
            <div class="card mb-5 shadow-sm">
                <div class="card-body">
                    <label for="notes" class="form-label">Additional Notes</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Payment terms, additional information, etc.">{{ old('notes', $invoice->footer_note) }}</textarea>
                </div>
            </div>

            <!-- Submit + Preview Buttons -->
            <div class="d-flex justify-content-center gap-3">
                <button type="submit" class="btn btn-primary btn-lg px-5">Modify Invoice</button>
                <a href="#" class="btn btn-secondary btn-lg px-5" id="previewBtn">Preview Invoice</a>
                <a href="{{ route('invoices.index') }}" class="btn btn-outline-primary btn-lg px-5">Cancel</a>
            </div>
        </form>
    </div>

    <div id="pdfWrapper" style="display: none;"></div>

    <!-- Invoice Preview Modal -->
    <div class="modal fade" id="invoicePreviewModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Invoice Preview</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="invoicePreviewBody">
                <!-- JavaScript will insert content here -->
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button class="btn btn-primary" id="downloadInvoiceBtn">Download</button>
            </div>
            </div>
        </div>
    </div>

    {{-- Dynamic Invoice Items Script --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <script>
        const customerSelect = document.getElementById('customer_id');

        customerSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];

            const name = selectedOption.getAttribute('data-name') || '';
            const email = selectedOption.getAttribute('data-email') || '';
            const address = selectedOption.getAttribute('data-address') || '';

            document.getElementById('client_name').value = name;
            document.getElementById('client_email').value = email;
            document.getElementById('client_address').value = address;
        });

        // ✅ Auto-fill on page load if an option is already selected
        if (customerSelect.value) {
            const selectedOption = customerSelect.options[customerSelect.selectedIndex];
            document.getElementById('client_name').value = selectedOption.getAttribute('data-name') || '';
            document.getElementById('client_email').value = selectedOption.getAttribute('data-email') || '';
            document.getElementById('client_address').value = selectedOption.getAttribute('data-address') || '';
        }
    </script>


    <script>
        // Post-login action handler
        const postLoginAction = sessionStorage.getItem('postLoginAction');
        if (postLoginAction && document.body.dataset.auth === 'true') {
            try {
                const action = JSON.parse(postLoginAction);
                
                if (action.action === 'downloadInvoice') {
                    // Reopen the modal if it was closed
                    const modalEl = document.getElementById(action.modalId);
                    if (modalEl) {
                        const modal = bootstrap.Modal.getInstance(modalEl) || 
                                    new bootstrap.Modal(modalEl);
                        modal.show();
                        
                        // Small delay to ensure modal is fully visible
                        setTimeout(() => {
                            downloadInvoicePdf();
                            sessionStorage.removeItem('postLoginAction');
                        }, 300);
                    }
                }
            } catch (e) {
                console.error('Error processing post-login action:', e);
                sessionStorage.removeItem('postLoginAction');
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            const addItemBtn = document.getElementById('addItemBtn');
            const invoiceItemsTable = document.getElementById('invoiceItemsTable').getElementsByTagName('tbody')[0];
            const totalAmountEl = document.getElementById('totalAmount');

            document.getElementById('downloadInvoiceBtn').addEventListener('click', function () {
                requireLoginThen(downloadInvoicePdf);
            });

            function downloadInvoicePdf() {
                const element = document.getElementById('invoicePreviewBody');
                if (!element) {
                    alert("Missing content.");
                    return;
                }

                const opt = {
                    margin: 0.5,
                    filename: 'invoice-preview.pdf',
                    image: { type: 'jpeg', quality: 0.98 },
                    html2canvas: { scale: 2, scrollX: 0, scrollY: 0 },
                    jsPDF: { unit: 'in', format: 'letter', orientation: 'portrait' }
                };

                const originalDisplay = element.style.display;
                element.style.display = 'block';

                html2pdf().from(element).set(opt).save().then(() => {
                    element.style.display = originalDisplay;
                });
            }

            function requireLoginThen(callback) {
                const isAuthenticated = document.body.dataset.auth === 'true';
                
                if (!isAuthenticated) {
                    // Store the action and any needed data
                    sessionStorage.setItem('postLoginAction', JSON.stringify({
                        action: 'downloadInvoice',
                        modalId: 'invoicePreviewModal' // Track which modal needs to be open
                    }));
                    
                    // Redirect to Google auth with current URL as return path
                    window.location.href = '/auth/google?redirect=' + 
                        encodeURIComponent(window.location.pathname);
                } else {
                    callback();
                }
            }

            function updateAmounts() {
                let total = 0;
                document.querySelectorAll('.invoice-item-row').forEach((row) => {
                    const qty = parseFloat(row.querySelector('.quantity').value) || 0;
                    const rate = parseFloat(row.querySelector('.rate').value) || 0;
                    const amount = qty * rate;
                    row.querySelector('.amount').textContent = '$' + amount.toFixed(2);
                    total += amount;
                });
                totalAmountEl.textContent = '$' + total.toFixed(2);
            }

            function renumberItems() {
                document.querySelectorAll('.invoice-item-row').forEach((row, index) => {
                    row.querySelectorAll('input').forEach(input => {
                        const name = input.name.replace(/\[\d+\]/, '[' + index + ']');
                        input.name = name;
                    });
                });
            }

            addItemBtn.addEventListener('click', () => {
                const newRow = invoiceItemsTable.querySelector('tr').cloneNode(true);
                newRow.querySelectorAll('input').forEach(input => {
                    input.value = input.classList.contains('quantity') ? 1 : input.classList.contains('rate') ? 0 : '';
                });
                invoiceItemsTable.appendChild(newRow);
                renumberItems();
                updateAmounts();
            });

            invoiceItemsTable.addEventListener('input', e => {
                if (e.target.classList.contains('quantity') || e.target.classList.contains('rate')) {
                    updateAmounts();
                }
            });

            invoiceItemsTable.addEventListener('click', e => {
                if (
                    e.target.classList.contains('remove-item-btn') || 
                    e.target.closest('.remove-item-btn')
                ) {
                    const btn = e.target.closest('.remove-item-btn');
                    if (invoiceItemsTable.querySelectorAll('tr').length > 1) {
                        btn.closest('tr').remove();
                        renumberItems();
                        updateAmounts();
                    }
                }
            });

            updateAmounts();

            function saveInvoiceToStorage() {
                const form = document.getElementById('invoiceForm');
                const formData = new FormData(form);
                let invoice = {};

                formData.forEach((value, key) => {
                    invoice[key] = value;
                });

                // Also store invoice items
                invoice.items = [];
                document.querySelectorAll('.invoice-item-row').forEach(row => {
                    const description = row.querySelector('[name*="[description]"]').value;
                    const quantity = row.querySelector('[name*="[quantity]"]').value;
                    const unit_price = row.querySelector('[name*="[unit_price]"]').value;
                    invoice.items.push({ description, quantity, unit_price });
                });

                localStorage.setItem('draft_invoice', JSON.stringify(invoice));
            }

            // Optional: preview button functionality
            document.getElementById('previewBtn').addEventListener('click', function (e) {
                e.preventDefault();

                const form = document.getElementById('invoiceForm');
                const formData = new FormData(form);

                // Extract basic fields
                const businessName = formData.get('business_name') || '';
                const businessEmail = formData.get('business_email') || '';
                const businessAddress = formData.get('business_address') || '';
                const invoiceNumber = formData.get('invoice_number') || '';
                const invoiceDate = formData.get('invoice_date') || '';
                const dueDate = formData.get('due_date') || '';
                const clientName = formData.get('client_name') || '';
                const clientEmail = formData.get('client_email') || '';
                const clientAddress = formData.get('client_address') || '';
                const notes = formData.get('notes') || '';

                const items = [];
                const logoInput = document.getElementById('logo');
                let logoBase64 = '';

                if (logoInput && logoInput.files && logoInput.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        logoBase64 = e.target.result;
                        generatePreview(); // Wait for logo to be ready
                    };
                    reader.readAsDataURL(logoInput.files[0]);
                } else {
                    generatePreview(); // No logo to load
                }

                function generatePreview() {
                    document.querySelectorAll('#invoiceItemsTable tbody tr').forEach(row => {
                        const descInput = row.querySelector('input[name*="[description]"]');
                        const qtyInput = row.querySelector('input.quantity');
                        const rateInput = row.querySelector('input.rate');

                        if (!descInput || !qtyInput || !rateInput) return;

                        const description = descInput.value;
                        const quantity = parseFloat(qtyInput.value || 0);
                        const rate = parseFloat(rateInput.value || 0);
                        const amount = quantity * rate;

                        items.push({ description, quantity, rate, amount });
                    });

                    const total = items.reduce((sum, item) => sum + item.amount, 0);

                    const html = `
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; gap: 1rem;">
                            <div style="display: flex; align-items: center; gap: 1rem; max-width: 60%;">
                                ${logoBase64 ? `<img src="${logoBase64}" alt="Logo" style="height: 60px; object-fit: contain;">` : ''}
                                <div style="overflow-wrap: break-word;">
                                    <h4 style="margin: 0;">${businessName}</h4>
                                    <p style="margin: 0; font-size: 0.9rem; line-height: 1.2;">
                                        <strong>Email:</strong> ${businessEmail}<br>
                                        <strong>Address:</strong> ${businessAddress}
                                    </p>
                                </div>
                            </div>

                            <div style="text-align: right; font-size: 0.9rem; flex-shrink: 0; min-width: 150px;">
                                <p style="margin: 0;"><strong>Invoice #:</strong> ${invoiceNumber}</p>
                                <p style="margin: 0;"><strong>Date:</strong> ${invoiceDate}</p>
                                <p style="margin: 0;"><strong>Due:</strong> ${dueDate}</p>
                            </div>
                        </div>

                        <hr>

                        <h5>Client</h5>
                        <p>
                            <strong>${clientName}</strong><br>
                            ${clientEmail}<br>
                            ${clientAddress}
                        </p>

                        <h5>Items</h5>
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr><th>Description</th><th>Qty</th><th>Rate</th><th>Amount</th></tr>
                            </thead>
                            <tbody>
                                ${items.map(i => `
                                    <tr>
                                        <td>${i.description}</td>
                                        <td>${i.quantity}</td>
                                        <td>$${i.rate.toFixed(2)}</td>
                                        <td>$${i.amount.toFixed(2)}</td>
                                    </tr>
                                `).join('')}
                            </tbody>
                            <tfoot>
                                <tr><th colspan="3" class="text-end">Total</th><th>$${total.toFixed(2)}</th></tr>
                            </tfoot>
                        </table>

                        ${notes ? `<h5>Notes</h5><p>${notes}</p>` : ''}
                    `;

                    document.getElementById('invoicePreviewBody').innerHTML = html;
                    new bootstrap.Modal(document.getElementById('invoicePreviewModal')).show();
                }
            });

            document.getElementById('downloadPdfBtn').addEventListener('click', async function () {
                const isAuthenticated = @json(auth()->check());

                if (!isAuthenticated) {
                    saveInvoiceToStorage();
                    window.location.href = '/login?redirect=save_invoice';
                    return;
                }

                const { jsPDF } = window.jspdf;
                const preview = document.getElementById('invoicePreviewBody');
                const doc = new jsPDF('p', 'pt', 'a4');
                await doc.html(preview, {
                    callback: function (doc) {
                        doc.save('invoice-preview.pdf');
                    },
                    x: 30,
                    y: 30,
                    html2canvas: { scale: 0.6 },
                });
            });

        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <x-footer />
</x-app-layout>
