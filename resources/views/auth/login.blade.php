@extends('layouts.guest-login')

@section('content')
<div class="container d-flex justify-content-center align-items-center min-vh-100">
  <div class="login-container shadow rounded overflow-hidden d-flex" style="width: 800px;">
    
    {{-- Kiri --}}
    <div class="login-left bg-gradient p-4 text-white text-center d-flex flex-column justify-content-center" style="background: linear-gradient(to bottom, #7b3e2f, #a56b4c); width: 50%;">
      <h2 class="fw-bold">SELAMAT DATANG</h2>
      <p>BUSINESS PROJECT WEBSITE</p>
      <img src="https://cdn-icons-png.flaticon.com/512/194/194938.png" alt="Illustration" class="img-fluid mt-3" style="width: 80px;">
    </div>

    {{-- Kanan --}}
    <div class="login-right p-4" style="width: 50%;">
      <div class="text-center mb-4">
       <img src="{{ asset('images/logo-telkomsat.png') }}" alt="Telkomsat Logo" style="max-height: 60px;">
      </div>
      <h3 class="fw-bold mb-3">SIGN IN</h3>
      <form method="POST" action="{{ route('login') }}">
     @csrf
        <div class="mb-3">
            <label for="username" class="form-label">Username :</label>
            <input type="text" class="form-control" id="username" name="username" placeholder="Username" required autofocus>
        </div>
        <div class="mb-3">
          <label for="password" class="form-label">Password :</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="Password" required>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="remember" name="remember">
          <label class="form-check-label" for="remember">Remember Me</label>
        </div>
        <button type="submit" class="btn w-100 text-white" style="background-color: #a56b4c;">MASUK</button>
      </form>
    </div>
    
  </div>
</div>
@endsection
