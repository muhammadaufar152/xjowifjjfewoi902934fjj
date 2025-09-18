<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>@yield('title', 'Document Page - Telkomsat')</title>

  <!-- Bootstrap & Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">

  <style>
    body {
      font-family: 'Poppins', sans-serif;
      background-color: #f4f4f4;
      margin: 0;
      padding: 0;
    }

    .sidebar {
      background: linear-gradient(to bottom, #648d9eff, #7a504bff);
      color: white;
      min-height: 100vh;
      padding: 30px 20px;
    }

    /* BESARKAN LOGO – hanya untuk logo brand */
    .sidebar .brand-logo{
      max-height: 150px;
      width: 100%;
      height: auto;
    }

    .sidebar .user-info {
      text-align: center;
      margin-bottom: 30px;
    }

    /* avatar user tetap 80px */
    .sidebar .user-info img {
      width: 80px;
      height: 80px;
      border-radius: 50%;
    }

    .sidebar .user-info .fw-bold { margin-top: 10px; font-size: 16px; }

    .sidebar .nav-link {
      color: white;
      margin: 10px 0;
      font-weight: 500;
      padding: 8px 15px;
      border-radius: 6px;
      text-align: left;
      width: 100%;
    }
    .sidebar .nav-link.active, .sidebar .nav-link:hover { background-color: white; color: #7b3e2f; }

    .sidebar .logout-btn { background-color: #8b655cff; color: white; width: 100%; border: none; padding: 10px; border-radius: 6px; }
    .sidebar .logout-btn:hover { background-color: #936d63ff; }
  </style>

  @stack('styles')
</head>
<body>
  <div class="container-fluid">
    <div class="row">
      <!-- Sidebar -->
      <div class="col-md-2 sidebar d-flex flex-column align-items-center">
        <!-- tambahkan class brand-logo -->
        <img src="{{ asset('images/logo-telkomsat.png') }}" alt="Telkomsat Logo" class="img-fluid mb-4 brand-logo">

        <div class="user-info">
          <img src="https://randomuser.me/api/portraits/men/32.jpg" alt="User">
          <div class="fw-bold text-white">{{ Auth::user()->name }}</div>
          <div class="text-white">{{ ucfirst(Auth::user()->getRoleNames()->first()) }}</div>
        </div>

        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">Home</a>
        <a href="{{ route('document') }}" class="nav-link {{ request()->routeIs('document') ? 'active' : '' }}">Document</a>
        {{-- <a href="{{ route('form_review.index') }}" class="nav-link {{ request()->routeIs('form_review.*') ? 'active' : '' }}">Form Review</a>
        <a href="{{ route('ai.index') }}" class="nav-link {{ request()->routeIs('ai.index') ? 'active' : '' }}">Action Item</a>
        <a href="{{ route('km.index') }}" class="nav-link {{ request()->routeIs('km.index') ? 'active' : '' }}">KM</a> --}}

        <div class="mt-auto w-100">
          <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-btn mt-5">Log Out</button>
          </form>
        </div>
      </div>

      <!-- Konten Utama -->
      <div class="col-md-10 py-4 px-3">
        @yield('content')
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  @stack('scripts')
</body>
</html>
