<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - Telkomsat</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      padding: 0;
      height: 100vh;
      display: flex;
      justify-content: center;
      align-items: center;
      background-color: #f4f4f4;
    }
    .login-container {
      display: flex;
      width: 800px;
      height: 500px;
      border-radius: 10px;
      overflow: hidden;
      box-shadow: 0 0 20px rgba(0,0,0,0.2);
    }
    .login-left {
      background: linear-gradient(to bottom, #648d9eff, #7a504bff);
      color: white;
      width: 50%;
      padding: 30px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
    }
    .login-right {
      background: white;
      width: 50%;
      padding: 40px;
    }
    .btn-login {
      background-color: #a56b4c;
      color: white;
      border: none;
      width: 100%;
    }
  </style>
</head>
<body>
  <div class="login-container">
    
    <!-- Kiri -->
    <div class="login-left text-center">
      <h2 class="fw-bold">SELAMAT DATANG</h2>
      <p>BUSINESS PROJECT WEBSITE</p>
      <img src="https://cdn-icons-png.flaticon.com/512/194/194938.png" alt="User" class="mt-3" style="width: 80px;">
    </div>
    
    <!-- Kanan -->
    <div class="login-right">
      <!-- Logo di tengah atas -->
      <div class="logo-container text-center mb-3">
        <img src="{{ asset('images/logo-telkomsat.png') }}" 
            alt="Telkomsat Logo" 
            style="max-height: 120px; display:block; margin:0 auto;">
      </div>

      <!-- Form Sign In -->
      <h4 class="text-center">SIGN IN</h4>
      <form method="POST" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
          <label for="username">Username :</label>
          <input type="text" id="username" name="username" class="form-control" placeholder="Username" required>
        </div>
        <div class="mb-3">
          <label for="password">Password :</label>
          <input type="password" id="password" name="password" class="form-control" placeholder="Password" required>
        </div>
        <div class="form-check mb-3">
          <input class="form-check-input" type="checkbox" id="remember" name="remember">
          <label class="form-check-label" for="remember">Remember Me</label>
        </div>
        <button type="submit" class="btn btn-login w-100">MASUK</button>
      </form>
    </div>
  </div>
</body>
</html>
