<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recuperação de senha</title>
</head>
<body>
    <p>Olá, {{ $usuario->nome }},</p>
    <p>Você solicitou a recuperação de senha no sistema Brasa. Use o token abaixo no aplicativo ou site, no fluxo de redefinição de senha:</p>
    <p><strong>Token:</strong> {{ $tokenPlano }}</p>
    <p>Este token expira em 30 minutos. Se você não fez esta solicitação, ignore este email.</p>
    <p>— Equipe Brasa</p>
</body>
</html>
