<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign in — Future Legacy School SMS</title>
    <style>
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #16283f;
            font-family: Georgia, 'Times New Roman', serif;
        }
        .panel {
            background: #f7f4ee;
            width: 100%;
            max-width: 360px;
            padding: 2rem;
            border-radius: 8px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        .panel h1 {
            font-size: 1.1rem;
            color: #16283f;
            margin: 0 0 0.25rem;
        }
        .panel p.sub {
            color: #6b7280;
            font-size: 0.85rem;
            margin: 0 0 1.5rem;
        }
        label { display: block; font-size: 0.85rem; margin-bottom: 0.3rem; color: #16283f; }
        input[type=email], input[type=password] {
            width: 100%;
            padding: 0.55rem 0.7rem;
            margin-bottom: 1rem;
            border: 1px solid #d8d2bf;
            border-radius: 4px;
            font-family: inherit;
        }
        button {
            width: 100%;
            padding: 0.65rem;
            background: #16283f;
            color: #c8a24a;
            border: none;
            border-radius: 4px;
            font-weight: bold;
            cursor: pointer;
        }
        .errors { background: #fbeaea; border: 1px solid #e5b3b3; padding: 0.6rem 0.8rem;
            border-radius: 4px; margin-bottom: 1rem; font-size: 0.85rem; color: #8a1f1f; }
    </style>
</head>
<body>
    <div class="panel">
        <h1>Future Legacy School</h1>
        <p class="sub">Sign in to the School Management System</p>

        @if ($errors->any())
            <div class="errors">
                @foreach ($errors->all() as $error)
                    <div>{{ $error }}</div>
                @endforeach
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <button type="submit">Sign in</button>
        </form>
    </div>
</body>
</html>
