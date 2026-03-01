<div class="dashboard-page page-background">
    <div class="row container py-4">

        <div class="col-lg-3 d-none d-lg-block user-panel-sidebar">
            <livewire:user.sidebar />
        </div>

        <div class="col-12 d-lg-none mb-3">
            @include('pages.user.common.mobile-tabs')
        </div>

        {{-- Skeleton --}}
        <div wire:loading class="col-lg-9 col-12 orders-skeleton">
            @for($i = 0; $i < 4; $i++)
                <div class="skeleton-row">
                    <span></span><span></span><span></span><span></span>
                    <span></span><span></span><span></span><span></span>
                </div>
            @endfor
        </div>

        <div wire:loading.remove class="col-lg-9 col-12 orders-page table-orderpage">

            {{-- HEADER --}}
            <div class="orders-header user-box d-flex align-items-center justify-content-between mb-3">
                <div>
                    <h3 class="fw-bold mb-0">My Orders</h3>
                    <span class="text-muted small">Showing your recent purchases</span>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <input type="text" id="orderSearch" class="form-control" placeholder="Search orders..."
                        style="width:200px">

                    <select id="perPageSelect" class="form-select" style="width:120px">
                        <option value="10">10</option>
                        <option value="25">25</option>
                        <option value="50">50</option>
                        <option value="100">100</option>
                    </select>
                </div>
            </div>

            {{-- TABLE --}}
            <div class="orders-card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table orders-table align-middle mb-0 w-100">
                            <thead>
                                <tr>
                                     <th>Order ID</th>
                                    <th>Order Type</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Auto-Topup</th>
                                    <th>Balance</th>
                                    <th>Date</th>
                                    <th>Total</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>

                            <tbody>
                                @forelse($orders as $o)
                                    <tr data-row>
                                         <td>{{ $o->id }}</td>
                                        <td>{{ $o->orderType }}</td>
                                       
                                         <td>
 {{ $o->plan?->zone?->zone_name ?? 'N/A' }},
{{ $o->plan?->GB ?? $o->GB }} GB,
{{ $o->plan?->Days ?? $o->Days }} Days

</td>



                                        </td>

                                        <td>
                                            @if(in_array(strtolower($o->paymentStatus), ['completed', 'paid']))
                                                <span class="status-pill status-success">Completed</span>
                                            @elseif(strtolower($o->paymentStatus) === 'pending')
                                                <span class="status-pill status-pending">Pending</span>
                                            @else
                                                <span class="status-pill status-failed">Failed</span>
                                            @endif
                                        </td>

                                        <td>
                                            <span class="auto-toggle {{ $o->autorenew ? 'on' : 'off' }}"
                                                wire:click="toggleAutoTopup({{ $o->id }})">
                                                {{ $o->autorenew ? 'ON' : 'OFF' }}
                                            </span>
                                        </td>

                                        <td>
                                            <a href="{{ route('orders.balance', $o->id) }}" class="balance-btn">
                                                Balance
                                            </a>
                                        </td>

                                        <td>{{ optional($o->date)->format('d-m-Y') }}</td>
                                        <td>${{ number_format($o->USD, 2) }}</td>

                                        <td class="text-end">
                                            <div class="action-icons">
                                                <a href="{{ route('orders.detail', $o->id) }}" class="action-btn edit">
                                                    <i class="fa-solid fa-info"></i>
                                                </a>
                                               @if(!empty($o->msisdn))
                                                <a href="{{ route('orders.recharge', ['msisdn' => $o->msisdn]) }}"
                                                class="action-btn recharge">
                                                        <i class="fa-solid fa-credit-card"></i>
                                                    </a>
                                                @else
                                                    <span class="text-muted small">N/A</span>
                                                @endif
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="9" class="text-center text-muted py-4">
                                            No orders found
                                        </td>
                                    </tr>
                                @endforelse

                                {{-- NO DATA ROW (JS) --}}
                                <tr id="noDataRow" style="display:none;">
                                    <td colspan="9" class="text-center text-muted py-4">
                                        No data found
                                    </td>
                                </tr>
                            </tbody>
                        </table>

                        {{-- PAGINATION --}}
                        <div class="d-flex justify-content-between align-items-center mt-3">
                            <div id="paginationInfo" class="js-pagination-info"></div>

                            <div class="js-pagination d-flex align-items-center gap-2">
                                <button id="prevPage" class="pagination-btn">‹</button>
                                <span id="pageNumbers"></span>
                                <button id="nextPage" class="pagination-btn">›</button>
                            </div>
                        </div>


                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<script>
    document.addEventListener('DOMContentLoaded', function () {

        const searchInput = document.getElementById('orderSearch');
        const perPageSelect = document.getElementById('perPageSelect');
        const tbody = document.querySelector('.orders-table tbody');
        const rows = Array.from(tbody.querySelectorAll('tr[data-row]'));
        const noDataRow = document.getElementById('noDataRow');

        let currentPage = 1;
        let perPage = parseInt(perPageSelect.value);

        function getFilteredRows() {
            const q = searchInput.value.toLowerCase();
            return rows.filter(row =>
                row.innerText.toLowerCase().includes(q)
            );
        }

        function renderTable() {
            const filtered = getFilteredRows();
            const total = filtered.length;

            rows.forEach(r => r.style.display = 'none');

            if (total === 0) {
                noDataRow.style.display = '';
                document.getElementById('paginationInfo').textContent = '';
                document.getElementById('pageNumbers').innerHTML = '';
                return;
            } else {
                noDataRow.style.display = 'none';
            }

            const start = (currentPage - 1) * perPage;
            const end = start + perPage;

            filtered.slice(start, end).forEach(row => {
                row.style.display = '';
            });

            renderPagination(total);
        }

        function renderPagination(total) {
            const totalPages = Math.ceil(total / perPage);
            const pageNumbers = document.getElementById('pageNumbers');
            const info = document.getElementById('paginationInfo');


            info.textContent =
                `Showing ${(currentPage - 1) * perPage + 1}
             to ${Math.min(currentPage * perPage, total)}
             of ${total} results`;

            pageNumbers.innerHTML = '';

            for (let i = 1; i <= totalPages; i++) {
                const btn = document.createElement('button');
                btn.className = 'pagination-btn' + (i === currentPage ? ' active' : '');

                btn.textContent = i;

                btn.onclick = () => {
                    currentPage = i;
                    renderTable();
                };

                pageNumbers.appendChild(btn);
            }

            const prev = document.getElementById('prevPage');
            const next = document.getElementById('nextPage');

            prev.classList.toggle('disabled', currentPage === 1);
            next.classList.toggle('disabled', currentPage === totalPages);

        }

        document.getElementById('prevPage').onclick = () => {
            if (currentPage > 1) {
                currentPage--;
                renderTable();
            }
        };

        document.getElementById('nextPage').onclick = () => {
            currentPage++;
            renderTable();
        };

        searchInput.addEventListener('keyup', () => {
            currentPage = 1;
            renderTable();
        });

        perPageSelect.addEventListener('change', () => {
            perPage = parseInt(perPageSelect.value);
            currentPage = 1;
            renderTable();
        });

        renderTable();
    });
</script>