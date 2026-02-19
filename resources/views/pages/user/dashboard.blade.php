<div class="dashboard-page page-background">
    <div class="container py-4">
        <div class="row">

            <!-- SIDEBAR -->
            <div class="col-lg-3 d-none d-lg-block">
                <livewire:user.sidebar />
            </div>

            <!-- MOBILE TABS -->
            <div class="col-12 d-lg-none mb-3">
                @include('pages.user.common.mobile-tabs')
            </div>

            <!-- CONTENT -->
            <div class="col-lg-9">
                <div class="dashboard-content">

                    <!-- HEADER -->
                    <div class="user-box dashboard-header">
                        <h3>Hello, {{ auth()->user()->fname }} </h3>
                        <p class="text-muted">Welcome back to your gsm2go dashboard</p>
                    </div>

                    <!-- STATS -->
                    <div class="user-dashboard-info row g-4 mt-2">

                        <div class="col-md-6">
                            <div class="stats-card">
                                <div class="stats-icon"><i class="fa-solid fa-cart-shopping"></i></div>
                                <div>
                                    <h5>{{ auth()->user()->orders()->count() ?? 0 }}</h5>
                                    <span>Total Orders</span>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <div class="stats-card green">
                                <div class="stats-icon"><i class="fa-solid fa-user"></i></div>
                                <div>
                                    <h5>{{ auth()->user()->email }}</h5>
                                    <span>Account Email</span>
                                </div>
                            </div>
                        </div>

                    </div>

                    <!-- ACCOUNT INFO -->
                    <div class="info-card mt-4">
                        <div class="info-header text-success">
                            <h5>Account Information</h5>
                        </div>

                        <ul class="p-0">
                            <li><strong>Name:</strong> {{ auth()->user()->fname }} {{ auth()->user()->lname }}</li>
                            <li><strong>Email:</strong> {{ auth()->user()->email }}</li>
                            <li><strong>Phone:</strong> +{{ auth()->user()->country_code }} {{ auth()->user()->mobile }}</li>
                        </ul>
                    </div>

                    <!-- QUICK ACTIONS -->
                    <div class="row g-3 mt-4">
                        <div class="col-md-4">
                            <a href="{{ route('user.orders') }}" class="quick-card">
                               <i class="fa-solid fa-cart-shopping"></i> View Orders
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('user.profile') }}" class="quick-card">
                                <i class="fa-solid fa-pencil"></i> Edit Profile
                            </a>
                        </div>
                        <div class="col-md-4">
                            <a href="{{ route('user.password') }}" class="quick-card danger">
                                <i class="fa-solid fa-key"></i> Change Password
                            </a>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </div>
</div>
