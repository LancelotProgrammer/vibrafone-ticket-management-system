<!-- Navbar Start -->
<nav class="navbar navbar-expand-lg navbar-dark px-5 py-3 py-lg-0">
    <a href="/" class="navbar-brand p-2">
        <h1></h1>
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="fa fa-bars"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto py-1">
            <a href="/" class="nav-item nav-link {{ request()->is('/') ? 'active' : '' }}">Home</a>
            <a href="/contact" class="nav-item nav-link {{ request()->is('contact') ? 'active' : '' }}">Contact</a>
        </div>
        @auth
            <a href="/admin/login" class="btn btn-primary py-2 px-4 ms-3">Dashboard</a>
        @endauth
        @guest
            <a href="/admin/login" class="btn btn-primary py-2 px-4 ms-3">Register / Login</a>
        @endguest
    </div>
</nav>
<!-- Navbar End -->
