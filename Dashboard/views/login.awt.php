<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $name }} | Dashboard Login</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    <link rel="stylesheet" href="@asset('css/main.css')">
</head>
<body class="awt-ui">
<main class="min-vh-100 d-flex align-items-center justify-content-center p-3">
    <section class="card w-100 mw-sm" aria-labelledby="login-title">
        <header class="text-center mb-4">
            <p class="eyebrow mb-2">Welcome back</p>
            <h1 id="login-title" class="h2 m-0">{{ $name }}</h1>
            <p class="card-subtitle">Sign in to your dashboard</p>
        </header>
        <form action="/dashboard/action/login" method="post" class="d-flex flex-column gap-3">
            @csrf
            @method("post")
            <div>
                <label for="input_username" class="form-label mb-1">Username</label>
                <input type="text" name="username" id="input_username" class="form-control"
                       placeholder="Enter your username" autocomplete="username"
                       autocapitalize="none" spellcheck="false" required>
            </div>
            <div>
                <label for="input_password" class="form-label mb-1">Password</label>
                <input type="password" name="password" id="input_password" class="form-control"
                       placeholder="Enter your password" autocomplete="current-password" required>
            </div>
            <button type="submit" class="btn btn-primary w-100 mt-2">Sign in</button>
            <div class="text-center mt-2">
                <a href="/dashboard/forgot-password" class="link text-small d-inline-flex align-items-center min-touch">Forgot password?</a>
            </div>
        </form>
    </section>
</main>
</body>
</html>
