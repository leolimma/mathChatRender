=== Math Chat Render ===
Contributors: Leonardo Lima
Donate link: https://www.paypal.com/invoice/p/#FPHZV27JRERTAFZX
Tags: mathjax, latex, ai engine, chat, chatbot, equations, math, formulas, render
Requires at least: 5.0
Tested up to: 6.8
Stable tag: 1.2.1
Requires PHP: 7.0
Requires Plugins: ai-engine
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Renderiza LaTeX (MathJax) em tempo real nas janelas de chat do AI Engine (MWAI).

== Description ==

Math Chat Render é um complemento para o plugin AI Engine (MWAI Chatbot). Ele detecta mensagens novas do chatbot e renderiza fórmulas LaTeX usando MathJax em tempo real, incluindo mensagens que chegam por streaming.

Principais capacidades:
- Detecta novas mensagens do chat (MutationObserver).
- Renderiza apenas as partes necessárias com MathJax para evitar repinturas desnecessárias.
- Força o scroll para seguir a última mensagem após renderizar.
- Compatível com MathJax v4 (aguarda MathJax.startup.promise antes de typeset).

== Installation ==

1. Certifique-se de que o plugin AI Engine está instalado e ativo.
2. Faça upload da pasta `mathChatRender` para `/wp-content/plugins/` ou envie o arquivo `mathChatRender.zip` via `Plugins > Adicionar Novo > Enviar plugin`.
3. Ative o plugin em `Plugins` no admin do WordPress.
4. Abra uma página com o chat do AI Engine e teste enviando mensagens com LaTeX.

Observação: o plugin injeta por padrão o MathJax via CDN. Se você preferir usar uma cópia local ou outro provedor, use os filtros PHP documentados em `FILTERS.md`.

== Frequently Asked Questions ==

= O plugin requer configuração no admin? =
Não — por padrão funciona sem UI. As opções foram removidas da interface para evitar inconsistência em runtime; em vez disso o plugin expõe filtros PHP para personalização (veja `FILTERS.md`).

= O que fazer se as fórmulas só aparecem após recarregar a página? =
Isso indica que o handler não detectou a mutação. Primeiro, verifique o Console do navegador para mensagens com o prefixo `MathChatRender:`. Você também pode forçar uma re-renderização via Console:

```js
globalThis.mcr.refresh()
```

Se funcionar ao forçar, o problema é detecção (observer) — abra uma issue com logs e eu ajudo a ajustar a observação.

== Screenshots ==

Nenhuma screenshot incluída.

== Changelog ==

= 1.2.1 =
- Correções de compatibilidade com MathJax v4 (aguarda MathJax.startup.promise).
- Usa handle próprio `mcr-mathjax` para reduzir conflitos.
- Injeta configuração segura (nowdoc) e adiciona fallback local via `/vendor` se necessário.

= 1.1.0 =
- Refinamentos na lógica de pausa/retoma do observer e redução de logs desnecessários.

= 1.0.3 =
- Adiciona suporte para delimitadores experimentais `(...)` e `[...]`.

= 1.0.0 =
- Lançamento inicial: observer + debounce para render em streaming.

== Upgrade Notice ==

= 1.2.1 =
Se atualizar de versões anteriores, limpe o cache do navegador e verifique que a versão do `js/mwai-mathjax-handler.js` servida é a atual. Em instalações que usam outro plugin para MathJax, use o filtro `mcr_load_mathjax` para desativar o carregamento pelo Math Chat Render.

== For Developers ==

O plugin expõe os seguintes filtros (veja `FILTERS.md`): `mcr_should_enqueue`, `mcr_load_mathjax`, `mcr_config`, `mcr_mathjax_sri`.

Para desenvolvimento local, o arquivo fonte está em `src/mwai-mathjax-handler.mjs` e usa esbuild para gerar o bundle em `js/mwai-mathjax-handler.js`.
