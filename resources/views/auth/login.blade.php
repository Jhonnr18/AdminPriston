<!doctype html>
<html lang="pt-BR">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Entrar · AdminPriston</title>@vite(['resources/css/app.css','resources/js/app.js'])</head>
<body class="vlh-shell" style="display:grid;place-items:center;min-height:100vh">
    <main class="vlh-card" style="width:min(420px,calc(100% - 2rem));padding:2rem">
        <h1 class="text-xl font-semibold mb-4">Entrar no AdminPriston</h1>
        @if ($errors->any()) <div class="vlh-callout danger mb-4">{{ $errors->first() }}</div> @endif
        <form method="post" action="{{ route('login.store') }}">
            @csrf
            <label class="block mb-3">E-mail<input class="vlh-input w-full" type="email" name="email" value="{{ old('email') }}" required autofocus></label>
            <label class="block mb-4">Senha<input class="vlh-input w-full" type="password" name="password" required></label>
            <label class="block mb-4 text-sm"><input type="checkbox" name="remember" value="1"> Lembrar sessão</label>
            <button class="vlh-button" type="submit">Entrar</button>
        </form>
    </main>
</body>
</html>
