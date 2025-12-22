<?php
session_start();

// ✅ Safe redirect check
if (
    isset($_SESSION['is_logged_in'], $_SESSION['csrf_token'], $_SESSION['base_url']) &&
    $_SESSION['is_logged_in'] === true &&
    is_string($_SESSION['csrf_token']) &&
    filter_var($_SESSION['base_url'], FILTER_VALIDATE_URL)
) {
    header("Location: " . rtrim($_SESSION['base_url'], '/') . "/store/layout/start/");
    exit();
}
?>
<!doctype html>
<html lang="en">

<head>
  <title>Login</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- Bootstrap CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/css/bootstrap.min.css" rel="stylesheet">

  <style>
    body {
      background-color: #f4f4f4;
      background-image: url('bg.jpg');
      background-size: cover;
      background-repeat: no-repeat;
    }

    .rounded-t-5 {
      border-top-left-radius: 0.5rem;
      border-top-right-radius: 0.5rem;
    }

    @media (min-width: 992px) {
      .rounded-tr-lg-0 { border-top-right-radius: 0; }
      .rounded-bl-lg-5 { border-bottom-left-radius: 0.5rem; }
    }
  </style>
</head>

<body class="d-flex align-items-center justify-content-center" style="min-height: 100vh;">

<main class="container">
  <section class="text-center text-lg-start">
    <div class="card mb-3" style="background-color: rgba(255,255,255,0.85);">

      <!-- ✅ Proper Error Alert -->
      <?php if (isset($_GET['error'])): ?>
        <div class="alert alert-danger text-center m-3" role="alert">
          <?= htmlspecialchars($_GET['error']) ?>
        </div>
      <?php endif; ?>

      <div class="row g-0 d-flex align-items-center">
        <div class="col-lg-4 d-none d-lg-flex">
          <img src="site_logo.jpg"
               class="w-100 rounded-t-5 rounded-tr-lg-0 rounded-bl-lg-5"
               alt="Site Logo">
        </div>

        <div class="col-lg-8">
          <div class="card-body py-5 px-md-5">

            <form method="POST" action="mailvarification.php">
              <!-- Email -->
              <div class="form-outline mb-4">
                <input type="email" name="email" class="form-control" required>
                <label class="form-label">Email address</label>
              </div>

              <!-- Password -->
              <div class="form-outline mb-4">
                <input type="password" name="password" class="form-control" required>
                <label class="form-label">Password</label>
              </div>

              <!-- Remember Me -->
              <div class="form-check mb-4">
                <input class="form-check-input" type="checkbox" name="remember">
                <label class="form-check-label">Remember me</label>
              </div>

              <!-- Submit -->
              <button type="submit" class="btn btn-primary w-100 mb-3">
                Sign In
              </button>

            </form>

          </div>
        </div>
      </div>
    </div>
  </section>
</main>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.1/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>
