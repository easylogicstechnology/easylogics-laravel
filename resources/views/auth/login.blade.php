<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Login - EasyLogics Technology</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f5f5; color: #333; min-height: 100vh; display: flex; flex-direction: column; }
        .header { background: #2c3e50; color: #fff; padding: 16px 24px; text-align: center; }
        .header h1 { font-size: 22px; font-weight: 600; }
        .main { flex: 1; display: flex; align-items: flex-start; justify-content: center; padding: 40px 16px; gap: 32px; flex-wrap: wrap; }
        .login-box { background: #fff; border-radius: 8px; padding: 32px; width: 100%; max-width: 400px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .login-box h2 { font-size: 20px; margin-bottom: 24px; color: #2c3e50; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; font-size: 14px; font-weight: 500; margin-bottom: 6px; color: #555; }
        .form-group input { width: 100%; padding: 10px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
        .form-group input:focus { outline: none; border-color: #3498db; box-shadow: 0 0 0 2px rgba(52,152,219,0.2); }
        .btn-login { width: 100%; padding: 12px; background: #2c3e50; color: #fff; border: none; border-radius: 4px; font-size: 15px; font-weight: 600; cursor: pointer; }
        .btn-login:hover { background: #34495e; }
        .alert { padding: 10px 14px; border-radius: 4px; margin-bottom: 16px; font-size: 13px; }
        .alert-error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .stats-box { background: #fff; border-radius: 8px; padding: 24px; width: 100%; max-width: 500px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stats-box h2 { font-size: 18px; margin-bottom: 16px; color: #2c3e50; }
        .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; }
        .stat-item { text-align: center; padding: 12px; background: #f8f9fa; border-radius: 6px; }
        .stat-item .number { font-size: 24px; font-weight: 700; color: #2c3e50; }
        .stat-item .label { font-size: 11px; color: #777; text-transform: uppercase; letter-spacing: 0.5px; margin-top: 4px; }
    </style>
</head>
<body>
    <div class="header">
        <h1>EasyLogics Technology</h1>
    </div>
    <div class="main">
        <div class="login-box">
            <h2>Login</h2>

            @if(session('error'))
                <div class="alert alert-error">{{ session('error') }}</div>
            @endif

            @if($errors->any())
                <div class="alert alert-error">{{ $errors->first() }}</div>
            @endif

            <form method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" value="{{ old('username') }}" required autofocus>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required>
                </div>
                <button type="submit" class="btn-login">Login</button>
            </form>
        </div>

        <div class="stats-box">
            <h2>At a Glance</h2>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="number">{{ number_format($stats['societies'] ?? 0) }}</div>
                    <div class="label">Societies</div>
                </div>
                <div class="stat-item">
                    <div class="number">{{ number_format($stats['members'] ?? 0) }}</div>
                    <div class="label">Members</div>
                </div>
                <div class="stat-item">
                    <div class="number">{{ number_format($stats['bills'] ?? 0) }}</div>
                    <div class="label">Bills</div>
                </div>
                <div class="stat-item">
                    <div class="number">{{ number_format($stats['resellers'] ?? 0) }}</div>
                    <div class="label">Resellers</div>
                </div>
                <div class="stat-item">
                    <div class="number">{{ number_format($stats['buildings'] ?? 0) }}</div>
                    <div class="label">Buildings</div>
                </div>
                <div class="stat-item">
                    <div class="number">{{ number_format($stats['wings'] ?? 0) }}</div>
                    <div class="label">Wings</div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
