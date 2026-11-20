# TikTok Shop - Vercel + Supabase

Loja em PHP rodando na Vercel (runtime `vercel-php`) com banco **Supabase (PostgreSQL)**.

## Estrutura
```
api/index.php      roteador unico (substitui o .htaccess)
app/               codigo PHP (admin, api, paginas)
public/            arquivos estaticos (assets, uploads antigos, favicon)
supabase/schema.sql  tabelas do banco
vercel.json
```

## Deploy
1. **Supabase**: crie o projeto > SQL Editor > cole e rode `supabase/schema.sql`.
2. **GitHub**: crie um repositorio e envie este projeto (`git push`).
3. **Vercel**: *Add New > Project* > importe o repositorio (Framework: *Other*).
4. Em *Settings > Environment Variables* cadastre as variaveis de `.env.example`.
5. Faca o *Deploy*. Acesse `/admin` (usuario `admin`, senha = `ADMIN_PASSWORD`).

## Observacoes
- Imagens enviadas no admin vao para o Supabase Storage (bucket `uploads`, criado pelo schema).
- Vercel limita o corpo da requisicao a 4,5 MB: envie poucas imagens por vez.
- Paginas de produto, `data/config.js` e `data/products.json` sao geradas dinamicamente do banco.
- Para migrar dados do MySQL antigo: exporte cada tabela em CSV (phpMyAdmin) e importe em *Table Editor > Import data*.
