<div class="dashboard-layout">
    <div class="dashboard-sidebar">
        <div class="user-box p-4">
            <h4>{{ auth()->user()->fname }} {{ auth()->user()->lname }}</h4>
            <small>{{ auth()->user()->email }}</small>
        </div>

        <ul class="dashboard-menu mt-4">
            <li class="{{ request()->routeIs('user.dashboard') ? 'active' : '' }}">
                <a href="{{ route('user.dashboard') }}">Dashboard</a>
            </li>
            <li class="{{ request()->routeIs('user.orders') ? 'active' : '' }}">
                <a href="{{ route('user.orders') }}">Orders</a>
            </li>
            <li class="{{ request()->routeIs('user.profile') ? 'active' : '' }}">
                <a href="{{ route('user.profile') }}">Personal Information</a>
            </li>
            <li class="{{ request()->routeIs('user.password') ? 'active' : '' }}">
                <a href="{{ route('user.password') }}">Password</a>
            </li>
            <li wire:click="logout" class="logout">Logout</li>
        </ul>
    </div>

</div>
