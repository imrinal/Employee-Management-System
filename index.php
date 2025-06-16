<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <title>Employee Management System</title>

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css">
    <link href="resorce/css/style.css" rel="stylesheet">

    <!-- Font + Icons -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">

    <style>
      body, html {
        height: 100%;
        margin: 0;
        font-family: 'Inter', sans-serif;
        background: linear-gradient(120deg, #c471ed, #f7797d, #fbd786);
        background-size: 400% 400%;
        animation: gradientMove 10s ease infinite;
      }

      @keyframes gradientMove {
        0% { background-position: 0% 50%; }
        50% { background-position: 100% 50%; }
        100% { background-position: 0% 50%; }
      }

      .bg {
        height: 100%;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 30px;
      }

      .login-form-bg {
        width: 100%;
      }

      .card.login-form {
        background: rgba(255, 255, 255, 0.15);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.25);
        border-radius: 20px;
        color: white;
      }

      .card.login-form h2,
      .card.login-form h6 {
        color: #fff;
        font-weight: 600;
      }

      .btn-primary {
        background-color: rgba(255, 255, 255, 0.2);
        border: none;
        color: white;
        padding: 12px 24px;
        border-radius: 10px;
        backdrop-filter: blur(5px);
        font-weight: 600;
        transition: all 0.3s ease-in-out;
      }

      .btn-primary:hover {
        background-color: rgba(255, 255, 255, 0.4);
        color: #333;
        box-shadow: 0 8px 20px rgba(255, 255, 255, 0.2);
      }

      .btn-toolbar {
        flex-wrap: wrap;
        gap: 1rem;
      }
    </style>
  </head>

  <body>
    <div class="bg border">
      <div class="login-form-bg h-100">
        <div class="container h-100">
          <div class="row justify-content-center h-100">
            <div class="col-xl-6">
              <div class="form-input-content">
                <div class="card login-form mt-5">
                  <div class="card-body shadow">
                    <h2 class="text-center pb-4">Employee Management System</h2>
                    <h6 class="text-center pb-4">Please Log-In According To Your Role!!</h6>
                    <div class="container mt-4">
                      <div class="btn-toolbar justify-content-between">
                        <div class="btn-group">
                          <a href="employee/dashboard.php" class="btn btn-primary btn-lg">Log-in As Employee</a>
                        </div>
                        <div class="btn-group">
                          <a href="admin/dashboard.php" class="btn btn-primary btn-lg">Log-In As Admin</a>
                        </div>
                      </div>
                    </div>         
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div> 
    </div>

    <!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="./resorce/plugins/common/common.min.js"></script>
    <script src="./resorce/js/custom.min.js"></script>
    <script src="./resorce/js/settings.js"></script>
    <script src="./resorce/js/gleek.js"></script>
    <script src="./resorce/js/styleSwitcher.js"></script>
  </body>
</html>
